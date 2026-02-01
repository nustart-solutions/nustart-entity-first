# NuStart Entity-First SEO Plugin

WordPress plugin implementing entity-first SEO with ACF fields and custom post types.

## Requirements

- **Advanced Custom Fields (ACF)** - Free or Pro version
- WordPress 5.0+
- PHP 7.4+

## Installation

1. Install and activate **Advanced Custom Fields** (free version from WordPress.org)
2. Install this plugin (download from GitHub or upload ZIP)
3. Activate the plugin in WordPress admin
4. Plugin will automatically check for ACF and show admin notice if missing

## Architecture

This plugin uses **ACF + Custom Post Types** for entity management:

- **Custom Post Type**: `ns_entity` - Entities stored as WordPress posts
- **Taxonomy**: `ns_entity_type` (Organization, Person, Service, Place, etc.)
- **ACF Fields**: Core metadata and schema properties stored in flexible JSON format
- **ACF Free Compatible**: Uses simple field types (text, textarea, relationship)

## What It Does

### On Activation:
- Checks for ACF dependency
- Registers `ns_entity` custom post type
- Registers `ns_entity_type` taxonomy with default terms
- Flushes rewrite rules

### On Every Page Load:
- Outputs proper JSON-LD schema in `<head>`
- Automatically builds `@graph` with all relevant entities
- Uses ACF-based schema generator (`NS_Schema_Generator_ACF`)

## Custom Post Type: ns_entity

### Core Fields (ACF)
- **Entity ID** - Unique identifier (e.g., `org-nustart`, `person-anne`)
- **Status** - Published or Draft
- **Canonical URL** - Primary URL for this entity
- **Same As** - External profile URLs (one per line: LinkedIn, Twitter, etc.)
- **Parent Entity** - Relationship field linking to parent entity

### Schema Properties (JSON)
All schema.org properties are stored in a single `schema_json` field as JSON:

```json
{
  "@type": "Service",
  "name": "WordPress Emergency Support",
  "serviceType": "Emergency WordPress Support",
  "description": "24/7 WordPress emergency fixes",
  "isPartOf": {"@id": "https://nustart.solutions/#service-wordpress-support"},
  "hasOfferCatalog": [
    {"@id": "https://nustart.solutions/#service-emergency-fixes"}
  ],
  "additionalProperty": [
    {"@type": "PropertyValue", "name": "Platform", "value": "WordPress"}
  ]
}
```

This approach supports:
- Hierarchical relationships (`isPartOf`, `hasOfferCatalog`)
- Custom properties via `additionalProperty`
- Full schema.org flexibility without rigid ACF flexible content layouts

## REST API Access

All entities are accessible via WordPress REST API:

```bash
# List all entities
GET /wp-json/wp/v2/ns_entity

# Get specific entity
GET /wp-json/wp/v2/ns_entity/{id}

# Filter by entity type
GET /wp-json/wp/v2/ns_entity?ns_entity_type=organization
```

ACF fields are exposed via REST API when properly configured.

## Usage Examples

### Create an Entity via WordPress Admin

1. Go to **Entities → Add New Entity**
2. Set title (entity name)
3. Select **Entity Type** taxonomy term
4. Fill in **Entity Core Fields**:
   - Entity ID: `service-wordpress-emergency`
   - Status: Published
   - Canonical URL: `https://nustart.solutions/services/emergency`
5. Add **Schema.org JSON**:
```json
{
  "@type": "Service",
  "serviceType": "Emergency WordPress Support",
  "description": "24/7 WordPress emergency fixes",
  "provider": {"@id": "https://nustart.solutions/#org-nustart"}
}
```

### Create an Entity via Python Script

```python
import requests

response = requests.post(
    'https://nustart.solutions/wp-json/wp/v2/ns_entity',
    auth=('username', 'application_password'),
    json={
        'title': 'WordPress Emergency Support',
        'status': 'publish',
        'ns_entity_type': [1],  # Organization term ID
        'acf': {
            'entity_id': 'service-wordpress-emergency',
            'entity_status': 'published',
            'canonical_url': 'https://nustart.solutions/services/emergency',
            'schema_json': json.dumps({
                '@type': 'Service',
                'serviceType': 'Emergency WordPress Support',
                'description': '24/7 WordPress emergency fixes'
            })
        }
    }
)
```

## Getting Started

After installation, create your first entity:

1. Go to **Entities → Add New Entity**
2. Set the title and entity type
3. Fill in core fields (entity_id, canonical_url)
4. Add schema.org JSON properties
5. Publish

## Schema Output

The plugin automatically generates JSON-LD schema on every page using `NS_Schema_Generator_ACF`:

- Reads entity data from ACF fields
- Builds `@graph` structure with all relevant entities
- Handles hierarchical relationships (`isPartOf`, `hasOfferCatalog`)
- Outputs in `<head>` via `wp_head` hook

Example output on homepage:
```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "https://nustart.solutions/#org-nustart",
      "name": "NuStart Solutions",
      "url": "https://nustart.solutions/",
      "sameAs": ["https://www.linkedin.com/company/nustart-solutions"]
    },
    {
      "@type": "WebSite",
      "@id": "https://nustart.solutions/#website",
      "url": "https://nustart.solutions/",
      "publisher": {"@id": "https://nustart.solutions/#org-nustart"}
    }
  ]
}
```

## Files

### Core Files
- `nustart-entity-seo.php` - Main plugin file, activation hooks

### Plugin Files
- `includes/class-entity-post-type.php` - Registers `ns_entity` post type and taxonomy
- `includes/acf-fields/entity-core-fields.php` - Core ACF fields (entity_id, status, etc.)
- `includes/acf-fields/entity-schema-properties.php` - Schema JSON field
- `includes/acf-fields/page-entity-fields.php` - Page-level entity mapping fields
- `includes/class-schema-generator-acf.php` - Schema output from ACF data
- `vendor/yahnis-elsts/plugin-update-checker/` - GitHub update checker library

## Versioning

Every plugin change requires:
1. Increment version in `nustart-entity-seo.php` header and `NS_ENTITY_VERSION` constant
2. Add entry to `CHANGELOG.md` with date, version, and changes
3. Use semantic versioning: MAJOR.MINOR.PATCH

## Updates

This plugin automatically checks for updates from GitHub. When a new version is released:

1. WordPress will show an update notification
2. Click "Update Now" to install the latest version
3. Updates are delivered directly from the public GitHub repository

## Next Steps

1. Install and activate (ensure ACF is active)
2. Create entities via WordPress admin or REST API
3. Map entities to pages using ACF fields
4. Check page source for JSON-LD schema output
5. Test with [Google Rich Results Test](https://search.google.com/test/rich-results)
