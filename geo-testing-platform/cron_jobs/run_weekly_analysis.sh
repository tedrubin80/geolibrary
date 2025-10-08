#!/bin/bash

# GEO Testing Platform - Weekly Full Analysis
# Runs comprehensive analysis and generates reports

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
BACKEND_DIR="$PROJECT_DIR/backend"
RESULTS_DIR="$PROJECT_DIR/results"
ANALYSIS_DIR="$RESULTS_DIR/analysis"
LOG_DIR="$PROJECT_DIR/logs"
VENV_DIR="$PROJECT_DIR/venv"

# Create directories
mkdir -p "$RESULTS_DIR" "$ANALYSIS_DIR" "$LOG_DIR"

# Timestamp
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
LOG_FILE="$LOG_DIR/weekly_analysis_$TIMESTAMP.log"

echo "========================================" | tee -a "$LOG_FILE"
echo "GEO Weekly Analysis - $(date)" | tee -a "$LOG_FILE"
echo "========================================" | tee -a "$LOG_FILE"

# Activate virtual environment
if [ -d "$VENV_DIR" ]; then
    source "$VENV_DIR/bin/activate"
    echo "✅ Virtual environment activated" | tee -a "$LOG_FILE"
else
    echo "❌ Virtual environment not found" | tee -a "$LOG_FILE"
    exit 1
fi

# Run weekly analysis
echo "Running weekly analysis..." | tee -a "$LOG_FILE"
cd "$BACKEND_DIR"

python3 << EOF | tee -a "$LOG_FILE"
import asyncio
import sys
import json
from datetime import datetime, timedelta
from pathlib import Path
import pandas as pd
from collections import defaultdict

sys.path.insert(0, '$BACKEND_DIR')

from services.ollama_client import OllamaClient
from services.geo_methods import GEOMethod, get_transformation_prompt, get_method_description
from services.metrics_calculator import GEOMetricsCalculator

async def run_weekly_analysis():
    print("=" * 70)
    print("GEO WEEKLY COMPREHENSIVE ANALYSIS")
    print("=" * 70)

    ollama = OllamaClient()
    calc = GEOMetricsCalculator()

    if not await ollama.check_health():
        print("❌ Ollama is not healthy")
        return False

    print("✅ Ollama is healthy\\n")

    # Test queries across different domains
    test_queries = [
        {
            "query": "What are the best Italian restaurants?",
            "domain": "food",
            "content": "Mario's Restaurant serves authentic Italian cuisine with fresh ingredients daily.",
            "target": "Mario's Restaurant"
        },
        {
            "query": "How to optimize website performance?",
            "domain": "technology",
            "content": "Website optimization involves improving page speed, mobile responsiveness, and user experience.",
            "target": "Web Performance Guide"
        },
        {
            "query": "What are the benefits of exercise?",
            "domain": "health",
            "content": "Regular exercise improves cardiovascular health, strength, and mental well-being.",
            "target": "Health & Fitness Guide"
        }
    ]

    all_results = defaultdict(list)

    for query_data in test_queries:
        print(f"\\nTesting domain: {query_data['domain']}")
        print(f"Query: {query_data['query']}")
        print("-" * 70)

        baseline_metrics = None

        # Test all methods for this query
        for method in GEOMethod:
            print(f"  Testing {method.value}...", end=" ")

            # Transform content
            prompt_data = get_transformation_prompt(method, query_data['content'])
            try:
                result = await ollama.generate(
                    prompt=prompt_data['prompt'],
                    system=prompt_data['system'],
                    temperature=0.7,
                    max_tokens=2000
                )

                # Generate AI response
                ge_prompt = f\"\"\"
Query: {query_data['query']}

Source: {query_data['target']}
{result['text']}

