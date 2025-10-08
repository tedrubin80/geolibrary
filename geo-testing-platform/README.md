# GEO Testing Platform

**Domain**: geooptimizer.dev

Automated testing platform for validating Generative Engine Optimization (GEO) methods using local LLMs via Ollama.

## 🎯 Purpose

This platform validates the effectiveness of different GEO methods (Statistics Addition, Citation Addition, Quotations, etc.) for optimizing content visibility in AI-generated responses. Research findings will support patent applications and demonstrate commercial viability.

## 🏗️ Architecture

```
geo-testing-platform/
├── backend/
│   ├── models/          # SQLAlchemy database models
│   ├── services/        # Core services (Ollama, GEO methods, metrics)
│   ├── api/             # FastAPI endpoints
│   ├── utils/           # Helper functions
│   └── database.py      # Database configuration
├── notebooks/           # Jupyter notebooks for research
├── data/                # Test queries and content
├── results/             # Experiment results (for patent submission)
└── cron_jobs/           # Automated testing scripts
```

## 📊 Key Features

### GEO Methods Tested (9 total)
1. **Baseline** - Control group (no optimization)
2. **Statistics Addition** - Add numerical data
3. **Citation Addition** - Reference authoritative sources
4. **Authoritative Tone** - Write with expert confidence
5. **Quotation Addition** - Include expert quotes
6. **Fluency Optimization** - Improve readability
7. **Technical Terms** - Use domain-specific terminology
8. **Easy-to-Understand** - Simplify for general audience
9. **Keyword Optimization** - Traditional SEO (for comparison)
10. **Unique Words** - Use distinctive vocabulary

### Metrics Calculated
- **PAWC** (Position-Adjusted Word Count) - PRIMARY METRIC
  - Formula: `Σ (word_count_i × (1 / sqrt(position_i)))`
- **WC** (Word Count) - Total words citing our content
- **PS** (Position Score) - `1 / position` of first citation
- **Citation Frequency** - Number of times cited
- **Improvement %** - Percentage improvement vs baseline

## 🚀 Quick Start

### 1. Install Dependencies

```bash
# Create virtual environment
python3 -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate

# Install requirements
pip install -r requirements.txt

# Install Jupyter
pip install jupyter jupyterlab ipykernel
```

### 2. Initialize Database

```bash
cd backend
python database.py
```

### 3. Check Ollama

```bash
# Verify Ollama is running
ollama list

# Pull required models if needed
ollama pull mistral:7b-instruct
ollama pull gemma2:9b
```

### 4. Run Jupyter Notebooks

```bash
jupyter lab
# Open notebooks/01_initial_testing.ipynb
```

## 📓 Jupyter Notebooks

### `notebooks/01_initial_testing.ipynb`
- Test Ollama connection
- Run single GEO method transformation
- Calculate metrics for one query

### `notebooks/02_full_experiment.ipynb`
- Run complete experiments across all methods
- Test multiple queries
- Generate statistical analysis
- Export results for patent documentation

### `notebooks/03_analysis.ipynb`
- Analyze aggregated results
- Generate visualizations
- Calculate statistical significance
- Create patent evidence reports

## 🔬 Running Experiments

### Manual Experiment (Jupyter)

```python
from services.ollama_client import OllamaClient
from services.geo_methods import GEOMethod, get_transformation_prompt
from services.metrics_calculator import GEOMetricsCalculator

# Initialize
client = OllamaClient()
calc = GEOMetricsCalculator()

# Transform content
prompt_data = get_transformation_prompt(
    GEOMethod.STATISTICS,
    baseline_content
)

result = await client.generate(
    prompt=prompt_data['prompt'],
    system=prompt_data['system']
)

# Calculate metrics
metrics = calc.calculate_all_metrics(
    response_text=generated_response,
    target_source="Your Content Title",
    source_titles=["Your Content", "Competitor 1", "Competitor 2"]
)

print(calc.format_metrics_report(metrics))
```

### Automated Experiments (Cron)

```bash
# Run daily experiments
./cron_jobs/run_daily_tests.sh

# Run weekly full analysis
./cron_jobs/run_weekly_analysis.sh
```

## 📁 Data Structure

### Query Format (data/queries.json)
```json
{
  "queries": [
    {
      "text": "What are the best Italian restaurants in NYC?",
      "domain": "food",
      "intent": "informational",
      "difficulty": "moderate",
      "competing_sources": [
        "Joe's Pizza - NYC Institution",
        "Carbone - Fine Dining",
        "L'Artusi - Modern Italian"
      ]
    }
  ]
}
```

