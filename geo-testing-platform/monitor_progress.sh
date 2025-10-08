#!/bin/bash

# Monitor Monte Carlo progress

echo "=================================="
echo "Monte Carlo Test Progress Monitor"
echo "=================================="
echo ""

# Check if process is running
if pgrep -f "run_monte_carlo.py" > /dev/null; then
    echo "✅ Test is running"
    echo ""

    # Show latest results file
    LATEST_RESULT=$(ls -t results/monte_carlo_*.json 2>/dev/null | head -1)
    if [ -n "$LATEST_RESULT" ]; then
        echo "Latest result file: $LATEST_RESULT"
    fi

    # Check if log exists
    LATEST_LOG=$(ls -t logs/monte_carlo_*.log 2>/dev/null | head -1)
    if [ -n "$LATEST_LOG" ]; then
        echo "Latest log: $LATEST_LOG"
        echo ""
        echo "Last 20 lines:"
        echo "----------------------------------"
        tail -20 "$LATEST_LOG"
    fi
else
    echo "❌ No Monte Carlo test is currently running"
    echo ""

    # Show completed results
    RESULT_COUNT=$(ls results/monte_carlo_*.json 2>/dev/null | wc -l)
    echo "Completed Monte Carlo runs: $RESULT_COUNT"

    if [ $RESULT_COUNT -gt 0 ]; then
        echo ""
        echo "Most recent result:"
        LATEST=$(ls -t results/monte_carlo_*.json | head -1)
        echo "  File: $LATEST"
        echo "  Timestamp: $(stat -c %y "$LATEST" 2>/dev/null || stat -f "%Sm" "$LATEST")"
    fi
fi

echo ""
echo "=================================="
echo "To watch in real-time:"
echo "  tail -f logs/monte_carlo_*.log"
echo "=================================="
