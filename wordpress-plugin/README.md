# NuStart Entity-First SEO Plugin

WordPress plugin implementing entity-first SEO with two-table architecture.

## Installation

1. Copy this entire `wordpress-plugin` folder to `wp-content/plugins/nustart-entity-seo/`
2. Activate the plugin in WordPress admin

## What It Does

### On Activation:
- Creates `ns_entities` table (knowledge layer)
- Creates `ns_page_entity_map` table (page layer)
- Seeds "NuStart Solutions" organization entity
- Seeds "Anne Allen" person entity
- Maps homepage to organization entity

### On Every Page Load:
- Outputs proper JSON-LD schema in `<head>`
- Automatically builds @graph with all relevant entities

## Tables Created

### ns_entities
Stores all entities (Organization, Person, Service, Place, etc.)
- `entity_id` - Unique identifier (e.g., 'org-nustart')
- `entity_type` - Organization, Person, Service, etc.
- `name` - Display name
- `properties` - JSON storage for schema properties
- `same_as` - JSON array of external profile URLs

### ns_page_entity_map
Maps URLs to entities
- `url` - Full URL
- `primary_entity_id` - Main entity for this page
- `about_entity_ids` - Array of entities this page is about
- `mentions_entity_ids` - Array of entities mentioned
- `faq_data` - FAQ schema data

## Usage Examples

### Add a new Service entity:

```php
$entity_model = new NS_Entity_Model();

$entity_model->upsert('service-wordpress-emergency', [
    'entity_type' => 'Service',
    'name' => 'WordPress Emergency Support',
    'slug' => 'wordpress-emergency-support',
    'canonical_url' => 'https://nustart.solutions/services/emergency',
    'parent_entity_id' => 'org-nustart',
    'properties' => [
        'description' => '24/7 WordPress emergency fixes',
        'serviceType' => 'Emergency WordPress Support',
        'provider' => ['@id' => 'https://nustart.solutions/#org-nustart']
    ],
    'status' => 'published'
]);
```

### Map a page to entities:

```php
$page_model = new NS_Page_Entity_Map_Model();

$page_model->upsert('https://nustart.solutions/services/emergency', [
    'page_type' => 'service',
    'primary_entity_id' => 'service-wordpress-emergency',
    'about_entity_ids' => ['service-wordpress-emergency', 'org-nustart'],
    'robots' => 'index,follow',
    'sitemap_include' => true
]);
```

## Current Entities Seeded

1. **org-nustart** - NuStart Solutions (Organization)
2. **person-anne** - Anne Allen (Person)

## Schema Output

On the homepage, this plugin will output:
- Organization schema for NuStart Solutions
- WebSite schema
- WebPage schema
- Person schema for Anne Allen (mentioned entity)

All wrapped in a proper `@graph` structure.

## Files

- `nustart-entity-seo.php` - Main plugin file
- `includes/class-entity-model.php` - Entity CRUD operations
- `includes/class-page-entity-map-model.php` - Page mapping CRUD
- `includes/class-schema-generator.php` - Schema JSON-LD generator

## Next Steps

1. Install and activate
2. Check homepage source - you should see JSON-LD schema
3. Test with [Google Rich Results Test](https://search.google.com/test/rich-results)
4. Add more entities and page mappings as needed
