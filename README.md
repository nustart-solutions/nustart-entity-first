# NuStart Entity-First SEO Plugin

WordPress plugin implementing entity-first SEO with ACF fields and custom post types.

## Requirements

- **ACF Pro** (required for custom fields)
- WordPress 5.0+
- PHP 7.4+

## Installation

1. Copy this entire `wordpress-plugin` folder to `wp-content/plugins/nustart-entity-seo/`
2. Ensure ACF Pro is installed and activated
3. Activate the plugin in WordPress admin

## Architecture

This plugin uses a **hybrid architecture** that combines:
- **ACF + Custom Post Types** (current system) - Entities stored as `ns_entity` posts with ACF fields
- **Legacy Custom Tables** (backward compatibility) - `ns_entities` and `ns_page_entity_map` tables maintained during migration

### Current System (ACF-based)

Entities are stored as WordPress posts (`ns_entity` post type) with:
- **Taxonomy**: `ns_entity_type` (Organization, Person, Service, Place, etc.)
- **ACF Fields**: Core metadata and schema properties stored in flexible JSON format

## What It Does

### On Activation:
- Creates legacy tables (`ns_entities`, `ns_page_entity_map`) for backward compatibility
- Registers `ns_entity` custom post type
- Registers `ns_entity_type` taxonomy with default terms
- Seeds "NuStart Solutions" organization entity (to legacy table)
- Seeds "Anne Allen" person entity (to legacy table)
- Maps homepage to organization entity

### On Every Page Load:
- Outputs proper JSON-LD schema in `<head>`
- Automatically builds `@graph` with all relevant entities
- Uses ACF-based schema generator (`NS_Schema_Generator_ACF`)

## Custom Post Type: ns_entity

### Core Fields (ACF)
- **Entity ID** - Unique identifier (e.g., `org-nustart`, `person-anne`)
- **Status** - Published or Draft
- **Canonical URL** - Primary URL for this entity
- **Same As** - External profile URLs (LinkedIn, Twitter, etc.)
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

### Legacy API (Backward Compatibility)

The old table-based models still work:

```php
$entity_model = new NS_Entity_Model();
$entity_model->upsert('service-wordpress-emergency', [
    'entity_type' => 'Service',
    'name' => 'WordPress Emergency Support',
    'properties' => ['description' => '...'],
    'status' => 'published'
]);
```

## Migration from Legacy Tables

A migration tool is included to move data from custom tables to ACF + Custom Post Types.

### Via WordPress Admin

1. Go to **Entities → Migrate Data**
2. Review migration statistics
3. Run **Dry Run** to preview changes
4. Uncheck "Dry Run" and click **Run Migration**

### Via WP-CLI

```bash
# Dry run (preview only)
wp nustart-entity migrate --dry-run

# Live migration
wp nustart-entity migrate

# Verify migration
wp nustart-entity verify
```

The migration:
- Creates `ns_entity` posts from `ns_entities` table rows
- Converts JSON properties to ACF `schema_json` field
- Links parent-child relationships
- Maps page entity associations to ACF fields on posts/pages

## Current Entities Seeded

1. **org-nustart** - NuStart Solutions (Organization)
2. **person-anne** - Anne Allen (Person)

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

### ACF-Based System (Current)
- `includes/class-entity-post-type.php` - Registers `ns_entity` post type and taxonomy
- `includes/acf-fields/entity-core-fields.php` - Core ACF fields (entity_id, status, etc.)
- `includes/acf-fields/entity-schema-properties.php` - Schema JSON field
- `includes/acf-fields/page-entity-fields.php` - Page-level entity mapping fields
- `includes/class-schema-generator-acf.php` - Schema output from ACF data
- `includes/class-migration.php` - Migration tool from legacy tables

### Legacy System (Backward Compatibility)
- `includes/class-entity-model.php` - Entity CRUD for `ns_entities` table
- `includes/class-page-entity-map-model.php` - Page mapping CRUD
- `includes/class-schema-generator.php` - Legacy schema generator
- `includes/class-rest-api.php` - Custom REST endpoints for legacy tables

## Versioning

Every plugin change requires:
1. Increment version in `nustart-entity-seo.php` header and `NS_ENTITY_VERSION` constant
2. Add entry to `CHANGELOG.md` with date, version, and changes
3. Use semantic versioning: MAJOR.MINOR.PATCH

## Next Steps

1. Install and activate (ensure ACF Pro is active)
2. Run migration if you have legacy table data
3. Create entities via WordPress admin or REST API
4. Check page source for JSON-LD schema output
5. Test with [Google Rich Results Test](https://search.google.com/test/rich-results)
