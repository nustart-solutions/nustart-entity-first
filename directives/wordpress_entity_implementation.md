# WordPress Entity-First SEO Implementation

## Architecture: Two-Table Design

### Table A: `ns_entities` (Knowledge Layer)
Central repository of all entities in your system. One row = one real-world entity.

### Table B: `ns_page_entity_map` (Page Layer)
Maps URLs/pages to entities. One row = one URL's entity configuration.

---

## Table Schemas

### ns_entities

```sql
CREATE TABLE {prefix}ns_entities (
    entity_id VARCHAR(50) PRIMARY KEY,           -- UUID or stable slug (e.g., 'org-nustart')
    entity_type VARCHAR(50) NOT NULL,            -- Organization, Person, Service, Place, Event, Product, WebSite
    name VARCHAR(255) NOT NULL,                  -- Display name
    slug VARCHAR(255) NOT NULL UNIQUE,           -- URL-friendly slug
    canonical_url VARCHAR(500),                  -- The page representing this entity
    same_as JSON,                                -- Array of external profiles ["https://twitter.com/...", "https://linkedin.com/..."]
    parent_entity_id VARCHAR(50),                -- FK to another entity (e.g., Service → Organization)
    properties JSON,                             -- Type-specific fields (see examples below)
    status ENUM('draft', 'published') DEFAULT 'draft',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY entity_type (entity_type),
    KEY slug (slug),
    KEY parent_entity (parent_entity_id),
    KEY status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### ns_page_entity_map

```sql
CREATE TABLE {prefix}ns_page_entity_map (
    page_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wp_post_id BIGINT UNSIGNED,                  -- Nullable; links to wp_posts if applicable
    url VARCHAR(500) NOT NULL UNIQUE,            -- Full URL (do not rely on slug alone)
    page_type VARCHAR(50) NOT NULL,              -- home, hub, service, location, tool, blog_post, snippet
    primary_entity_id VARCHAR(50),               -- FK to ns_entities (the main entity this page is about)
    schema_graph JSON,                           -- JSON config for schema nodes to emit
    about_entity_ids JSON,                       -- Array of entity_ids this page is about
    mentions_entity_ids JSON,                    -- Array of entity_ids mentioned on page
    faq_data JSON,                               -- FAQ schema data
    canonical_url VARCHAR(500),                  -- Override canonical (optional)
    robots VARCHAR(100) DEFAULT 'index,follow',  -- Robots directive
    sitemap_include BOOLEAN DEFAULT TRUE,
    title_override VARCHAR(255),                 -- Override title (if not using Rank Math/Yoast)
    meta_description_override TEXT,              -- Override meta description
    last_generated_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY wp_post_id (wp_post_id),
    KEY page_type (page_type),
    KEY primary_entity (primary_entity_id),
    FOREIGN KEY (primary_entity_id) REFERENCES {prefix}ns_entities(entity_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Entity Properties Examples

### Organization Entity
```json
{
  "logo": "https://nustart.solutions/logo.png",
  "address": {
    "streetAddress": "21163 82 A Ave",
    "addressLocality": "Langley",
    "addressRegion": "BC",
    "postalCode": "V2Y 0B5",
    "addressCountry": "CA"
  },
  "contactPoint": {
    "telephone": "+1-555-1234",
    "email": "anne@nustart.solutions",
    "contactType": "customer support"
  },
  "foundingDate": "2020",
  "areaServed": ["CA", "US", "GB"]
}
```

### Person Entity
```json
{
  "jobTitle": "Founder & WordPress Expert",
  "email": "anne@nustart.solutions",
  "image": "https://nustart.solutions/team/anne.jpg",
  "worksFor": "org-nustart",
  "knowsAbout": ["WordPress", "SEO", "ADA Compliance"]
}
```

### Service Entity
```json
{
  "serviceType": "WordPress Support",
  "provider": "org-nustart",
  "areaServed": ["CA", "US", "GB"],
  "description": "24/7 WordPress emergency fixes and maintenance"
}
```

---

## WordPress Plugin Implementation

### 1. Activation Hook (Create Tables)

```php
<?php
function ns_entity_activate() {
    global $wpdb;
    $prefix = $wpdb->prefix;
    $charset = $wpdb->get_charset_collate();
    
    // Table 1: ns_entities
    $sql_entities = "CREATE TABLE {$prefix}ns_entities (
        entity_id VARCHAR(50) PRIMARY KEY,
        entity_type VARCHAR(50) NOT NULL,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        canonical_url VARCHAR(500),
        same_as JSON,
        parent_entity_id VARCHAR(50),
        properties JSON,
        status ENUM('draft', 'published') DEFAULT 'draft',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY entity_type (entity_type),
        KEY slug (slug),
        KEY parent_entity (parent_entity_id),
        KEY status (status)
    ) $charset;";
    
    // Table 2: ns_page_entity_map
    $sql_page_map = "CREATE TABLE {$prefix}ns_page_entity_map (
        page_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        wp_post_id BIGINT UNSIGNED,
        url VARCHAR(500) NOT NULL UNIQUE,
        page_type VARCHAR(50) NOT NULL,
        primary_entity_id VARCHAR(50),
        schema_graph JSON,
        about_entity_ids JSON,
        mentions_entity_ids JSON,
        faq_data JSON,
        canonical_url VARCHAR(500),
        robots VARCHAR(100) DEFAULT 'index,follow',
        sitemap_include BOOLEAN DEFAULT TRUE,
        title_override VARCHAR(255),
        meta_description_override TEXT,
        last_generated_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY wp_post_id (wp_post_id),
        KEY page_type (page_type),
        KEY primary_entity (primary_entity_id),
        FOREIGN KEY (primary_entity_id) REFERENCES {$prefix}ns_entities(entity_id) ON DELETE SET NULL
    ) $charset;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_entities);
    dbDelta($sql_page_map);
}
register_activation_hook(__FILE__, 'ns_entity_activate');
```

### 2. Entity Model Class

```php
<?php
class NS_Entity_Model {
    private $wpdb;
    private $table;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'ns_entities';
    }
    
    /**
     * Create or update an entity
     */
    public function upsert($entity_id, $data) {
        $existing = $this->get($entity_id);
        
        $prepared = [
            'entity_id' => $entity_id,
            'entity_type' => $data['entity_type'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'canonical_url' => $data['canonical_url'] ?? null,
            'same_as' => json_encode($data['same_as'] ?? []),
            'parent_entity_id' => $data['parent_entity_id'] ?? null,
            'properties' => json_encode($data['properties'] ?? []),
            'status' => $data['status'] ?? 'draft'
        ];
        
        if ($existing) {
            // Update
            $this->wpdb->update(
                $this->table,
                $prepared,
                ['entity_id' => $entity_id],
                ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'],
                ['%s']
            );
        } else {
            // Insert
            $this->wpdb->insert($this->table, $prepared);
        }
        
        return $entity_id;
    }
    
    /**
     * Get entity by ID
     */
    public function get($entity_id) {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE entity_id = %s", $entity_id),
            ARRAY_A
        );
        
        if ($result) {
            // Decode JSON fields
            $result['same_as'] = json_decode($result['same_as'], true);
            $result['properties'] = json_decode($result['properties'], true);
        }
        
        return $result;
    }
    
    /**
     * Get entities by type
     */
    public function get_by_type($entity_type, $status = 'published') {
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE entity_type = %s AND status = %s",
                $entity_type,
                $status
            ),
            ARRAY_A
        );
        
        foreach ($results as &$result) {
            $result['same_as'] = json_decode($result['same_as'], true);
            $result['properties'] = json_decode($result['properties'], true);
        }
        
        return $results;
    }
    
    /**
     * Generate schema.org JSON-LD for entity
     */
    public function to_schema($entity_id) {
        $entity = $this->get($entity_id);
        if (!$entity) return null;
        
        $schema = [
            '@type' => ucfirst($entity['entity_type']),
            '@id' => $entity['canonical_url'] . '#' . $entity['entity_id'],
            'name' => $entity['name'],
            'url' => $entity['canonical_url']
        ];
        
        // Add sameAs
        if (!empty($entity['same_as'])) {
            $schema['sameAs'] = $entity['same_as'];
        }
        
        // Merge properties
        if (!empty($entity['properties'])) {
            $schema = array_merge($schema, $entity['properties']);
        }
        
        return $schema;
    }
}
```

### 3. Page Entity Map Model Class

```php
<?php
class NS_Page_Entity_Map_Model {
    private $wpdb;
    private $table;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'ns_page_entity_map';
    }
    
    /**
     * Create or update page entity mapping
     */
    public function upsert($url, $data) {
        $existing = $this->get_by_url($url);
        
        $prepared = [
            'url' => $url,
            'wp_post_id' => $data['wp_post_id'] ?? null,
            'page_type' => $data['page_type'],
            'primary_entity_id' => $data['primary_entity_id'] ?? null,
            'schema_graph' => json_encode($data['schema_graph'] ?? []),
            'about_entity_ids' => json_encode($data['about_entity_ids'] ?? []),
            'mentions_entity_ids' => json_encode($data['mentions_entity_ids'] ?? []),
            'faq_data' => json_encode($data['faq_data'] ?? []),
            'canonical_url' => $data['canonical_url'] ?? null,
            'robots' => $data['robots'] ?? 'index,follow',
            'sitemap_include' => $data['sitemap_include'] ?? true,
            'title_override' => $data['title_override'] ?? null,
            'meta_description_override' => $data['meta_description_override'] ?? null,
        ];
        
        if ($existing) {
            // Update
            $this->wpdb->update(
                $this->table,
                $prepared,
                ['url' => $url]
            );
            return $existing['page_id'];
        } else {
            // Insert
            $this->wpdb->insert($this->table, $prepared);
            return $this->wpdb->insert_id;
        }
    }
    
    /**
     * Get page config by URL
     */
    public function get_by_url($url) {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE url = %s", $url),
            ARRAY_A
        );
        
        if ($result) {
            // Decode JSON fields
            $result['schema_graph'] = json_decode($result['schema_graph'], true);
            $result['about_entity_ids'] = json_decode($result['about_entity_ids'], true);
            $result['mentions_entity_ids'] = json_decode($result['mentions_entity_ids'], true);
            $result['faq_data'] = json_decode($result['faq_data'], true);
        }
        
        return $result;
    }
    
    /**
     * Get page config by WordPress post ID
     */
    public function get_by_post_id($post_id) {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE wp_post_id = %d", $post_id),
            ARRAY_A
        );
        
        if ($result) {
            $result['schema_graph'] = json_decode($result['schema_graph'], true);
            $result['about_entity_ids'] = json_decode($result['about_entity_ids'], true);
            $result['mentions_entity_ids'] = json_decode($result['mentions_entity_ids'], true);
            $result['faq_data'] = json_decode($result['faq_data'], true);
        }
        
        return $result;
    }
}
```

### 4. Schema Generator Class

```php
<?php
class NS_Schema_Generator {
    private $entity_model;
    private $page_model;
    
    public function __construct() {
        $this->entity_model = new NS_Entity_Model();
        $this->page_model = new NS_Page_Entity_Map_Model();
    }
    
    /**
     * Generate complete schema graph for a URL
     */
    public function generate_for_url($url) {
        $page = $this->page_model->get_by_url($url);
        if (!$page) return null;
        
        $graph = [];
        
        // Add primary entity
        if ($page['primary_entity_id']) {
            $primary_schema = $this->entity_model->to_schema($page['primary_entity_id']);
            if ($primary_schema) {
                $graph[] = $primary_schema;
            }
        }
        
        // Add about entities
        if (!empty($page['about_entity_ids'])) {
            foreach ($page['about_entity_ids'] as $entity_id) {
                $schema = $this->entity_model->to_schema($entity_id);
                if ($schema) {
                    $graph[] = $schema;
                }
            }
        }
        
        // Add WebPage entity
        $graph[] = [
            '@type' => 'WebPage',
            '@id' => $url . '#webpage',
            'url' => $url,
            'name' => $page['title_override'] ?? get_the_title($page['wp_post_id']),
            'description' => $page['meta_description_override'],
            'isPartOf' => ['@id' => home_url() . '#website'],
            'about' => $page['primary_entity_id'] ? ['@id' => $this->entity_model->get($page['primary_entity_id'])['canonical_url'] . '#' . $page['primary_entity_id']] : null
        ];
        
        // Add FAQPage if FAQ data exists
        if (!empty($page['faq_data'])) {
            $graph[] = [
                '@type' => 'FAQPage',
                'mainEntity' => $this->format_faq_schema($page['faq_data'])
            ];
        }
        
        // Wrap in @graph
        return [
            '@context' => 'https://schema.org',
            '@graph' => array_filter($graph) // Remove nulls
        ];
    }
    
    private function format_faq_schema($faq_data) {
        $questions = [];
        foreach ($faq_data as $faq) {
            $questions[] = [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer']
                ]
            ];
        }
        return $questions;
    }
    
    /**
     * Output schema in <head>
     */
    public function output_schema() {
        $url = home_url($_SERVER['REQUEST_URI']);
        $schema = $this->generate_for_url($url);
        
        if ($schema) {
            echo '<script type="application/ld+json">' . "\n";
            echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            echo "\n" . '</script>' . "\n";
        }
    }
}

// Hook into wp_head
add_action('wp_head', function() {
    $generator = new NS_Schema_Generator();
    $generator->output_schema();
}, 1); // Priority 1 to output early
```

---

## Usage Examples

### Example 1: Seed Organization Entity

```php
$entity_model = new NS_Entity_Model();

$entity_model->upsert('org-nustart', [
    'entity_type' => 'Organization',
    'name' => 'NuStart Solutions',
    'slug' => 'nustart-solutions',
    'canonical_url' => 'https://nustart.solutions',
    'same_as' => [
        'https://twitter.com/nustartsolution',
        'https://www.linkedin.com/company/nustart-solutions'
    ],
    'properties' => [
        'logo' => 'https://nustart.solutions/logo.png',
        'address' => [
            'streetAddress' => '21163 82 A Ave',
            'addressLocality' => 'Langley',
            'addressRegion' => 'BC',
            'postalCode' => 'V2Y 0B5',
            'addressCountry' => 'CA'
        ],
        'email' => 'anne@nustart.solutions',
        'foundingDate' => '2020'
    ],
    'status' => 'published'
]);
```

### Example 2: Seed Person Entity

```php
$entity_model->upsert('person-anne', [
    'entity_type' => 'Person',
    'name' => 'Anne Allen',
    'slug' => 'anne-allen',
    'canonical_url' => 'https://nustart.solutions/about',
    'same_as' => [
        'https://www.linkedin.com/in/anneallen',
        'https://twitter.com/anneallen'
    ],
    'parent_entity_id' => 'org-nustart',
    'properties' => [
        'jobTitle' => 'Founder & WordPress Expert',
        'email' => 'anne@nustart.solutions',
        'image' => 'https://nustart.solutions/team/anne.jpg',
        'worksFor' => ['@id' => 'https://nustart.solutions#org-nustart'],
        'knowsAbout' => ['WordPress', 'SEO', 'ADA Compliance', 'AI Visibility']
    ],
    'status' => 'published'
]);
```

### Example 3: Map Homepage to Entities

```php
$page_model = new NS_Page_Entity_Map_Model();

$page_model->upsert('https://nustart.solutions/', [
    'wp_post_id' => get_option('page_on_front'), // Homepage post ID
    'page_type' => 'home',
    'primary_entity_id' => 'org-nustart',
    'about_entity_ids' => ['org-nustart', 'person-anne'],
    'mentions_entity_ids' => [],
    'schema_graph' => [
        'include' => ['Organization', 'WebSite', 'WebPage']
    ],
    'faq_data' => [
        ['question' => 'What services does NuStart offer?', 'answer' => 'WordPress support...'],
        ['question' => 'Where is NuStart located?', 'answer' => 'Langley, BC, Canada']
    ],
    'robots' => 'index,follow',
    'sitemap_include' => true
]);
```

---

## Integration with Existing pSEO Plugin

You can integrate this with your existing `wp_pseo_pages` table:

```php
// In your pSEO page generation
$pseo_page = PSEO_Model::get_by_slug('wordpress-plugin-gravity-forms');

// Map to entity system
$page_model->upsert($pseo_page['url'], [
    'wp_post_id' => $pseo_page['post_id'],
    'page_type' => 'hub', // or 'spoke'
    'primary_entity_id' => 'product-gravity-forms',
    'about_entity_ids' => ['product-gravity-forms', 'org-gravityforms'],
    'mentions_entity_ids' => ['org-nustart'], // You mention it in content
    'robots' => 'index,follow',
    'sitemap_include' => true
]);
```

---

## Next Steps

1. Add these table creation functions to your plugin activation
2. Create the model classes
3. Seed your core entities (Organization, Person)
4. Integrate schema generator into `wp_head`
5. Map existing pages to entities
6. Test schema with Google Rich Results Test

This architecture gives you:
- ✅ Clean separation (entities vs pages)
- ✅ Reusable entities across multiple pages
- ✅ Flexible schema graph generation
- ✅ Easy entity relationship management