Please answer the query citing the source when appropriate.
\"\"\"

                response = await ollama.generate(
                    prompt=ge_prompt,
                    system="You are a helpful AI assistant. Cite sources when making factual claims.",
                    temperature=0.7,
                    max_tokens=1000
                )

                # Calculate metrics
                if method == GEOMethod.BASELINE:
                    baseline_metrics = calc.calculate_all_metrics(
                        response_text=response['text'],
                        target_source=query_data['target'],
                        source_titles=[query_data['target'], "Alternative Source"]
                    )
                    metrics = baseline_metrics
                else:
                    metrics = calc.calculate_all_metrics(
                        response_text=response['text'],
                        target_source=query_data['target'],
                        source_titles=[query_data['target'], "Alternative Source"],
                        baseline_metrics=baseline_metrics
                    )

                # Store result
                all_results[query_data['domain']].append({
                    'query': query_data['query'],
                    'method': method.value,
                    'pawc': metrics['pawc'],
                    'cited': metrics['cited'],
                    'citation_position': metrics['citation_position'],
                    'improvement_pawc': metrics.get('improvements', {}).get('pawc', 0)
                })

                print(f"PAWC: {metrics['pawc']:.2f}, Improvement: {metrics.get('improvements', {}).get('pawc', 0):+.1f}%")

            except Exception as e:
                print(f"Error: {e}")
                continue

    # Generate summary report
    print("\\n" + "=" * 70)
    print("WEEKLY SUMMARY REPORT")
    print("=" * 70)

    # Aggregate by method across all domains
    method_performance = defaultdict(lambda: {'total_pawc': 0, 'count': 0, 'improvements': []})

    for domain, results in all_results.items():
        for result in results:
            method = result['method']
            method_performance[method]['total_pawc'] += result['pawc']
            method_performance[method]['count'] += 1
            if result['improvement_pawc'] != 0:
                method_performance[method]['improvements'].append(result['improvement_pawc'])

    # Calculate averages
    summary_data = []
    for method, data in method_performance.items():
        avg_pawc = data['total_pawc'] / data['count'] if data['count'] > 0 else 0
        avg_improvement = sum(data['improvements']) / len(data['improvements']) if data['improvements'] else 0

        summary_data.append({
            'Method': method,
            'Avg PAWC': round(avg_pawc, 2),
            'Avg Improvement': round(avg_improvement, 1),
            'Tests': data['count']
        })

    # Sort by improvement
    summary_df = pd.DataFrame(summary_data)
    summary_df = summary_df.sort_values('Avg Improvement', ascending=False)

    print("\\n" + summary_df.to_string(index=False))

    # Save detailed results
    output_file = Path("$ANALYSIS_DIR") / f"weekly_analysis_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
    with open(output_file, 'w') as f:
        json.dump({
            'timestamp': datetime.now().isoformat(),
            'week_ending': datetime.now().strftime('%Y-%m-%d'),
            'summary': summary_data,
            'by_domain': dict(all_results)
        }, f, indent=2, default=str)

    # Save CSV
    csv_file = Path("$ANALYSIS_DIR") / f"weekly_summary_{datetime.now().strftime('%Y%m%d')}.csv"
    summary_df.to_csv(csv_file, index=False)

    print(f"\\n✅ Detailed results saved to: {output_file}")
    print(f"✅ CSV summary saved to: {csv_file}")

    # Generate patent evidence if significant results found
    top_methods = summary_df.head(3)
    if (top_methods['Avg Improvement'] > 20).any():
        print("\\n🎯 PATENT EVIDENCE: Significant improvements detected!")
        print("Top performing methods:")
        print(top_methods.to_string(index=False))

    return True

try:
    success = asyncio.run(run_weekly_analysis())
    if success:
        print("\\n✅ Weekly analysis completed successfully")
        sys.exit(0)
    else:
        print("\\n❌ Weekly analysis failed")
        sys.exit(1)
except Exception as e:
    print(f"\\n❌ Error: {e}")
    import traceback
    traceback.print_exc()
    sys.exit(1)
EOF

EXIT_CODE=$?

if [ $EXIT_CODE -eq 0 ]; then
    echo "✅ Weekly analysis completed successfully" | tee -a "$LOG_FILE"
else
    echo "❌ Weekly analysis failed" | tee -a "$LOG_FILE"
fi

echo "========================================" | tee -a "$LOG_FILE"

deactivate
exit $EXIT_CODE
