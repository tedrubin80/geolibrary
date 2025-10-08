#!/bin/bash

# GEO Testing Platform - Daily Automated Tests
# Runs daily experiments and saves results

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
BACKEND_DIR="$PROJECT_DIR/backend"
RESULTS_DIR="$PROJECT_DIR/results"
LOG_DIR="$PROJECT_DIR/logs"
VENV_DIR="$PROJECT_DIR/venv"

# Create directories if they don't exist
mkdir -p "$RESULTS_DIR" "$LOG_DIR"

# Timestamp
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
LOG_FILE="$LOG_DIR/daily_test_$TIMESTAMP.log"

echo "========================================" | tee -a "$LOG_FILE"
echo "GEO Daily Tests - $(date)" | tee -a "$LOG_FILE"
echo "========================================" | tee -a "$LOG_FILE"

# Activate virtual environment
if [ -d "$VENV_DIR" ]; then
    source "$VENV_DIR/bin/activate"
    echo "✅ Virtual environment activated" | tee -a "$LOG_FILE"
else
    echo "❌ Virtual environment not found at $VENV_DIR" | tee -a "$LOG_FILE"
    exit 1
fi

# Check if Ollama is running
echo "Checking Ollama status..." | tee -a "$LOG_FILE"
if ! curl -s http://localhost:11434/api/tags > /dev/null; then
    echo "❌ Ollama is not running. Starting Ollama..." | tee -a "$LOG_FILE"
    # Try to start Ollama (adjust based on your setup)
    ollama serve &
    sleep 5
fi

# Run Python test script
echo "Running daily tests..." | tee -a "$LOG_FILE"
cd "$BACKEND_DIR"

python3 << EOF | tee -a "$LOG_FILE"
import asyncio
import sys
import json
from datetime import datetime
from pathlib import Path

sys.path.insert(0, '$BACKEND_DIR')

from services.ollama_client import OllamaClient
from services.geo_methods import GEOMethod, get_transformation_prompt
from services.metrics_calculator import GEOMetricsCalculator

async def run_daily_test():
    print("Initializing clients...")
    ollama = OllamaClient()
    calc = GEOMetricsCalculator()

    # Check health
    if not await ollama.check_health():
        print("❌ Ollama is not healthy")
        return False

    print("✅ Ollama is healthy")

    # Sample test query
    test_query = "What are the best practices for website optimization?"
    baseline_content = """
    Website optimization improves site performance and user experience.
    Key areas include page speed, mobile responsiveness, and content quality.
    Regular testing and updates are important for maintaining performance.
    """

    target_source = "Web Optimization Guide"
    sources = ["Web Optimization Guide", "Google PageSpeed", "GTmetrix"]

    results = {}
    baseline_metrics = None

    # Test 3 methods (quick daily check)
    test_methods = [GEOMethod.BASELINE, GEOMethod.STATISTICS, GEOMethod.CITATION]

    for method in test_methods:
        print(f"\\nTesting {method.value}...")

        # Transform content
        prompt_data = get_transformation_prompt(method, baseline_content)
        result = await ollama.generate(
            prompt=prompt_data['prompt'],
            system=prompt_data['system'],
            temperature=0.7
        )

        optimized_content = result['text']

        # Generate AI response
        ge_prompt = f"""
Query: {test_query}

Source: {target_source}
{optimized_content}

Please answer the query citing the source.
"""

        response = await ollama.generate(
            prompt=ge_prompt,
            system="You are a helpful AI assistant. Cite your sources.",
            temperature=0.7
        )

        # Calculate metrics
        if method == GEOMethod.BASELINE:
            baseline_metrics = calc.calculate_all_metrics(
                response_text=response['text'],
                target_source=target_source,
                source_titles=sources
            )
            results[method.value] = baseline_metrics
        else:
            metrics = calc.calculate_all_metrics(
                response_text=response['text'],
                target_source=target_source,
                source_titles=sources,
                baseline_metrics=baseline_metrics
            )
            results[method.value] = metrics

            # Print improvement
            if 'improvements' in metrics:
                print(f"  PAWC improvement: {metrics['improvements'].get('pawc', 0):+.1f}%")

    # Save results
    output_file = Path("$RESULTS_DIR") / f"daily_test_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
    with open(output_file, 'w') as f:
        json.dump({
            'timestamp': datetime.now().isoformat(),
            'query': test_query,
            'results': results
        }, f, indent=2, default=str)

    print(f"\\n✅ Results saved to: {output_file}")
    return True

# Run the test
try:
    success = asyncio.run(run_daily_test())
    if success:
        print("\\n✅ Daily tests completed successfully")
        sys.exit(0)
    else:
        print("\\n❌ Daily tests failed")
        sys.exit(1)
except Exception as e:
    print(f"\\n❌ Error: {e}")
    import traceback
    traceback.print_exc()
    sys.exit(1)
EOF

EXIT_CODE=$?

if [ $EXIT_CODE -eq 0 ]; then
    echo "✅ Daily tests completed successfully" | tee -a "$LOG_FILE"
else
    echo "❌ Daily tests failed with exit code $EXIT_CODE" | tee -a "$LOG_FILE"
fi

echo "========================================" | tee -a "$LOG_FILE"
echo "Log saved to: $LOG_FILE" | tee -a "$LOG_FILE"
echo "========================================" | tee -a "$LOG_FILE"

# Deactivate virtual environment
deactivate

exit $EXIT_CODE
