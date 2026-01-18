# Improving Entity Scores: Directive

## Goal
Systematically improve entity optimization based on measurement results. This directive provides actionable tactics to increase entity salience, schema completeness, and overall entity-first SEO performance.

## Improvement Framework

Entity score improvement follows a prioritized approach:

1. **Quick Wins** (Week 1): Schema markup and structural fixes
2. **Content Optimization** (Weeks 2-4): Rewrite/enhance existing content
3. **Authority Building** (Ongoing): External entity signals
4. **Advanced Tactics** (Month 2+): Entity relationships and advanced schema

## Quick Wins: Schema & Technical (Week 1)

### Strategy 1: Implement Core Schema Markup

**Goal**: Explicitly declare entities to search engines using structured data

#### For Organization Entity (Homepage)

**Minimum Viable Schema**:
```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "NuStart Solutions",
  "url": "https://nustart.solutions",
  "logo": "https://nustart.solutions/logo.png",
  "description": "Full-service SEO agency specializing in entity-first optimization strategies",
  "sameAs": [
    "https://www.linkedin.com/company/nustart-solutions",
    "https://twitter.com/nustartsolutions",
    "https://www.facebook.com/nustartsolutions"
  ]
}
```

**Enhanced Schema** (add these properties):
```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "NuStart Solutions",
  "alternateName": "NuStart",
  "url": "https://nustart.solutions",
  "logo": {
    "@type": "ImageObject",
    "url": "https://nustart.solutions/logo.png",
    "width": 600,
    "height": 60
  },
  "description": "Full-service SEO agency specializing in entity-first optimization strategies",
  "foundingDate": "2020",
  "founder": {
    "@type": "Person",
    "name": "Founder Name"
  },
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "123 Main St",
    "addressLocality": "City",
    "addressRegion": "State",
    "postalCode": "12345",
    "addressCountry": "US"
  },
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+1-555-1234",
    "contactType": "Customer Service",
    "email": "hello@nustart.solutions"
  },
  "sameAs": [
    "https://www.linkedin.com/company/nustart-solutions",
    "https://twitter.com/nustartsolutions",
    "https://www.facebook.com/nustartsolutions"
  ]
}
```

**Implementation**: Add JSON-LD script tag in `<head>` section

#### For Article/Blog Content

**Required Schema**:
```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "Your Article Title Here",
  "description": "Meta description or first paragraph",
  "image": "https://nustart.solutions/article-image.jpg",
  "datePublished": "2026-01-15T08:00:00+00:00",
  "dateModified": "2026-01-16T10:30:00+00:00",
  "author": {
    "@type": "Person",
    "name": "Author Name",
    "url": "https://nustart.solutions/team/author-name",
    "sameAs": [
      "https://www.linkedin.com/in/author-name",
      "https://twitter.com/authorname"
    ]
  },
  "publisher": {
    "@type": "Organization",
    "name": "NuStart Solutions",
    "logo": {
      "@type": "ImageObject",
      "url": "https://nustart.solutions/logo.png"
    }
  },
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "https://nustart.solutions/article-url"
  }
}
```

**Advanced**: Add `about` and `mentions` properties for entity connections:
```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "...": "...",
  "about": {
    "@type": "Thing",
    "name": "Entity-First SEO",
    "description": "SEO strategy focusing on entity optimization",
    "sameAs": "https://en.wikipedia.org/wiki/Semantic_search"
  },
  "mentions": [
    {
      "@type": "SoftwareApplication",
      "name": "Google Natural Language API"
    },
    {
      "@type": "Organization",
      "name": "Google",
      "sameAs": "https://en.wikipedia.org/wiki/Google"
    }
  ]
}
```

#### For Service Pages

**Service Schema**:
```json
{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Entity-First SEO Consulting",
  "description": "Comprehensive entity optimization strategies",
  "provider": {
    "@type": "Organization",
    "name": "NuStart Solutions"
  },
  "serviceType": "SEO Consulting",
  "areaServed": {
    "@type": "Country",
    "name": "United States"
  },
  "hasOfferCatalog": {
    "@type": "OfferCatalog",
    "name": "SEO Services",
    "itemListElement": [
      {
        "@type": "Offer",
        "itemOffered": {
          "@type": "Service",
          "name": "Technical SEO Audit"
        }
      },
      {
        "@type": "Offer",
        "itemOffered": {
          "@type": "Service",
          "name": "Content Strategy"
        }
      }
    ]
  }
}
```