### Content Format (data/content/)
```
data/content/
├── query_001_baseline.txt
├── query_001_statistics.txt
├── query_001_citation.txt
└── ...
```

### Results Format (results/)
```
results/
├── experiments/
│   ├── exp_001_20251008.json      # Raw results
│   └── exp_001_20251008_summary.json
├── analysis/
│   ├── statistical_analysis.json
│   └── method_comparison.csv
└── patent_evidence/
    ├── best_examples/
    ├── improvement_charts/
    └── statistical_proof.pdf
```

## 🤖 Ollama Models

### Tier 1: Bulk Testing
- **mistral:7b-instruct** (4.4GB)
- Fast, cost-effective
- Use for: Initial testing, content transformation, high-volume experiments

### Tier 2: Validation
- **gemma2:9b** (5.4GB)
- Higher quality, slower
- Use for: Validating top-performing methods

### Tier 3: Quality Check
- **llama3.2:3b** (2.0GB)
- Quick validation
- Use for: Spot-checking results

## 📈 Expected Results (Based on KDD '24 Paper)

| Method | Expected Improvement |
|--------|---------------------|
| Statistics Addition | +35% PAWC |
| Citations | +40% PAWC |
| Quotations | +38% PAWC |
| Authoritative Tone | +28% PAWC |
| Technical Terms | +25% PAWC |
| Fluency | +15% PAWC |
| Keyword Stuffing | -10 to -17% PAWC ⚠️ |

**Domain-Specific Performance:**
- Legal: Statistics work best (+73%)
- People & Society: Quotations work best (+72%)
- Technology: Technical Terms work best (+68%)

## 🔐 Environment Variables

```bash
# Copy example environment file
cp .env.example .env

# Edit with your settings
# DATABASE_URL=sqlite:///./geo_testing.db
# OLLAMA_BASE_URL=http://localhost:11434
# OLLAMA_DEFAULT_MODEL=mistral:7b-instruct
```

## 📊 Cron Jobs

### Setup Cron Jobs

```bash
# Make scripts executable
chmod +x cron_jobs/*.sh

# Add to crontab
crontab -e

# Add these lines:
# Run tests daily at 2 AM
0 2 * * * /var/www/geolibrary/geo-testing-platform/cron_jobs/run_daily_tests.sh

# Run full analysis weekly (Sunday 3 AM)
0 3 * * 0 /var/www/geolibrary/geo-testing-platform/cron_jobs/run_weekly_analysis.sh
```

## 🧪 Testing

```bash
cd backend
pytest tests/
```

## 📄 Patent Documentation

### What to Submit
1. **Experimental Evidence** (`results/patent_evidence/`)
   - Raw test data showing method effectiveness
   - Statistical analysis proving significance
   - Before/after examples

2. **Methodology** (`notebooks/` + `backend/services/`)
   - GEO method implementations
   - Metrics calculation formulas
   - Experiment design

3. **Results Summary**
   - Aggregated performance data
   - Domain-specific findings
   - Commercial viability proof

## 💡 Cost Analysis

### Using Ollama (Local)
- **Hardware**: Requires GPU with 8GB+ VRAM (or CPU with patience)
- **Cost**: $0 per API call
- **Speed**: ~10-30 seconds per generation (depending on model/hardware)
- **Privacy**: All data stays local

### Estimated Test Volume
- 50 queries × 9 methods × 10 iterations = 4,500 tests
- Time: ~12-36 hours total (can run overnight)
- Cost: **$0** (vs $500-1000 with cloud APIs)

## 🎯 Success Metrics

✅ Platform successfully runs 4,500+ tests
✅ At least 3 methods show >20% PAWC improvement
✅ Statistical significance (p < 0.05) achieved
✅ Domain-specific patterns identified
✅ Patent-ready documentation generated

## 📚 References

- **KDD '24 Paper**: "GEO: Generative Engine Optimization"
- **GEO-bench Dataset**: https://github.com/GEO-optim/GEO
- **Ollama Documentation**: https://ollama.ai/

## 🤝 Contributing

This is a research platform for patent validation. Not currently accepting external contributions.

## 📝 License

Proprietary - All rights reserved for patent filing.

---

**Project**: GEO Testing Platform
**Domain**: geooptimizer.dev
**Status**: Active Research
**Last Updated**: October 8, 2025
