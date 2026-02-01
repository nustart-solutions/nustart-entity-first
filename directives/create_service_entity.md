# Creating Service Entities with Flexible JSON

## Quick Example

```bash
python execution/create_service.py \
  --entity-id service-new-website \
  --name "New Website Development" \
  --parent service-web-development \
  --service-type "WordPress Website Development" \
  --description "Custom WordPress website development with modern design and accessibility" \
  --platform "WordPress" \
  --design-system "Custom CSS" \
  --accessibility "WCAG 2.1 AA" \
  --mobile-first \
  --gdpr \
  --timeline "4-week turnaround" \
  --custom "Front-End Builder=Breakdance, Elementor, Bricks" \
  --custom "Hosting Stack=Cloudflare CDN + Pressable" \
  --custom "Security Stack=SSL, HTTPS, Cloudflare WAF"
```

## What This Creates

**Entity Post:**
- Title: "New Website Development"
- Entity ID: `service-new-website`
- Parent: `service-web-development` (creates `isPartOf` relationship)
- Type: Service

**Schema JSON Field:**
```json
{
  "@type": "Service",
  "name": "New Website Development",
  "serviceType": "WordPress Website Development",
  "description": "Custom WordPress website development...",
  "additionalProperty": [
    {"@type": "PropertyValue", "name": "Platform", "value": "WordPress"},
    {"@type": "PropertyValue", "name": "Design System", "value": "Custom CSS"},
    {"@type": "PropertyValue", "name": "Accessibility Compliance", "value": "WCAG 2.1 AA"},
    {"@type": "PropertyValue", "name": "Mobile-First Design", "value": "Yes"},
    {"@type": "PropertyValue", "name": "GDPR Compliant", "value": "Yes"},
    {"@type": "PropertyValue", "name": "Launch Timeline", "value": "4-week turnaround"},
    {"@type": "PropertyValue", "name": "Front-End Builder", "value": "Breakdance, Elementor, Bricks"},
    {"@type": "PropertyValue", "name": "Hosting Stack", "value": "Cloudflare CDN + Pressable"},
    {"@type": "PropertyValue", "name": "Security Stack", "value": "SSL, HTTPS, Cloudflare WAF"}
  ]
}
```

**Generated Schema Output:**
```json
{
  "@type": "Service",
  "@id": "https://nustart.solutions/services/new-website/#service-new-website",
  "name": "New Website Development",
  "url": "https://nustart.solutions/services/new-website/",
  "isPartOf": {
    "@id": "https://nustart.solutions/services/web-development/#service-web-development"
  },
  "category": "Web Development Services",
  "provider": {
    "@id": "https://nustart.solutions/#org-nustart"
  },
  "serviceType": "WordPress Website Development",
  "description": "Custom WordPress website development...",
  "additionalProperty": [...]
}
```

## Using JSON Files

For complex services, create a JSON file:

**properties.json:**
```json
{
  "@type": "Service",
  "name": "New Website Development",
  "serviceType": "WordPress Website Development",
  "description": "Full description here...",
  "offers": {
    "@type": "Offer",
    "price": "Contact for quote",
    "priceCurrency": "CAD"
  },
  "additionalProperty": [
    {"@type": "PropertyValue", "name": "Platform", "value": "WordPress"},
    {"@type": "PropertyValue", "name": "Theme Type", "value": "Block Theme, Classic Theme, Hybrid"},
    {"@type": "PropertyValue", "name": "Front-End Builder", "value": "Breakdance, Elementor, Bricks"},
    {"@type": "PropertyValue", "name": "Back-End Framework", "value": "PHP (WordPress core), MySQL"},
    {"@type": "PropertyValue", "name": "Accessibility Compliance", "value": "ADA Compliant (WCAG 2.1 AA)"},
    {"@type": "PropertyValue", "name": "Hosting Stack", "value": "Cloudflare CDN + Pressable / WPEngine"},
    {"@type": "PropertyValue", "name": "Security Stack", "value": "SSL, HTTPS, Cloudflare WAF, reCAPTCHA, 2FA"},
    {"@type": "PropertyValue", "name": "Speed Optimization", "value": "WP Rocket, Cloudflare APO, WebP"},
    {"@type": "PropertyValue", "name": "SEO Optimization", "value": "Yoast, RankMath, schema markup, Core Web Vitals"},
    {"@type": "PropertyValue", "name": "Mobile-First Design", "value": "Responsive"},
    {"@type": "PropertyValue", "name": "GDPR Compliant", "value": "Yes"},
    {"@type": "PropertyValue", "name": "Launch Timeline", "value": "4-week turnaround"},
    {"@type": "PropertyValue", "name": "Lead-Gen Optimized", "value": "Yes"},
    {"@type": "PropertyValue", "name": "Cloud Infrastructure", "value": "Yes"},
    {"@type": "PropertyValue", "name": "Local SEO Ready", "value": "Yes"},
    {"@type": "PropertyValue", "name": "Client Handoff", "value": "Includes training and documentation"},
    {"@type": "PropertyValue", "name": "Content Editing", "value": "No-code page builder UX"}
  ]
}
```

Then run:
```bash
python execution/create_service.py \
  --entity-id service-new-website \
  --name "New Website Development" \
  --parent service-web-development \
  --properties-file properties.json
```

## Hierarchical Structure

**Parent Service:**
```bash
python execution/create_service.py \
  --entity-id service-web-development \
  --name "Web Development Services" \
  --description "Professional WordPress website development"
```

**Child Services:**
```bash
# New Website
python execution/create_service.py \
  --entity-id service-new-website \
  --name "New Website Development" \
  --parent service-web-development \
  --properties-file new-website.json

# Website Redesign
python execution/create_service.py \
  --entity-id service-website-redesign \
  --name "Website Redesign" \
  --parent service-web-development \
  --properties-file redesign.json

# Maintenance
python execution/create_service.py \
  --entity-id service-website-maintenance \
  --name "Website Maintenance" \
  --parent service-web-development \
  --properties-file maintenance.json
```

**Result:** Parent service automatically gets `hasOfferCatalog` with all child services listed!

## Benefits

✅ **Infinitely Flexible** - Add ANY properties without touching code
✅ **Schema.org Compliant** - Use standard `additionalProperty` pattern
✅ **Hierarchical** - Proper parent/child relationships with `isPartOf`
✅ **Auto-Generated** - `provider`, `category`, `hasOfferCatalog` added automatically
✅ **Python Workflow** - Easy scripting for bulk operations
✅ **WordPress UI** - Still editable in admin if needed
