# Changelog

## [2.0.0] - 2026-01-31

### 🎉 Major Release: ACF + Custom Post Types Architecture

This is a **major architectural change** that migrates the plugin from custom database tables to WordPress-native ACF + Custom Post Types.

### Added
- **Custom Post Type:** `ns_entity` for managing entities in WordPress admin
- **Taxonomy:** `ns_entity_type` for categorizing entities (Organization, Person, Service, etc.)
- **ACF Field Groups:**
  - Core entity fields (entity_id, canonical_url, same_as, parent_entity, status)
  - Schema properties (flexible content for Organization, Person, Service properties)
  - Page entity mapping fields (primary_entity, about_entities, mentions_entities, FAQ, SEO overrides)
- **Migration System:**
  - WP-CLI commands: `wp nustart-entity migrate`, `wp nustart-entity verify`
  - Admin UI for migration in WordPress dashboard
  - Dry-run mode for safe testing
  - Automatic data migration from old tables to new ACF posts
- **New Schema Generator:** ACF-based schema generator using WordPress posts and ACF fields

### Changed
- **Plugin Version:** 2.0.0
- **Schema Output:** Now uses `NS_Schema_Generator_ACF` instead of table-based generator
- **Entity Management:** Entities now appear in WordPress admin with full UI
- **Data Storage:** Entities stored as `ns_entity` posts with ACF fields instead of custom tables

### Backward Compatibility
- Old custom tables (`ns_entities`, `ns_page_entity_map`) are retained during migration
- Old REST API endpoints still work for backward compatibility
- Migration is non-destructive and can be verified before cleanup

### Migration Instructions
1. Ensure ACF Pro is installed and activated
2. Activate plugin version 2.0.0
3. Run migration via WP-CLI: `wp nustart-entity migrate --dry-run` (test)
4. Execute migration: `wp nustart-entity migrate --execute`
5. Verify: `wp nustart-entity verify`
6. Or use admin UI: Navigate to Entities → Migrate Data

### Benefits
- ✅ WordPress-native entity management UI
- ✅ Built-in revision history
- ✅ Media library integration
- ✅ User permissions and capabilities
- ✅ Better developer experience with WP_Query
- ✅ Automatic REST API endpoints
- ✅ Compatible with WordPress ecosystem

## [1.2.0] - 2026-01-18

### Fixed
- Fixed duplicate entity output in schema graph when entity appears in both `primary_entity_id` and `about_entity_ids`
- Schema generator now tracks added entities and skips duplicates

### Enhanced
- Enhanced `create_entity.py` to support additional social media platforms:
  - Instagram (`--instagram`)
  - GitHub (`--github`)
  - Reddit (`--reddit`)
  - Google Maps (`--google-maps`)
  - Generic URLs via `--same-as` (comma-separated)
- Enhanced `manage_entity_direct.py` with same social media platform support

### Changed
- Removed hardcoded social profiles from plugin seed data
- Entities now managed exclusively via REST API for better flexibility

## [1.1.0] - 2026-01-17

### Added
- REST API endpoints for entity management (`/nustart-entity/v1/entities`)
- REST API endpoints for page-to-entity mapping (`/nustart-entity/v1/page-mappings`)
- Python scripts for entity management via REST API:
  - `create_entity.py` - Create/update entities
  - `map_url_to_entity.py` - Map URLs to entities
- Full CRUD operations via REST API (Create, Read, Update, Delete)
- Authentication via WordPress Application Passwords

### Changed
- Improved entity management workflow using REST API instead of direct database access

## [1.0.0] - 2026-01-17

### Added
- Initial release
- Two-table architecture (ns_entities + ns_page_entity_map)
- Entity Model class for CRUD operations
- Page Entity Map Model class
- Schema Generator class for JSON-LD output
- Auto-seeding of NuStart Solutions organization entity
- Auto-seeding of Anne Allen person entity
- Homepage mapping to organization entity
- Automatic schema output in `<head>` tag
