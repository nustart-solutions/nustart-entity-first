# Measuring Entity Scores: Directive

## Goal
Assess the current "entity score" of a webpage to understand how well it's optimized for entity-first SEO. This measurement provides a baseline and identifies specific improvement areas.

## What We Measure

### 1. Entity Salience Scores
**Definition**: How prominently specific entities feature in the content (0.0 to 1.0 scale)

**Metrics**:
- Primary entity salience (should be > 0.5)
- Secondary entity salience distribution
- Number of distinct entities detected
- Entity type diversity (Person, Organization, Location, Product, etc.)

**Interpretation**:
- **0.8-1.0**: Dominant entity, very prominent
- **0.5-0.8**: Strong entity presence
- **0.3-0.5**: Moderate entity presence
- **0.0-0.3**: Weak entity presence

### 2. Schema Markup Completeness
**Metrics**:
- Schema types present (Organization, Article, Person, etc.)
- Required fields populated (name, description, url, etc.)
- Nested entity relationships (author → Person, publisher → Organization)
- Schema validation errors/warnings

**Scoring**:
- 100%: All relevant schema types with complete properties
- 75-99%: Most schema present but missing some properties
- 50-74%: Basic schema but significant gaps
- 0-49%: Minimal or no schema markup

### 3. Content Entity Coverage
**Metrics**:
- Entity mentions in strategic locations (title, H1, first paragraph, headings)
- Entity context depth (how much content describes the entity)
- Co-occurring entities (related entities mentioned together)
- Entity-based internal links (anchor text referencing entities)

### 4. External Entity Signals
**Metrics**:
- NAP consistency (Name, Address, Phone across web)
- Entity profiles (Wikipedia, Wikidata, directories)
- Citations and mentions on other sites
- SameAs links in schema (social profiles, other official sources)

### 5. Composite Entity Score
A weighted score combining all metrics:

```
Entity Score = (
    Entity Salience × 30% +
    Schema Completeness × 30% +
    Content Coverage × 25% +
    External Signals × 15%
) × 100
```

**Rating Scale**:
- **90-100**: Excellent entity optimization
- **70-89**: Good entity presence, minor improvements needed
- **50-69**: Moderate entity signals, significant optimization potential
- **30-49**: Weak entity foundation, needs major work
- **0-29**: Poor entity optimization, complete overhaul required

## Tools & Scripts

### Primary Measurement Tools

#### 1. `execution/measure_entity_salience.py`
**Purpose**: Extract entities and calculate salience scores using Google's Natural Language API

**Inputs**:
- Page URL or raw HTML/text content
- (Optional) Target entity to focus on

**Outputs** (saved to `.tmp/`):
- JSON file with all detected entities and salience scores
- CSV summary of top entities
- Entity type distribution chart

**Usage**:
```bash
python execution/measure_entity_salience.py --url https://nustart.solutions/page
python execution/measure_entity_salience.py --text "content here" --target-entity "NuStart Solutions"
```

**API Requirements**:
- Google Cloud account
- Natural Language API enabled
- API key in `.env` file: `GOOGLE_NLP_API_KEY=your_key_here`

#### 2. `execution/audit_schema_markup.py`
**Purpose**: Analyze existing schema markup and identify gaps

**Inputs**:
- Page URL

**Outputs** (saved to `.tmp/`):
- JSON file with all detected schema
- Validation report (errors, warnings)
- Completeness score by schema type
- Recommendations for missing entity markup

**Usage**:
```bash
python execution/audit_schema_markup.py --url https://nustart.solutions/page
```

**Dependencies**:
- `extruct` library (extracts structured data)
- `requests` library
- `beautifulsoup4`

#### 3. `execution/analyze_page_entities.py`
**Purpose**: Comprehensive entity analysis combining content structure and HTML placement

**Inputs**:
- Page URL

**Outputs** (saved to `.tmp/`):
- Entity placement report (where entities appear: title, H1, paragraphs, etc.)
- Internal link anchor text analysis
- Entity co-occurrence matrix
- Strategic positioning score

**Usage**:
```bash
python execution/analyze_page_entities.py --url https://nustart.solutions/page
```

#### 4. `execution/generate_entity_report.py`
**Purpose**: Master script that orchestrates all measurement tools and generates a unified report

**Inputs**:
- Page URL
- (Optional) Target entity name
- (Optional) Competitor URLs for comparison

**Outputs** (saved to `.tmp/` and Google Sheets):
- Comprehensive HTML report with all metrics
- Composite entity score
- Prioritized recommendations
- (Optional) Google Sheet with results for easy sharing

**Usage**:
```bash
python execution/generate_entity_report.py --url https://nustart.solutions/page --target "NuStart Solutions"
python execution/generate_entity_report.py --url https://nustart.solutions/page --compare https://competitor.com/page
```

### Secondary Tools

#### Google Natural Language API Demo
- **URL**: https://cloud.google.com/natural-language
- **Purpose**: Quick manual entity extraction and salience checking
- **Usage**: Paste content, view entity analysis immediately
- **Limitation**: Manual process, not scalable

#### Google Rich Results Test
- **URL**: https://search.google.com/test/rich-results
- **Purpose**: Validate schema markup and preview rich snippets
- **Usage**: Enter URL, review detected structured data
- **Note**: Only shows schema that triggers rich results, not all markup

#### Schema Markup Validator
- **URL**: https://validator.schema.org/
- **Purpose**: Validate JSON-LD, Microdata, RDFa markup
- **Usage**: Paste markup or URL, check for errors
- **Benefit**: Comprehensive validation beyond Google's tool

## Measurement Workflow

### Single Page Analysis

