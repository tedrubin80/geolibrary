#!/usr/bin/env python3
"""
Auto-update Jupyter Notebook with Latest Results

This script updates the results analysis notebook after each Monte Carlo run.
It automatically finds the latest results file and regenerates all visualizations.
"""

import json
import subprocess
from pathlib import Path
from datetime import datetime

def find_latest_results():
    """Find the most recent Monte Carlo results file"""
    results_dir = Path('results')
    json_files = list(results_dir.glob('monte_carlo_*.json'))

    if not json_files:
        print("❌ No results files found!")
        return None

    latest = max(json_files, key=lambda p: p.stat().st_mtime)
    return latest

def update_notebook(results_file):
    """Update notebook with latest results file path"""
    notebook_path = Path('notebooks/02_results_analysis.ipynb')

    with open(notebook_path, 'r') as f:
        notebook = json.load(f)

    # Find the cell that loads results and update it
    for cell in notebook['cells']:
        if 'source' in cell and any('results_file = Path' in line for line in cell['source']):
            # Update the results file path
            new_source = []
            for line in cell['source']:
                if 'results_file = Path' in line:
                    new_source.append(f"results_file = Path('../{results_file}')\n")
                else:
                    new_source.append(line)
            cell['source'] = new_source
            break

    # Save updated notebook
    with open(notebook_path, 'w') as f:
        json.dump(notebook, f, indent=1)

    print(f"✅ Updated notebook with: {results_file}")

def execute_notebook():
    """Execute notebook to generate visualizations"""
    print("📊 Executing notebook to generate visualizations...")

    try:
        subprocess.run([
            'jupyter', 'nbconvert',
            '--to', 'notebook',
            '--execute',
            '--inplace',
            'notebooks/02_results_analysis.ipynb'
        ], check=True, capture_output=True, text=True)

        print("✅ Notebook executed successfully!")
        print("✅ Visualizations saved to results/analysis/")

    except subprocess.CalledProcessError as e:
        print(f"⚠️ Warning: Could not execute notebook automatically")
        print(f"   You can run it manually with: jupyter lab notebooks/02_results_analysis.ipynb")
        print(f"   Error: {e.stderr}")

def main():
    print("=" * 80)
    print("NOTEBOOK UPDATE - Latest Monte Carlo Results")
    print("=" * 80)

    # Find latest results
    results_file = find_latest_results()
    if not results_file:
        return

    print(f"\n📁 Latest results: {results_file}")

    # Load and display summary
    with open(results_file, 'r') as f:
        data = json.load(f)

    print(f"   Timestamp: {data['timestamp']}")
    print(f"   Query: {data['query']['query']}")
    print(f"   Baseline PAWC: {data['baseline_avg_pawc']:.2f}")

    # Update notebook
    update_notebook(results_file)

    # Try to execute (optional, may fail if jupyter not in PATH)
    execute_notebook()

    print("\n" + "=" * 80)
    print("✅ Update complete!")
    print("=" * 80)
    print("\nTo view the notebook:")
    print("  cd notebooks && jupyter lab 02_results_analysis.ipynb")
    print("\nOr convert to HTML:")
    print("  jupyter nbconvert --to html notebooks/02_results_analysis.ipynb")

if __name__ == "__main__":
    main()
