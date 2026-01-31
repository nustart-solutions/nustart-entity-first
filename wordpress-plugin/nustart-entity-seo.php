<?php
/**
 * Plugin Name: NuStart Entity-First SEO
 * Plugin URI: https://nustart.solutions
 * Description: Entity-first SEO system with schema.org markup generation (ACF + Custom Post Types)
 * Version: 2.0.0
 * Author: NuStart Solutions
 * Author URI: https://nustart.solutions
 * License: GPL v2 or later
 * Text Domain: nustart-entity-seo
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
if (!defined('NS_ENTITY_VERSION')) {
    define('NS_ENTITY_VERSION', '2.0.0');
}
if (!defined('NS_ENTITY_PATH')) {
    define('NS_ENTITY_PATH', plugin_dir_path(__FILE__));
}
if (!defined('NS_ENTITY_URL')) {
    define('NS_ENTITY_URL', plugin_dir_url(__FILE__));
}

// Require ACF-based classes
require_once NS_ENTITY_PATH . 'includes/class-entity-post-type.php';
require_once NS_ENTITY_PATH . 'includes/acf-fields/entity-core-fields.php';
require_once NS_ENTITY_PATH . 'includes/acf-fields/entity-schema-properties.php';
require_once NS_ENTITY_PATH . 'includes/acf-fields/page-entity-fields.php';
require_once NS_ENTITY_PATH . 'includes/class-schema-generator-acf.php';
require_once NS_ENTITY_PATH . 'includes/class-migration.php';

// Keep old classes for backward compatibility during migration
require_once NS_ENTITY_PATH . 'includes/class-entity-model.php';
require_once NS_ENTITY_PATH . 'includes/class-page-entity-map-model.php';
require_once NS_ENTITY_PATH . 'includes/class-schema-generator.php';
require_once NS_ENTITY_PATH . 'includes/class-rest-api.php';

/**
 * Plugin activation - create tables and seed data
 */
function ns_entity_activate()
{
    global $wpdb;
    $prefix = $wpdb->prefix;
    $charset = $wpdb->get_charset_collate();

    // Keep old tables for backward compatibility during migration
    // Table 1: ns_entities (Knowledge Layer)
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

    // Table 2: ns_page_entity_map (Page Layer)
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
        KEY primary_entity (primary_entity_id)
    ) $charset;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_entities);
    dbDelta($sql_page_map);

    // Seed core entities (to old tables for migration)
    ns_entity_seed_data();

    // Register post types and flush rewrite rules
    $post_type = new NS_Entity_Post_Type();
    $post_type->register_post_type();
    $post_type->register_taxonomy();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'ns_entity_activate');

/**
 * Seed NuStart Solutions entities
 */
function ns_entity_seed_data()
{
    $entity_model = new NS_Entity_Model();

    // Seed Organization: NuStart Solutions
    $entity_model->upsert('org-nustart', [
        'entity_type' => 'Organization',
        'name' => 'NuStart Solutions',
        'slug' => 'nustart-solutions',
        'canonical_url' => 'https://nustart.solutions/',
        'same_as' => [
            'https://www.linkedin.com/company/nustart-solutions'
        ],
        'properties' => [
            'description' => 'NuStart Solutions is a remote-first WordPress and AI visibility consultancy, headquartered in Langley, British Columbia. We help businesses across North America build, secure, optimize, and future-proof their websites — from emergency fixes to long-term optimization.',
            'areaServed' => [
                ['@type' => 'Country', 'name' => 'Canada'],
                ['@type' => 'Country', 'name' => 'United States']
            ],
            'contactPoint' => [
                [
                    '@type' => 'ContactPoint',
                    'contactType' => 'customer support',
                    'telephone' => '+1-778-240-8737',
                    'email' => 'info@nustart.solutions',
                    'availableLanguage' => ['en-CA', 'en-US']
                ]
            ]
        ],
        'status' => 'published'
    ]);

    // Seed Person: Anne Allen
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
            'jobTitle' => 'Founder & Lead WordPress Consultant',
            'email' => 'anne@nustart.solutions',
            'worksFor' => ['@id' => 'https://nustart.solutions/#org-nustart'],
            'knowsAbout' => ['WordPress', 'SEO', 'ADA Compliance', 'AI Visibility']
        ],
        'status' => 'published'
    ]);

    // Map homepage to organization entity
    $page_model = new NS_Page_Entity_Map_Model();
    $page_model->upsert(home_url('/'), [
        'wp_post_id' => get_option('page_on_front'),
        'page_type' => 'home',
        'primary_entity_id' => 'org-nustart',
        'about_entity_ids' => ['org-nustart'],
        'mentions_entity_ids' => ['person-anne'],
        'robots' => 'index,follow',
        'sitemap_include' => true
    ]);
}

/**
 * Output schema in <head>
 */
function ns_entity_output_schema()
{
    // Use ACF-based schema generator
    $generator = new NS_Schema_Generator_ACF();
    $generator->output_schema();
}
add_action('wp_head', 'ns_entity_output_schema', 1);

/**
 * Plugin deactivation
 */
function ns_entity_deactivate()
{
    // Nothing to do on deactivation
}
register_deactivation_hook(__FILE__, 'ns_entity_deactivate');