**Step 1: Run Baseline Measurement**
```bash
# Navigate to project directory
cd c:\Users\Anne\Documents\entity-first-seo\entity-first-seo

# Run comprehensive entity report
python execution/generate_entity_report.py \
  --url https://nustart.solutions/target-page \
  --target "NuStart Solutions" \
  --output-format html
```

**Step 2: Review Generated Report**
Open `.tmp/entity_report_[timestamp].html` in browser

**Key sections to review**:
1. **Executive Summary**: Composite score and overall rating
2. **Entity Salience Analysis**: What entities Google detects and their prominence
3. **Schema Audit**: What's present and what's missing
4. **Content Positioning**: Where entities appear in content structure
5. **Recommendations**: Prioritized list of improvements

**Step 3: Export to Google Sheets (Optional)**
```bash
# Re-run with Google Sheets export
python execution/generate_entity_report.py \
  --url https://nustart.solutions/target-page \
  --target "NuStart Solutions" \
  --export-sheets
```

Requires:
- `credentials.json` and `token.json` for Google Sheets API
- Sheet will be created in your Google Drive

### Multi-Page/Site-Wide Audit

**Use Case**: Audit multiple pages or entire sections of the site

**Step 1: Create URL List**
Create `.tmp/urls_to_audit.txt` with one URL per line:
```
https://nustart.solutions/
https://nustart.solutions/services
https://nustart.solutions/about
https://nustart.solutions/blog/entity-seo
```

**Step 2: Run Batch Analysis**
```bash
python execution/generate_entity_report.py \
  --url-file .tmp/urls_to_audit.txt \
  --target "NuStart Solutions" \
  --export-sheets \
  --sheet-name "NuStart Entity Audit"
```

**Output**: Single Google Sheet with one row per URL, all metrics in columns

### Competitive Analysis

**Use Case**: Compare entity optimization against competitors

**Step 1: Run Comparative Report**
```bash
python execution/generate_entity_report.py \
  --url https://nustart.solutions/page \
  --compare https://competitor1.com/page https://competitor2.com/page \
  --target "SEO Services" \
  --export-sheets \
  --sheet-name "Competitive Entity Analysis"
```

**Output**:
- Side-by-side comparison in Google Sheets
- Entity gap analysis (entities they have that you don't)
- Schema completeness comparison
- Salience score benchmarking

## Interpreting Results

### High Salience but Low Schema Score
**Diagnosis**: Content mentions entities well but doesn't explicitly declare them to search engines

**Action**: Implement schema markup for detected entities (see `directives/improve_entity_score.md`)

### Good Schema but Low Salience
**Diagnosis**: Schema declares entities but content doesn't emphasize them enough

**Action**: Rewrite content to prominently feature primary entity (see improvement directive)

### Low Across All Metrics
**Diagnosis**: Page isn't optimized for entity-first SEO at all

**Action**: Complete entity-first rewrite following `directives/entity_seo_explanation.md`

### High Content Coverage but No External Signals
**Diagnosis**: On-page optimization good but entity not recognized elsewhere

**Action**: Build external entity presence (Wikipedia, directories, citations)

## Frequency of Measurement

### Initial Baseline
- Measure all priority pages before any optimization work
- Establish benchmarks for comparison

### During Optimization
- Measure weekly as changes are implemented
- Track improvement trajectory

### Ongoing Monitoring
- Monthly measurement for priority pages
- Quarterly for full site audit
- After any significant content changes

## Data Storage

### Intermediate Files (`.tmp/`)
All raw outputs stored temporarily:
- Entity extraction JSONs
- Schema audit reports
- HTML reports

**Retention**: Can be deleted and regenerated; keep for reference during active optimization

### Deliverables (Google Sheets)
Permanent records of entity scores:
- Timestamped measurements for historical tracking
- Shareable with stakeholders
- Foundation for performance dashboards

**Location**: Google Drive (link in generated reports)

## Cost Considerations

### Google Natural Language API Pricing
- **Free tier**: 5,000 text records/month
- **Paid**: $1.00 per 1,000 text records (entity extraction)

**Budget Planning**:
- Single page analysis: 1 API call
- Site-wide audit (50 pages): 50 API calls (~$0.05)
- Monthly monitoring (20 pages): 20 API calls/month (~$0.02/month)

**Optimization**:
- Cache results in `.tmp/` to avoid duplicate API calls
- Use batch processing for efficiency
- Stay within free tier for most use cases

## Troubleshooting

### "API key not found" error
**Solution**: Ensure `.env` file exists with `GOOGLE_NLP_API_KEY=your_key`

### "No entities detected"
**Possible causes**:
- Content too short (need 20+ words)
- Content too generic (no specific, identifiable concepts)
- Content is heavily list-based without context

**Solution**: Add more descriptive, entity-rich content

### Schema validation errors
**Common issues**:
- Missing required properties (e.g., Organization without `name`)
- Invalid URLs or email formats
- Incorrect data types (string vs. number)

**Solution**: Use Schema.org documentation to fix properties

### Low salience for target entity
**Possible causes**:
- Entity mentioned but not emphasized
- Too many competing entities
- Target entity not in early/prominent content positions

**Solution**: See `directives/improve_entity_score.md` for optimization tactics

## Next Steps

After measuring entity scores:

1. **Document baseline**: Save initial reports as reference
2. **Prioritize pages**: Focus on high-traffic pages first
3. **Follow improvement directive**: Use `directives/improve_entity_score.md` to optimize
4. **Re-measure**: Track improvement after changes
5. **Iterate**: Continuous optimization cycle

For specific improvement tactics, refer to `directives/improve_entity_score.md`.
