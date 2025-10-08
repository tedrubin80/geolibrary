#!/bin/bash

# GEO Testing Platform - Quick Setup Script

echo "=========================================="
echo "GEO Testing Platform Setup"
echo "=========================================="

# Check if Python 3.11+ is installed
echo "Checking Python version..."
if ! command -v python3 &> /dev/null; then
    echo "❌ Python 3 is not installed"
    exit 1
fi

PYTHON_VERSION=$(python3 --version | cut -d' ' -f2)
echo "✅ Python $PYTHON_VERSION found"

# Check if Ollama is installed
echo "Checking Ollama..."
if ! command -v ollama &> /dev/null; then
    echo "❌ Ollama is not installed"
    echo "Install from: https://ollama.ai/"
    exit 1
fi

ollama --version
echo "✅ Ollama found"

# Check if Ollama is running
if ! curl -s http://localhost:11434/api/tags > /dev/null; then
    echo "⚠️  Ollama is not running. Start it with: ollama serve"
    exit 1
fi
echo "✅ Ollama is running"

# List available models
echo ""
echo "Available Ollama models:"
ollama list

# Create virtual environment
echo ""
echo "Creating Python virtual environment..."
python3 -m venv venv

# Verify venv was created
if [ ! -f venv/bin/python ]; then
    echo "❌ Virtual environment creation failed"
    exit 1
fi
echo "✅ Virtual environment created"

# Install dependencies (use venv's pip directly)
echo ""
echo "Installing Python dependencies..."
./venv/bin/pip install --upgrade pip
./venv/bin/pip install -r requirements.txt
./venv/bin/pip install jupyter jupyterlab ipykernel pandas matplotlib seaborn scipy

echo "✅ Dependencies installed"

# Create .env file
if [ ! -f .env ]; then
    echo ""
    echo "Creating .env file..."
    cp .env.example .env
    echo "✅ .env file created (please review and update if needed)"
fi

# Create necessary directories
echo ""
echo "Creating directories..."
mkdir -p data results logs
mkdir -p results/analysis
mkdir -p results/patent_evidence

# Initialize database
echo ""
echo "Initializing database..."
./venv/bin/python backend/database.py

# Test Ollama connection
echo ""
echo "Testing Ollama connection..."
./venv/bin/python << EOF
import asyncio
from backend.services.ollama_client import OllamaClient

async def test():
    client = OllamaClient()
    healthy = await client.check_health()
    if healthy:
        print("✅ Ollama connection successful")
        models = await client.list_models()
        print(f"Available models: {', '.join(models)}")
    else:
        print("❌ Ollama connection failed")
        return False
    return True

success = asyncio.run(test())
exit(0 if success else 1)
EOF

if [ $? -eq 0 ]; then
    echo ""
    echo "=========================================="
    echo "✅ Setup Complete!"
    echo "=========================================="
    echo ""
    echo "Next steps:"
    echo "1. Activate virtual environment: source venv/bin/activate"
    echo "2. Review .env file for configuration"
    echo "3. Run Jupyter Lab: jupyter lab"
    echo "4. Open: notebooks/01_initial_testing.ipynb"
    echo "5. Or run cron test: ./cron_jobs/run_daily_tests.sh"
    echo ""
    echo "Happy testing!"
else
    echo ""
    echo "❌ Setup failed - please check errors above"
    exit 1
fi