#### For Team/Person Pages

**Person Schema**:
```json
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "Team Member Name",
  "jobTitle": "Senior SEO Strategist",
  "worksFor": {
    "@type": "Organization",
    "name": "NuStart Solutions"
  },
  "url": "https://nustart.solutions/team/member-name",
  "image": "https://nustart.solutions/team/member-photo.jpg",
  "sameAs": [
    "https://www.linkedin.com/in/member-name",
    "https://twitter.com/membername"
  ],
  "description": "Expert in entity-first SEO with 10+ years experience",
  "knowsAbout": ["SEO", "Entity Optimization", "Semantic Search"]
}
```

### Strategy 2: Add BreadcrumbList Schema

**Purpose**: Show content hierarchy and entity relationships

**Implementation**:
```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Home",
      "item": "https://nustart.solutions"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Services",
      "item": "https://nustart.solutions/services"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "Entity-First SEO",
      "item": "https://nustart.solutions/services/entity-first-seo"
    }
  ]
}
```

### Strategy 3: Validate & Test Schema

**Tools to use**:
1. **Google Rich Results Test**: https://search.google.com/test/rich-results
2. **Schema Markup Validator**: https://validator.schema.org/
3. **Chrome Extensions**: Schema Markup Validator, SEO Meta in 1 Click

**Validation Checklist**:
- [ ] No schema markup errors
- [ ] All required properties present
- [ ] URLs are absolute and valid
- [ ] Dates in ISO 8601 format
- [ ] Images are accessible and correct dimensions
- [ ] sameAs links are active and correct

**Script Automation**:
```bash
python execution/audit_schema_markup.py --url https://nustart.solutions/page --validate
```

## Content Optimization (Weeks 2-4)

### Strategy 4: Optimize Entity Placement

**Goal**: Increase entity salience by strategic positioning

#### Title Tag Optimization
**Before**: "SEO Services | Digital Marketing Agency"  
**After**: "Entity-First SEO Services | NuStart Solutions"

**Rules**:
- Primary entity in title tag
- Target entity within first 50 characters
- Brand entity at end (for recognition)

#### H1 Optimization
**Before**: "Our SEO Services"  
**After**: "Entity-First SEO: How NuStart Solutions Optimizes for Google's Knowledge Graph"

**Rules**:
- H1 must contain primary entity
- Add context and related entities
- Make it specific, not generic

#### First Paragraph Optimization
**Before**: 
```
We offer SEO services to help businesses rank better. Our strategies are proven and effective.
```

**After**:
```
NuStart Solutions specializes in entity-first SEO, a modern optimization strategy that aligns with Google's Knowledge Graph and semantic search algorithms. Unlike traditional keyword-focused SEO, entity-first optimization establishes your brand as a recognized entity with clear relationships to relevant topics, locations, and services.
```

**Rules**:
- Mention primary entity in first sentence
- Define the entity clearly
- Add context with related entities
- Aim for 100-150 words in opening paragraph

#### Subheadings (H2-H3)
**Before**:
- "What We Do"
- "Our Process"
- "Why Choose Us"

**After**:
- "What is Entity-First SEO?"
- "How NuStart Solutions Implements Entity Optimization"
- "Why Entity-Based Strategies Outperform Keyword-Only SEO"

**Rules**:
- Include entity names in at least 50% of H2s
- Use question format with entities for featured snippets
- Maintain natural language

### Strategy 5: Enhance Entity Context

**Goal**: Add content that describes entity attributes and relationships

#### Entity Definition Sections
Add dedicated sections that explicitly define entities:

```markdown
## About NuStart Solutions

NuStart Solutions is a full-service SEO agency founded in 2020, specializing in entity-first optimization strategies. Based in [City, State], NuStart helps businesses establish strong entity recognition in Google's Knowledge Graph through structured data implementation, content optimization, and semantic authority building.

**Services Offered**:
- Entity-First SEO Consulting
- Schema Markup Implementation
- Content Cluster Architecture
- Semantic Search Optimization

**Team**: Led by [Founder Name], a recognized expert in semantic SEO with 15+ years of industry experience.
```

