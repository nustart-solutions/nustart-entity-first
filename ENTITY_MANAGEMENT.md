# Entity Management via REST API

Quick reference for managing entities using the REST API.

## Setup

Your `.env` file already has the credentials:
```
WP_API_URL=https://nustart.solutions/wp-json
WP_API_USER=clients@nustart.solutions
WP_API_PASSWORD=VkDt 5Xyr QBAy bVn9 gwwp aVMa
```

## Create Entity

### Create a Service
```bash
python execution/create_entity.py \
  --entity-id "service-ada-compliance" \
  --type "Service" \
  --name "ADA Compliance Services" \
  --description "Full WCAG 2.1 compliance audits and remediation" \
  --service-type "Accessibility Compliance"
```

### Create a Person
```bash
python execution/create_entity.py \
  --entity-id "person-john-doe" \
  --type "Person" \
  --name "John Doe" \
  --job-title "WordPress Developer" \
  --linkedin "https://linkedin.com/in/johndoe"
```

## Map URL to Entity

### Map a Service Page
```bash
python execution/map_url_to_entity.py \
  --url "https://nustart.solutions/services/ada-compliance" \
  --page-type "service" \
  --primary-entity "service-ada-compliance" \
  --about "service-ada-compliance,org-nustart"
```

### Map with FAQs
```bash
python execution/map_url_to_entity.py \
  --url "https://nustart.solutions/services/emergency" \
  --page-type "service" \
  --primary-entity "service-emergency" \
  --faq "What is emergency support?|24/7 WordPress fixes;How fast?|Under 2 hours"
```

## REST API Endpoints

All endpoints are at: `https://nustart.solutions/wp-json/nustart-entity/v1/`

- `POST /entities` - Create entity
- `GET /entities` - List all entities
- `GET /entities/{entity_id}` - Get specific entity
- `PUT /entities/{entity_id}` - Update entity
- `DELETE /entities/{entity_id}` - Delete entity
- `POST /page-mappings` - Create page mapping
- `GET /page-mappings` - List all mappings
- `GET /page-mappings/by-url?url=...` - Get mapping by URL

## After Creating

Visit the URL and view source to see the schema output!
