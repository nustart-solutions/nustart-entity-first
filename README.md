# Entity-First SEO Toolkit for NuStart Solutions

A comprehensive toolkit for implementing and measuring entity-first SEO strategies, built on a 3-layer architecture (Directives → Orchestration → Execution).

## What is Entity-First SEO?

Entity-first SEO is a modern optimization strategy that prioritizes establishing semantic relationships between entities (people, places, organizations, concepts) rather than focusing solely on keywords. This approach aligns with Google's Knowledge Graph and semantic search algorithms.

**Read the full explanation:** [`directives/entity_seo_explanation.md`](directives/entity_seo_explanation.md)

## Quick Start

### 1. Installation

```powershell
# Install Python dependencies
pip install -r requirements.txt

# Set up Google Natural Language API key
# Create .env file and add:
# GOOGLE_NLP_API_KEY=your_api_key_here
```

### 2. Measure Current Entity Score

```powershell
# Generate comprehensive entity report for a page
python execution/generate_entity_report.py --url https://nustart.solutions --target "NuStart Solutions"
```

This will produce:
- Composite entity score (0-100)
- Entity salience analysis
- Schema markup audit
- Content structure analysis
- Prioritized recommendations

### 3. Review Results

Open the generated HTML report in `.tmp/entity_report_[timestamp].html`

### 4. Implement Improvements

Follow the directive: [`directives/improve_entity_score.md`](directives/improve_entity_score.md)

## Architecture

This toolkit follows a 3-layer architecture:

### Layer 1: Directives (What to do)
SOPs written in Markdown that define goals, processes, and strategies:

- **[`entity_seo_explanation.md`](directives/entity_seo_explanation.md)** - Comprehensive guide to entity-first SEO
- **[`measure_entity_score.md`](directives/measure_entity_score.md)** - How to measure entity optimization
- **[`improve_entity_score.md`](directives/improve_entity_score.md)** - Strategies to improve scores

### Layer 2: Orchestration (Decision making)
You (or AI agent) read directives, call execution tools, handle errors, and make decisions.

### Layer 3: Execution (Doing the work)
Deterministic Python scripts that handle the actual work:

- **`measure_entity_salience.py`** - Extract entities using Google NLP API
- **`audit_schema_markup.py`** - Analyze structured data completeness
- **`analyze_page_entities.py`** - Check entity placement in content
- **`generate_entity_report.py`** - Comprehensive analysis with scoring

## Usage Examples

### Measure Entity Salience
```powershell
python execution/measure_entity_salience.py --url https://nustart.solutions --target-entity "NuStart Solutions"
```

### Audit Schema Markup
```powershell
python execution/audit_schema_markup.py --url https://nustart.solutions --validate
```

### Analyze Page Structure
```powershell
python execution/analyze_page_entities.py --url https://nustart.solutions --entities "NuStart Solutions" "SEO" --audit-links
```

### Generate Full Report
```powershell
# Single page analysis
python execution/generate_entity_report.py --url https://nustart.solutions --target "NuStart Solutions" --output-format html

# Multiple pages
python execution/generate_entity_report.py --url-file .tmp/urls.txt --target "NuStart Solutions"

# Competitive analysis
python execution/generate_entity_report.py --url https://nustart.solutions --compare https://competitor.com --target "SEO Services"
```

## Composite Entity Score

The toolkit calculates a weighted composite score:

```
Entity Score = (
    Entity Salience × 30% +
    Schema Completeness × 30% +
    Content Coverage × 25% +
    Link Quality × 15%
) × 100
```

**Score Ratings:**
- **90-100**: Excellent entity optimization
- **70-89**: Good - minor improvements needed
- **50-69**: Fair - significant optimization potential
- **30-49**: Poor - major work required
- **0-29**: Very Poor - complete overhaul needed

## Implementation for NuStart Solutions

### Phase 1: Baseline Measurement (Week 1)
1. Run entity reports on priority pages
2. Document current scores
3. Identify quick wins

### Phase 2: Quick Wins (Week 1-2)
1. Implement core schema markup (Organization, Article)
2. Optimize title tags and H1s
3. Re-measure scores

### Phase 3: Content Optimization (Weeks 3-4)
1. Rewrite first paragraphs
2. Add entity context sections
3. Improve internal link anchors

### Phase 4: Authority Building (Ongoing)
1. Build entity profiles (Wikipedia, Wikidata, directories)
2. Earn citations and mentions
3. Create content clusters

## Directory Structure

```
entity-first-seo/
├── directives/           # Layer 1: SOPs and strategy guides
│   ├── entity_seo_explanation.md
│   ├── measure_entity_score.md
│   └── improve_entity_score.md
├── execution/            # Layer 3: Python scripts
│   ├── measure_entity_salience.py
│   ├── audit_schema_markup.py
│   ├── analyze_page_entities.py
│   └── generate_entity_report.py
├── .tmp/                 # Intermediate files (gitignored)
├── .env                  # API keys (gitignored)
├── requirements.txt      # Python dependencies
└── README.md            # This file
```

## API Requirements

### Google Natural Language API
Used for entity extraction and salience scoring.

**Setup:**
1. Create Google Cloud account
2. Enable Natural Language API
3. Create API key
4. Add to `.env`: `GOOGLE_NLP_API_KEY=your_key`

**Pricing:**
- Free tier: 5,000 text records/month
- Paid: $1.00 per 1,000 text records

**Typical usage:**
- Single page: 1 API call
- Full site audit (50 pages): 50 calls (~$0.05)

## Tools & Resources

### Validation Tools
- [Google Rich Results Test](https://search.google.com/test/rich-results)
- [Schema.org Validator](https://validator.schema.org/)
- [Google NLP API Demo](https://cloud.google.com/natural-language)

### Reference
- [Schema.org Documentation](https://schema.org/)
- [Google Search Central: Structured Data](https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data)
- [Google Knowledge Graph](https://developers.google.com/knowledge-graph)

## Troubleshooting

### "API key not found"
- Ensure `.env` file exists with `GOOGLE_NLP_API_KEY=your_key`
- Check `.env` is in project root

### "No entities detected"
- Content may be too short (need 20+ words)
- Content lacks specific, identifiable concepts
- Add more entity-rich descriptive content

### Script import errors
- Run `pip install -r requirements.txt`
- Ensure Python 3.8+ is installed

## Contributing

This toolkit follows the self-annealing principle: when something breaks or can be improved, update the tool AND the directive to capture the learning.

## License

Proprietary - NuStart Solutions

---

**Next Steps:**
1. Read [`directives/entity_seo_explanation.md`](directives/entity_seo_explanation.md) for concepts
2. Run `python execution/generate_entity_report.py --url [YOUR_URL]` for baseline
3. Follow [`directives/improve_entity_score.md`](directives/improve_entity_score.md) for optimization