**This accomplishes**:
- Increases entity salience for "NuStart Solutions"
- Connects entity to service entities
- Establishes person entity (founder)
- Provides attributes (location, founding date, expertise)

#### Entity Relationship Sections
Connect your entity to related entities:

```markdown
## How Entity-First SEO Works with Google

Entity-first SEO leverages **Google's Knowledge Graph**, a massive database containing billions of entities and their relationships. When you optimize content for entities, you're helping Google understand:

- **What your organization does** (service entities)
- **Who works for you** (person entities)
- **Where you operate** (location entities)
- **What topics you cover** (concept entities)

This approach aligns with **semantic search**, Google's method of understanding meaning and intent rather than just matching keywords.
```

**Bold formatting** = signal to Google that these are important concepts/entities

### Strategy 6: Add Entity-Rich Supporting Content

**Tactics**:

1. **FAQ Sections** with entity-focused questions:
   ```markdown
   ## Frequently Asked Questions About Entity-First SEO
   
   **Q: What is an entity in SEO?**
   A: An entity is any distinct concept that Google's Knowledge Graph can identify, such as a person (like John Mueller), an organization (like Google), a location (like Mountain View, California), or a product (like Google Search Console).
   
   **Q: How does NuStart Solutions differ from traditional SEO agencies?**
   A: NuStart Solutions specializes in entity-first optimization, which goes beyond keywords to establish your brand as a recognized entity in Google's Knowledge Graph...
   ```

2. **Case Studies** featuring client entities:
   ```markdown
   ## Case Study: Entity Optimization for [Client Organization]
   
   [Client Name], a [industry] company based in [Location], partnered with NuStart Solutions to implement entity-first SEO...
   ```

3. **Author Bios** (person entity signals):
   ```markdown
   ## About the Author
   
   **[Author Name]** is a Senior SEO Strategist at NuStart Solutions with expertise in entity optimization and semantic search. With 10+ years of experience, [Author] has helped Fortune 500 companies establish Knowledge Graph presence. Connect with [Author] on [LinkedIn](link) and [Twitter](link).
   ```
   
   + Include Person schema for author

### Strategy 7: Internal Linking with Entity Anchors

**Before**:
- "Learn more [here](link)"
- "Check out [this article](link)"
- "[Read more](link)"

**After**:
- "Learn more about [entity-first SEO strategies](link)"
- "Explore our [schema markup services](link)"
- "Discover how [Google's Knowledge Graph](link) works"

**Rules**:
- Anchor text should contain entity name or entity-related phrase
- Link to entity-relevant pages (service pages, topic clusters, definitions)
- Avoid generic anchor text
- Internal link from high-authority pages to entity pages

**Script to Audit**:
```bash
python execution/analyze_page_entities.py --url https://nustart.solutions/page --audit-links
```

### Strategy 8: Co-Occurring Entities

**Goal**: Mention related entities together to strengthen semantic relationships

**Example**:
```markdown
When optimizing for **entity-first SEO**, it's essential to leverage **Google's Natural Language API** to measure entity salience. Tools like **Schema.org** markup help declare entities explicitly, while platforms like **Google Search Console** track performance. Industry experts like **John Mueller** and **Danny Sullivan** have emphasized the importance of semantic optimization in recent **Google Search Central** documentation.
```

**This creates relationships**:
- Entity-First SEO ↔ Google Natural Language API
- Schema.org ↔ entities
- John Mueller ↔ Google
- Google Search Console ↔ tracking

**Guidelines**:
- Mention 5-10 related entities per 1000 words
- Use bolding or italics for entity emphasis (optional, not required)
- Link to authoritative sources when mentioning entities (Wikipedia, official sites)

## Authority Building (Ongoing)

### Strategy 9: Build External Entity Presence

**Goal**: Get your entity recognized across the web

#### 1. Create/Claim Entity Profiles

**Priority Platforms**:
- [ ] **Wikipedia**: If notable enough (high barrier, but most authoritative)
- [ ] **Wikidata**: Open database, easier to create entity page
- [ ] **Google Business Profile**: Essential for local entity signals
- [ ] **Crunchbase**: For company entities
- [ ] **LinkedIn Company Page**: Include all entity details (description, location, etc.)
- [ ] **Industry Directories**: Specific to your niche

