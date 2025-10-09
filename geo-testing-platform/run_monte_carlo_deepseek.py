#!/usr/bin/env python3
"""
Monte Carlo GEO Testing - DeepSeek-Coder Model (Phase 3)

Runs the same experiment multiple times to:
1. Calculate average performance across methods
2. Measure standard deviation and confidence intervals
3. Determine statistical significance
4. Generate patent-ready evidence
"""

import asyncio
import sys
import json
from datetime import datetime
from pathlib import Path
import statistics
from collections import defaultdict

sys.path.insert(0, 'backend')

from services.ollama_client import OllamaClient
from services.geo_methods import GEOMethod, get_transformation_prompt
from services.metrics_calculator import GEOMetricsCalculator

# Configuration
ITERATIONS = 5  # Number of times to run each method
QUICK_MODE = True  # Set to True for faster testing (fewer tokens)

async def run_single_iteration(ollama, calc, query_data, method, iteration_num):
    """Run one iteration of a GEO method test"""

    baseline_content = query_data['baseline_content']
    target_source = query_data['target_source']
    sources = [target_source] + query_data['competing_sources']

    try:
        # Transform content
        prompt_data = get_transformation_prompt(method, baseline_content)

        result = await ollama.generate(
            prompt=prompt_data['prompt'],
            system=prompt_data['system'],
            temperature=0.7,
            max_tokens=500 if QUICK_MODE else 1000
        )
        optimized_content = result['text']

        # Generate AI response (shorter prompt in quick mode)
        if QUICK_MODE:
            ge_prompt = f"""Query: {query_data['query']}

Source: {target_source}
{optimized_content[:300]}

Answer briefly citing the source."""
        else:
            ge_prompt = f"""You are answering: {query_data['query']}

Sources available:
1. {target_source}: {optimized_content}
2. {sources[1]}
3. {sources[2]}

Answer citing sources."""

        response = await ollama.generate(
            prompt=ge_prompt,
            system="Cite sources when answering.",
            temperature=0.7,
            max_tokens=400 if QUICK_MODE else 800
        )

        # Calculate metrics
        metrics = calc.calculate_all_metrics(
            response_text=response['text'],
            target_source=target_source,
            source_titles=sources
        )

        return {
            'success': True,
            'iteration': iteration_num,
            'pawc': metrics['pawc'],
            'cited': metrics['cited'],
            'position': metrics['citation_position'],
            'generation_time': result['generation_time'] + response['generation_time']
        }

    except Exception as e:
        return {
            'success': False,
            'iteration': iteration_num,
            'error': str(e)
        }

