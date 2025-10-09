#!/usr/bin/env python3
"""
Statistical Analysis of Monte Carlo GEO Testing Results

Calculates:
- Confidence intervals (95%)
- T-test for statistical significance
- Effect sizes (Cohen's d)
- Patent-ready evidence report
"""

import json
import sys
from pathlib import Path
from scipy import stats
import math

def load_results(filepath):
    """Load Monte Carlo results from JSON file"""
    with open(filepath, 'r') as f:
        return json.load(f)

def calculate_confidence_interval(data, confidence=0.95):
    """Calculate confidence interval for a dataset"""
    n = len(data)
    if n < 2:
        return (data[0], data[0])

    mean = sum(data) / n
    std_dev = math.sqrt(sum((x - mean) ** 2 for x in data) / (n - 1))
    std_err = std_dev / math.sqrt(n)

    # T-distribution critical value for 95% confidence, n-1 degrees of freedom
    t_critical = stats.t.ppf((1 + confidence) / 2, n - 1)

    margin_of_error = t_critical * std_err

    return (mean - margin_of_error, mean + margin_of_error)

def cohens_d(group1, group2):
    """Calculate Cohen's d effect size"""
    n1, n2 = len(group1), len(group2)
    mean1, mean2 = sum(group1) / n1, sum(group2) / n2

    var1 = sum((x - mean1) ** 2 for x in group1) / (n1 - 1)
    var2 = sum((x - mean2) ** 2 for x in group2) / (n2 - 1)

    pooled_std = math.sqrt(((n1 - 1) * var1 + (n2 - 1) * var2) / (n1 + n2 - 2))

    if pooled_std == 0:
        return 0

    return (mean1 - mean2) / pooled_std

def interpret_effect_size(d):
    """Interpret Cohen's d effect size"""
    abs_d = abs(d)
    if abs_d < 0.2:
        return "negligible"
    elif abs_d < 0.5:
        return "small"
    elif abs_d < 0.8:
        return "medium"
    else:
        return "large"

def analyze_results(results_file):
    """Main analysis function"""
    data = load_results(results_file)

    print("=" * 80)
    print("STATISTICAL ANALYSIS - MONTE CARLO GEO TESTING")
    print("=" * 80)
    print(f"\nTimestamp: {data['timestamp']}")
    print(f"Query: {data['query']['query']}")
    print(f"Domain: {data['query']['domain']}")
    print(f"Target Source: {data['query']['target_source']}")
    print(f"\nBaseline PAWC: {data['baseline_avg_pawc']:.2f}")
    print("\n" + "=" * 80)

    # Get baseline data
    baseline_results = None
    for method_data in data['summary']:
        if method_data['method'] == 'baseline':
            baseline_results = [r['pawc'] for r in method_data['raw_results']]
            break

    # Analyze each method
    results_table = []

    for method_data in data['summary']:
        method = method_data['method']
        if method == 'baseline':
            continue

        # Extract PAWC scores
        method_scores = [r['pawc'] for r in method_data['raw_results']]

        # Calculate confidence interval
        ci_lower, ci_upper = calculate_confidence_interval(method_scores)

        # Perform t-test vs baseline
        t_stat, p_value = stats.ttest_ind(method_scores, baseline_results)

        # Calculate effect size
        effect_size = cohens_d(method_scores, baseline_results)
        effect_interpretation = interpret_effect_size(effect_size)

        # Statistical significance
        is_significant = p_value < 0.05

        results_table.append({
            'method': method,
            'avg_pawc': method_data['avg_pawc'],
            'std_dev': method_data['std_dev'],
            'improvement': method_data['improvement_pct'],
            'ci_lower': ci_lower,
            'ci_upper': ci_upper,
            'p_value': p_value,
            'effect_size': effect_size,
            'effect_interpretation': effect_interpretation,
            'is_significant': is_significant
        })

    # Sort by improvement
    results_table.sort(key=lambda x: x['improvement'], reverse=True)

    # Print results table
    print("\nMETHOD PERFORMANCE WITH STATISTICAL SIGNIFICANCE")
    print("=" * 80)
    print(f"{'Method':<15} {'Avg PAWC':<12} {'Improvement':<12} {'95% CI':<20} {'p-value':<10} {'Significance'}")
    print("-" * 80)

    for r in results_table:
        sig_mark = "***" if r['is_significant'] and r['p_value'] < 0.001 else \
                   "**" if r['is_significant'] and r['p_value'] < 0.01 else \
                   "*" if r['is_significant'] else "NS"

        ci_str = f"[{r['ci_lower']:.2f}, {r['ci_upper']:.2f}]"

        print(f"{r['method']:<15} {r['avg_pawc']:<12.2f} {r['improvement']:>+10.1f}%  {ci_str:<20} {r['p_value']:<10.4f} {sig_mark}")

    print("\nLegend: *** p<0.001, ** p<0.01, * p<0.05, NS = Not Significant")

    # Effect sizes
    print("\n" + "=" * 80)
    print("EFFECT SIZES (Cohen's d)")
    print("=" * 80)
    print(f"{'Method':<15} {'Effect Size':<12} {'Interpretation'}")
    print("-" * 80)

    for r in results_table:
        print(f"{r['method']:<15} {r['effect_size']:>+10.2f}   {r['effect_interpretation']}")

    # Summary recommendations
    print("\n" + "=" * 80)
    print("PATENT EVIDENCE SUMMARY")
    print("=" * 80)

    significant_improvements = [r for r in results_table if r['is_significant'] and r['improvement'] > 0]

    if significant_improvements:
        print("\n✅ STATISTICALLY SIGNIFICANT IMPROVEMENTS:")
        for r in significant_improvements:
            print(f"\n  • {r['method'].upper()}")
            print(f"    - Improvement: {r['improvement']:+.1f}%")
            print(f"    - PAWC: {r['avg_pawc']:.2f} ± {r['std_dev']:.2f}")
            print(f"    - 95% Confidence Interval: [{r['ci_lower']:.2f}, {r['ci_upper']:.2f}]")
            print(f"    - p-value: {r['p_value']:.4f}")
            print(f"    - Effect size: {r['effect_size']:+.2f} ({r['effect_interpretation']})")

    significant_decreases = [r for r in results_table if r['is_significant'] and r['improvement'] < 0]

    if significant_decreases:
        print("\n\n⚠️  STATISTICALLY SIGNIFICANT DECREASES (Avoid these methods):")
        for r in significant_decreases:
            print(f"\n  • {r['method'].upper()}")
            print(f"    - Decrease: {r['improvement']:.1f}%")
            print(f"    - p-value: {r['p_value']:.4f}")

    print("\n" + "=" * 80)
    print(f"\n📊 Total tests performed: {len(data['summary']) * 5}")
    print(f"📈 Methods showing improvement: {len([r for r in results_table if r['improvement'] > 0])}")
    print(f"✅ Statistically significant improvements: {len(significant_improvements)}")
    print(f"⚠️  Statistically significant decreases: {len(significant_decreases)}")

    print("\n" + "=" * 80)
    print("ANALYSIS COMPLETE")
    print("=" * 80)

    return results_table

if __name__ == "__main__":
    results_file = Path("results/monte_carlo_20251009_002155.json")

    if not results_file.exists():
        # Find latest results file
        results_dir = Path("results")
        json_files = list(results_dir.glob("monte_carlo_*.json"))
        if json_files:
            results_file = max(json_files, key=lambda p: p.stat().st_mtime)
            print(f"Using latest results file: {results_file}")
        else:
            print("No results files found!")
            sys.exit(1)

    analyze_results(results_file)