**Consistency Rules**:
- **NAP (Name, Address, Phone)**: Identical across ALL platforms
- **Description**: Similar wording across platforms
- **Logo**: Same image file across platforms
- **Social Links**: Link all profiles together (cross-platform sameAs)

#### 2. Earn Entity Citations

**Methods**:
- **Guest Posts**: Include author bio with entity information + Person schema
- **Press Releases**: Mention organization entity with full details
- **Industry Publications**: Get featured/quoted with entity context
- **Podcast Appearances**: Show notes should link to your entity profiles
- **Speaking Engagements**: Event pages should mention your entity
- **Client Testimonials**: Clients mention your entity by name

**Quality over Quantity**: One citation from Wikipedia > 100 citations from low-quality directories

#### 3. Optimize Social Profiles

**LinkedIn**:
- Complete all fields (company size, founding year, specialties)
- Post content mentioning your entity and service entities
- Add employees (person entities) and link to their profiles

**Twitter/X**:
- Bio should include entity definition
- Use consistent handle across platforms
- Pin tweet defining your entity

**Facebook**:
- Fill out "About" section completely
- Add address, phone, website
- Regular posts mentioning entity

### Strategy 10: Get Listed in Knowledge Graph Sources

**Tactics**:

1. **Wikipedia Entry** (if eligible):
   - Requires notability (significant media coverage)
   - Follow Wikipedia guidelines strictly
   - Hire a Wikipedia editor if needed
   - Don't be promotional, be factual

2. **Wikidata Entry** (easier alternative):
   - Create free account
   - Add entity with properties (instance of: organization, country: US, etc.)
   - Link to official website, social profiles
   - Add founding date, location, industry

3. **Google Merchant Center** (if selling products/services):
   - List services/products with full entity details
   - Proper product schema on website

4. **Industry-Specific Databases**:
   - Moz Top 500 (for SEO agencies)
   - Clutch/GoodFirms (agency listings)
   - Industry associations and directories

## Advanced Tactics (Month 2+)

### Strategy 11: Implement Advanced Schema Relationships

**WebPage + PrimaryImageOf Page**:
```json
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "Entity-First SEO Guide",
  "about": {
    "@type": "Thing",
    "name": "Entity-First SEO"
  },
  "author": {
    "@type": "Organization",
    "name": "NuStart Solutions"
  },
  "primaryImageOfPage": {
    "@type": "ImageObject",
    "url": "https://nustart.solutions/images/entity-seo-guide.jpg",
    "caption": "Entity-First SEO Strategy Diagram"
  }
}
```

**HowTo Schema** (for guide content):
```json
{
  "@context": "https://schema.org",
  "@type": "HowTo",
  "name": "How to Implement Entity-First SEO",
  "description": "Step-by-step guide to entity optimization",
  "step": [
    {
      "@type": "HowToStep",
      "name": "Identify Primary Entities",
      "text": "List all entities associated with your brand..."
    },
    {
      "@type": "HowToStep",
      "name": "Implement Schema Markup",
      "text": "Add JSON-LD schema for Organization, Person, and Service entities..."
    }
  ]
}
```

**VideoObject Schema** (if you have video content):
```json
{
  "@context": "https://schema.org",
  "@type": "VideoObject",
  "name": "Entity-First SEO Explained",
  "description": "Introduction to entity optimization strategies",
  "thumbnailUrl": "https://nustart.solutions/video-thumb.jpg",
  "uploadDate": "2026-01-15T08:00:00+00:00",
  "duration": "PT10M",
  "contentUrl": "https://nustart.solutions/videos/entity-seo.mp4"
}
```

### Strategy 12: Build Content Clusters

**Hub-and-Spoke Model**:

**Pillar Page** (Hub): "Complete Guide to Entity-First SEO"
- **URL**: /entity-first-seo
- **Target Entity**: Entity-First SEO (concept)
- **Length**: 5000+ words, comprehensive
- **Schema**: Article + HowTo

**Cluster Pages** (Spokes):
- "/entity-salience-optimization" → focuses on salience tactics
- "/schema-markup-guide" → focuses on structured data
- "/google-knowledge-graph" → explains Knowledge Graph
- "/semantic-search-strategy" → semantic SEO tactics

