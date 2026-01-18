# Create Entity Workflow

## Purpose

Interactive workflow for creating and deploying new entities to the WordPress site with proper schema.org markup.

## Workflow Steps

### Step 1: Information Gathering

User provides:
- **Entity information** (name, description, URLs, etc.)
- **Suggested entity type** (Organization, Person, Service, Product, etc.)
- **Social media profiles** (LinkedIn, Twitter, Instagram, GitHub, etc.)
- **Additional properties** (job title, service type, contact info, etc.)

### Step 2: Schema Generation & Review

Agent:
1. Reviews the provided information
2. Determines appropriate schema.org type
3. Generates a complete schema JSON preview
4. Presents schema to user for review

User:
- Reviews the generated schema
- Requests modifications if needed
- Approves when satisfied

### Step 3: Entity Creation

Agent:
1. Creates entity via REST API using `execution/create_entity.py`
2. Confirms successful creation
3. Retrieves entity ID for reference

### Step 4: Page Assignment

User provides:
- **Page URL(s)** where entity should appear
- **Relationship type**: `primary`, `about`, or `mentions`

Agent:
1. Maps entity to page(s) using `execution/map_url_to_entity.py`
2. Verifies schema output on live page
3. Confirms entity appears correctly

## Tools Used

### Entity Creation
```bash
python execution/create_entity.py \
  --entity-id "entity-slug" \
  --type "EntityType" \
  --name "Entity Name" \
  --description "Description" \
  --url "https://canonical-url.com" \
  --linkedin "https://linkedin.com/..." \
  --twitter "https://x.com/..." \
  --instagram "https://instagram.com/..." \
  --github "https://github.com/..." \
  --reddit "https://reddit.com/..." \
  --same-as "https://other-profile.com,https://another.com"
```

### Page Mapping
```bash
python execution/map_url_to_entity.py \
  --url "https://nustart.solutions/page" \
  --primary-entity "entity-slug" \
  --about "entity1,entity2" \
  --mentions "entity3,entity4"
```

## Supported Entity Types

### Organization
Properties:
- `name`, `description`, `url`
- `logo`, `email`, `telephone`
- `sameAs` (social profiles)
- `areaServed`, `contactPoint`

### Person
Properties:
- `name`, `description`, `url`
- `jobTitle`, `email`
- `sameAs` (social profiles)
- `worksFor` (parent organization)
- `knowsAbout`

### Service
Properties:
- `name`, `description`, `url`
- `serviceType`
- `provider` (parent organization)

### Product
Properties:
- `name`, `description`, `url`
- `image`, `brand`
- `manufacturer`

## Schema.org Types Reference

Common types:
- `Organization` - Companies, businesses
- `Person` - Individuals, authors, team members
- `Service` - Services offered
- `Product` - Products sold
- `Article` - Blog posts, articles
- `WebPage` - Generic web pages
- `LocalBusiness` - Physical business locations
- `SoftwareApplication` - Software products
- `Course` - Educational courses
- `Event` - Events, webinars

## Example Workflow

### Example 1: Creating a Service Entity

**User provides:**
```
Name: WordPress Emergency Support
Type: Service
Description: 24/7 emergency WordPress fixes and security response
Service Type: Emergency Support
```

**Agent generates schema:**
```json
{
  "@type": "Service",
  "@id": "https://nustart.solutions/#service-emergency",
  "name": "WordPress Emergency Support",
  "description": "24/7 emergency WordPress fixes and security response",
  "serviceType": "Emergency Support",
  "provider": {
    "@id": "https://nustart.solutions/#org-nustart"
  }
}
```

**User approves, agent creates:**
```bash
python execution/create_entity.py \
  --entity-id "service-emergency" \
  --type "Service" \
  --name "WordPress Emergency Support" \
  --description "24/7 emergency WordPress fixes and security response" \
  --service-type "Emergency Support" \
  --parent "org-nustart"
```

**User assigns to page:**
```
URL: https://nustart.solutions/wordpress-emergency-support
Relationship: primary
```

**Agent maps:**
```bash
python execution/map_url_to_entity.py \
  --url "https://nustart.solutions/wordpress-emergency-support" \
  --primary-entity "service-emergency" \
  --about "service-emergency,org-nustart"
```

### Example 2: Creating a Person Entity

**User provides:**
```
Name: John Smith
Type: Person
Job Title: Senior WordPress Developer
LinkedIn: https://linkedin.com/in/johnsmith
GitHub: https://github.com/johnsmith
Email: john@nustart.solutions
```

**Agent generates schema:**
```json
{
  "@type": "Person",
  "@id": "https://nustart.solutions/team/john-smith#person-john",
  "name": "John Smith",
  "jobTitle": "Senior WordPress Developer",
  "email": "john@nustart.solutions",
  "sameAs": [
    "https://linkedin.com/in/johnsmith",
    "https://github.com/johnsmith"
  ],
  "worksFor": {
    "@id": "https://nustart.solutions/#org-nustart"
  }
}
```

**User approves and assigns to team page**

## Verification

After entity creation and page mapping:
1. Visit the page URL
2. View page source
3. Find `<script type="application/ld+json">`
4. Verify entity appears in `@graph` array
5. Validate with Google Rich Results Test

## Notes

- Entity IDs should be kebab-case slugs (e.g., `service-emergency`, `person-john`)
- Use semantic versioning for plugin updates
- Always update `CHANGELOG.md` when modifying entity structure
- Entities can be reused across multiple pages
- Use `primary` for the main subject, `about` for topics, `mentions` for references