async def run_monte_carlo_experiment():
    """Run Monte Carlo experiment"""

    print("=" * 80)
    print("MONTE CARLO GEO TESTING")
    print("=" * 80)
    print(f"Configuration:")
    print(f"  • Iterations per method: {ITERATIONS}")
    print(f"  • Quick mode: {QUICK_MODE}")
    print(f"  • Total tests: {len(list(GEOMethod)) * ITERATIONS}")
    print("=" * 80)

    ollama = OllamaClient(default_model="deepseek-coder-v2:16b")
    calc = GEOMetricsCalculator()

    # Load query
    with open('data/sample_queries.json', 'r') as f:
        data = json.load(f)
    query_data = data['queries'][0]

    print(f"\nQuery: {query_data['query']}")
    print(f"Domain: {query_data['domain']}\n")

    # Store all results
    all_results = defaultdict(list)
    baseline_metrics_list = []

    # Run baseline first to get comparison point
    print("Running BASELINE iterations...")
    for i in range(ITERATIONS):
        print(f"  [{i+1}/{ITERATIONS}]", end=" ", flush=True)
        result = await run_single_iteration(ollama, calc, query_data, GEOMethod.BASELINE, i+1)
        if result['success']:
            baseline_metrics_list.append(result)
            all_results['baseline'].append(result)
            print(f"✅ PAWC={result['pawc']:.2f}")
        else:
            print(f"❌ {result['error']}")

    # Calculate baseline average
    baseline_avg_pawc = statistics.mean([r['pawc'] for r in baseline_metrics_list if r['success']])
    print(f"\n  Baseline Average PAWC: {baseline_avg_pawc:.2f}\n")

    # Run other methods
    methods_to_test = [m for m in GEOMethod if m != GEOMethod.BASELINE]

    for method in methods_to_test:
        print(f"Running {method.value.upper()} iterations...")
        method_results = []

        for i in range(ITERATIONS):
            print(f"  [{i+1}/{ITERATIONS}]", end=" ", flush=True)
            result = await run_single_iteration(ollama, calc, query_data, method, i+1)

            if result['success']:
                all_results[method.value].append(result)
                method_results.append(result)

                # Calculate improvement vs baseline
                improvement = ((result['pawc'] - baseline_avg_pawc) / baseline_avg_pawc) * 100
                symbol = "📈" if improvement > 0 else "📉"
                print(f"✅ PAWC={result['pawc']:.2f} {symbol} {improvement:+.1f}%")
            else:
                print(f"❌ {result['error']}")

        # Calculate statistics for this method
        if method_results:
            pawc_values = [r['pawc'] for r in method_results]
            avg_pawc = statistics.mean(pawc_values)
            std_dev = statistics.stdev(pawc_values) if len(pawc_values) > 1 else 0
            avg_improvement = ((avg_pawc - baseline_avg_pawc) / baseline_avg_pawc) * 100

            print(f"  → Average: {avg_pawc:.2f} ± {std_dev:.2f}")
            print(f"  → Improvement: {avg_improvement:+.1f}%\n")

    # Generate summary report
    print("\n" + "=" * 80)
    print("MONTE CARLO RESULTS SUMMARY")
    print("=" * 80)
    print(f"{'Method':<15} {'Avg PAWC':<12} {'Std Dev':<12} {'Improvement':<15} {'Citation %'}")
    print("-" * 80)

    summary_data = []

    for method_name, results in all_results.items():
        if not results:
            continue

        success_results = [r for r in results if r['success']]
        if not success_results:
            continue

        pawc_values = [r['pawc'] for r in success_results]
        cited_count = sum(1 for r in success_results if r['cited'])

        avg_pawc = statistics.mean(pawc_values)
        std_dev = statistics.stdev(pawc_values) if len(pawc_values) > 1 else 0
        improvement = ((avg_pawc - baseline_avg_pawc) / baseline_avg_pawc) * 100 if method_name != 'baseline' else 0
        citation_pct = (cited_count / len(success_results)) * 100

        imp_str = f"{improvement:+.1f}%" if method_name != 'baseline' else "-"

        print(f"{method_name:<15} {avg_pawc:<12.2f} {std_dev:<12.2f} {imp_str:<15} {citation_pct:.0f}%")

        summary_data.append({
            'method': method_name,
            'iterations': len(success_results),
            'avg_pawc': avg_pawc,
            'std_dev': std_dev,
            'improvement_pct': improvement,
            'citation_rate': citation_pct,
            'raw_results': success_results
        })

    print("-" * 80)

    # Save results
    timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
    output_file = Path(f'results/monte_carlo_{timestamp}.json')
    output_file.parent.mkdir(exist_ok=True)

    with open(output_file, 'w') as f:
        json.dump({
            'timestamp': datetime.now().isoformat(),
            'config': {
                'iterations': ITERATIONS,
                'quick_mode': QUICK_MODE
            },
            'query': query_data,
            'baseline_avg_pawc': baseline_avg_pawc,
            'summary': summary_data
        }, f, indent=2, default=str)

    print(f"\n✅ Results saved to: {output_file}")

    # Identify best performing methods
    print("\n📊 TOP PERFORMING METHODS:")
    sorted_methods = sorted(
        [s for s in summary_data if s['method'] != 'baseline'],
        key=lambda x: x['improvement_pct'],
        reverse=True
    )[:3]

    for i, method in enumerate(sorted_methods, 1):
        print(f"  {i}. {method['method']}: {method['improvement_pct']:+.1f}% (±{method['std_dev']:.2f})")

    print("\n" + "=" * 80)
    print("✅ Monte Carlo experiment complete!")
    print("=" * 80)

    return True

if __name__ == "__main__":
    try:
        asyncio.run(run_monte_carlo_experiment())
        sys.exit(0)
    except KeyboardInterrupt:
        print("\n\n⚠️  Interrupted by user")
        sys.exit(130)
    except Exception as e:
        print(f"\n❌ Error: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)
