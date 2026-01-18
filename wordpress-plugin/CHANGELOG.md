# Changelog

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