**Internal Linking**:
- Pillar page links to ALL cluster pages
- Cluster pages link back to pillar
- Cluster pages cross-link to related clusters
- All links use entity-based anchor text

**Result**: Establishes topical authority for entity cluster

### Strategy 13: Monitor Competitor Entities

**Process**:
1. Identify top 5 competitors
2. Run entity analysis on their key pages:
   ```bash
   python execution/generate_entity_report.py \
     --url https://nustart.solutions/page \
     --compare https://competitor.com/page
   ```
3. Review gaps:
   - What entities do they optimize for that you don't?
   - What schema types do they use that you don't?
   - What's their average entity salience vs. yours?
4. Implement improvements to close gaps

## Re-Measurement & Iteration

### After Implementing Quick Wins (Week 1)
```bash
python execution/generate_entity_report.py \
  --url https://nustart.solutions/page \
  --target "NuStart Solutions" \
  --compare-baseline .tmp/baseline_report.json
```

**Expected Improvements**:
- Schema completeness: +30-50 points
- Overall entity score: +15-25 points

### After Content Optimization (Week 4)
```bash
python execution/generate_entity_report.py \
  --url https://nustart.solutions/page \
  --target "NuStart Solutions"
```

**Expected Improvements**:
- Entity salience: +0.2-0.4 points
- Content coverage: +20-30 points
- Overall entity score: +25-40 points

### Ongoing (Monthly)
- Track composite entity score trends
- Monitor SERP features (knowledge panels, rich snippets)
- Check Google Search Console for entity-based query increases
- Review competitor entity scores

## Prioritization Matrix

### High Impact, Low Effort (Do First)
- Add Organization schema to homepage
- Add Article schema to blog posts
- Optimize title tags with entity names
- Rewrite first paragraphs to feature entities

### High Impact, High Effort (Next)
- Build content clusters
- Create Wikipedia/Wikidata entries
- Comprehensive content rewrite with entity focus
- Multi-page schema implementation

### Low Impact, Low Effort (Quick Wins)
- Add BreadcrumbList schema
- Optimize internal link anchor text
- Add author bios with Person schema
- Update social profiles

### Low Impact, High Effort (Defer)
- Video schema (unless you have video content)
- Complex nested schema (beyond basic types)
- Industry directory submissions (dozens of them)

## Troubleshooting Common Issues

### "Implemented schema but score didn't improve"
**Check**:
- Schema validated with no errors?
- Schema contains meaningful entity information (not placeholder text)?
- Google has recrawled the page? (Check Search Console)

**Wait**: Schema improvements take 2-4 weeks to reflect in search

### "Entity salience not increasing despite content changes"
**Possible causes**:
- Entity mentioned but not in strategic positions (title, H1, first paragraph)
- Too many competing entities diluting salience
- Content lacks entity context (just mentions name without describing it)

**Solution**: Focus on primary entity only, reduce secondary entity mentions

### "Competitor has higher score despite less content"
**Check**:
- Their external entity signals (Wikipedia, citations, profiles)
- Their schema completeness
- Their domain authority and backlinks

**Solution**: Focus on external authority building (Strategy 9-10)

## Success Metrics

### Leading Indicators (1-3 months)
- Composite entity score increases
- Schema validation passes
- Entity salience for target entities >0.5

### Lagging Indicators (3-6 months)
- Rich snippet appearances in SERPs
- Knowledge panel for brand entity
- Increase in entity-based query traffic (Search Console)
- Featured snippet wins for entity-related queries

### Long-term Indicators (6-12 months)
- Top 3 rankings for entity-based queries
- Citations on high-authority sites (Wikipedia, industry publications)
- Consistent knowledge panel across brand queries

## Next Steps

1. Generate current entity score report (if not done yet):
   ```bash
   python execution/generate_entity_report.py --url [YOUR_URL]
   ```

2. Review recommendations in report

3. Implement quick wins (Schema + structural fixes) in Week 1

4. Schedule content optimization for Weeks 2-4

5. Set up monthly re-measurement calendar

6. Track improvements in Google Sheets dashboard

For ongoing strategy refinement, revisit `directives/entity_seo_explanation.md` and `directives/measure_entity_score.md`.
