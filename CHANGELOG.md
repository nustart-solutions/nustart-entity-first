# Changelog

## [2.3.10] - 2026-02-01

### Fixed
- **Schema output:** Fixed JSON array generation by resetting array keys with `array_values()` after filtering. This resolves an issue where the parent entity node was generated but dropped from the final output due to non-sequential array keys.

## [2.3.9] - 2026-02-01

### Added
- **Deep debugging:** Added inspection of generated parent schema properties and JSON encoding validation

## [2.3.8] - 2026-02-01

### Added
- **Debug output:** Added detailed HTML comments to schema output to trace parent entity logic and diagnose "Unspecified Type" errors

## [2.3.7] - 2026-02-01

### Fixed
- **Schema generation:** Fixed array handling for ACF Relationship fields (specifically `parent_entity`) to ensure parent entities are correctly added to the schema graph even when ACF returns them as an array of objects/IDs.

## [2.3.6] - 2026-02-01

### Fixed
- **Schema generation:** Fixed "Unspecified Type" error by standardizing entity ID handling (resolves issue where ACF returns objects instead of IDs, causing parent entities to be missed)

## [2.3.5] - 2026-02-01

### Fixed
- **Plugin activation error:** Added error handling for plugin update checker to prevent fatal errors during activation
- **PHP syntax error:** Moved use statement outside try-catch block

## [2.3.4] - 2026-02-01

### Fixed
- **Parent entity detection:** Improved logic to check all entities (primary, about, mentions) for parent relationships, not just primary entity

## [2.3.3] - 2026-02-01

### Fixed
- **Parent entity inclusion:** Child service pages now automatically include parent service entities in the schema graph when referenced via `isPartOf`, resolving "Unspecified Type" validation errors

## [2.3.2] - 2026-02-01

### Fixed
- **WebPage about property:** Validates entity_id before including, prevents incomplete `@id` references
- **WebSite publisher:** Uses actual organization entity instead of hardcoded `org-nustart`
- **Person isPartOf:** Removed invalid `isPartOf` property from Person entities (use `worksFor` instead)
- **Entity relationships:** All `isPartOf`, `worksFor`, and `hasOfferCatalog` now validate entity_id before creating references
- **sameAs validation:** Filters out non-URL values (like post IDs) from sameAs property
- All fixes resolve schema.org validation errors for incomplete or invalid entity references

## [2.3.1] - 2026-02-01

### Fixed
- **Entity posts no longer publicly accessible:** Changed post type to `public => false` and `publicly_queryable => false`
- Entity URLs like `/entity/service-name/` now return 404
- Entities remain accessible in admin and via REST API for schema generation
- Entity taxonomy also made non-public

## [2.3.0] - 2026-02-01

### 🎉 Setup Wizard & Settings Page

This release adds a user-friendly setup wizard and settings page for managing organization entities without requiring JSON knowledge.

### Added
- **Setup Wizard:** Automatic wizard on first activation to create organization entity
- **Settings Page:** Settings → Entity SEO for editing organization details anytime
- **Dual Storage:** Settings synced between WordPress options and entity post
- **Auto-mapping:** Homepage automatically mapped to organization entity
- **Collapsible Sections:** Address and social profiles are optional and collapsible
- **Skip Option:** Users can skip setup and create entities manually

### Changed
- **Schema Generator:** Exclude `@context` from schema_json merge to prevent duplicates
- **Service Provider:** Removed hardcoded `org-nustart` default for services

### Benefits
- ✅ **Zero-config setup** for basic sites
- ✅ **No JSON knowledge required** for organization setup
- ✅ **User-friendly forms** with clear labels and descriptions
- ✅ **Flexible** - Can still edit JSON directly for advanced users
- ✅ **Revisitable** - Settings can be updated anytime

## [2.2.1] - 2026-02-01

### Fixed
- **Vendor directory included:** Fixed activation error by including `vendor/` directory in repository
- Plugin Update Checker library is now properly distributed with the plugin

## [2.2.0] - 2026-02-01

### 🎉 Removed Legacy Infrastructure & ACF Free Compatible

This release removes all legacy custom table code and makes the plugin compatible with ACF Free (no Pro license required).

### Removed
- **Legacy custom tables:** Removed `ns_entities` and `ns_page_entity_map` table creation
- **Legacy model classes:** Deleted `NS_Entity_Model`, `NS_Page_Entity_Map_Model`, `NS_Schema_Generator`
- **Migration system:** Removed `NS_Entity_Migration` class (no longer needed)
- **Legacy REST API:** Removed old REST API endpoints
- **ACF Pro dependency:** Changed repeater field to textarea for ACF Free compatibility

### Changed
- **Same As field:** Changed from repeater (ACF Pro) to textarea (ACF Free compatible)
  - Now accepts one URL per line instead of repeater rows
  - Schema generator updated to parse newline-separated URLs
- **Plugin activation:** Simplified to only register post types (no table creation)
- **Plugin header:** Added `Requires Plugins: advanced-custom-fields` for WordPress 6.5+

### Added
- **ACF dependency check:** Plugin now checks for ACF on activation and shows admin notice if missing
- **Graceful degradation:** Schema output disabled if ACF is not available
- **Installation link:** Admin notice includes direct link to install ACF from WordPress.org

### Benefits
- ✅ Works with **ACF Free** (no Pro license needed)
- ✅ Cleaner codebase (removed 5 legacy files)
- ✅ Simpler activation (no database tables)
- ✅ Better user experience (clear ACF requirement messaging)
- ✅ Easier maintenance (single architecture, no migration code)

## [2.1.0] - 2026-01-31

### Changed
- **Simplified ACF Schema Properties:** Replaced rigid flexible content layouts with single `schema_json` textarea field
- **Maximum Flexibility:** Store complete schema.org JSON directly, no field constraints
- **Hierarchical Relationships:** Added proper `isPartOf` and `hasOfferCatalog` support for parent/child services
- **Auto-Generated Relationships:** Schema generator automatically adds `provider`, `worksFor`, and category based on parent entity

### Added
- **Python Helper Script:** `execution/create_service.py` for easy hierarchical service creation
- Support for `additionalProperty` arrays in schema JSON
- Automatic child service detection and `hasOfferCatalog` generation
- Category field auto-populated from parent entity name

### Benefits
- ✅ Store ANY schema.org properties without code changes
- ✅ Perfect for complex service specifications
- ✅ Proper Knowledge Graph hierarchies (Organization → Service → Sub-Service)
- ✅ Python scripts can write complex JSON directly

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
