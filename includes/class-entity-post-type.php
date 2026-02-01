<?php
/**
 * Entity Post Type Registration
 * Registers ns_entity custom post type and ns_entity_type taxonomy
 */
class NS_Entity_Post_Type
{
    public function __construct()
    {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_taxonomy']);
    }

    /**
     * Register ns_entity custom post type
     */
    public function register_post_type()
    {
        $labels = [
            'name' => 'Entities',
            'singular_name' => 'Entity',
            'menu_name' => 'Entities',
            'add_new' => 'Add New Entity',
            'add_new_item' => 'Add New Entity',
            'edit_item' => 'Edit Entity',
            'new_item' => 'New Entity',
            'view_item' => 'View Entity',
            'search_items' => 'Search Entities',
            'not_found' => 'No entities found',
            'not_found_in_trash' => 'No entities found in trash',
            'all_items' => 'All Entities',
        ];

        $args = [
            'labels' => $labels,
            'public' => false, // Not publicly accessible on frontend
            'publicly_queryable' => false, // No frontend URLs
            'show_ui' => true, // Show in admin
            'show_in_menu' => true, // Show in admin menu
            'show_in_rest' => true, // Enable REST API for programmatic access
            'rest_base' => 'ns_entity',
            'query_var' => false, // No query var needed
            'rewrite' => false, // No URL rewriting
            'capability_type' => 'post',
            'has_archive' => false, // No archive page
            'hierarchical' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-networking',
            'supports' => ['title', 'editor', 'thumbnail', 'revisions', 'custom-fields'],
            'taxonomies' => ['ns_entity_type'],
        ];

        register_post_type('ns_entity', $args);
    }

    /**
     * Register ns_entity_type taxonomy
     */
    public function register_taxonomy()
    {
        $labels = [
            'name' => 'Entity Types',
            'singular_name' => 'Entity Type',
            'search_items' => 'Search Entity Types',
            'all_items' => 'All Entity Types',
            'parent_item' => 'Parent Entity Type',
            'parent_item_colon' => 'Parent Entity Type:',
            'edit_item' => 'Edit Entity Type',
            'update_item' => 'Update Entity Type',
            'add_new_item' => 'Add New Entity Type',
            'new_item_name' => 'New Entity Type Name',
            'menu_name' => 'Entity Types',
        ];

        $args = [
            'labels' => $labels,
            'hierarchical' => true,
            'public' => false, // Not publicly accessible
            'show_ui' => true, // Show in admin
            'show_admin_column' => true,
            'show_in_rest' => true, // Enable REST API
            'query_var' => false,
            'rewrite' => false, // No URL rewriting
        ];

        register_taxonomy('ns_entity_type', 'ns_entity', $args);

        // Register default entity types
        $this->register_default_terms();
    }

    /**
     * Register default entity type terms
     */
    private function register_default_terms()
    {
        $default_types = [
            'Organization',
            'Person',
            'Service',
            'Place',
            'LocalBusiness',
            'Thing',
        ];

        foreach ($default_types as $type) {
            if (!term_exists($type, 'ns_entity_type')) {
                wp_insert_term($type, 'ns_entity_type');
            }
        }
    }
}

// Initialize
new NS_Entity_Post_Type();
