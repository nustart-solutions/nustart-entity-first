<?php
/**
 * Settings Page for Entity-First SEO
 * 
 * Provides a user-friendly interface for managing organization entity settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class NS_Entity_Settings_Page
{
    /**
     * Organization entity post ID
     */
    private $org_entity_id = null;

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_ns_entity_save_settings', [$this, 'save_settings']);
    }

    /**
     * Add settings page to WordPress admin
     */
    public function add_settings_page()
    {
        add_options_page(
            'Entity SEO Settings',
            'Entity SEO',
            'manage_options',
            'ns-entity-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings with WordPress
     */
    public function register_settings()
    {
        // Organization settings
        register_setting('ns_entity_settings', 'ns_entity_org_name');
        register_setting('ns_entity_settings', 'ns_entity_org_type');
        register_setting('ns_entity_settings', 'ns_entity_org_email');
        register_setting('ns_entity_settings', 'ns_entity_org_phone');
        register_setting('ns_entity_settings', 'ns_entity_org_description');
        register_setting('ns_entity_settings', 'ns_entity_org_address');
        register_setting('ns_entity_settings', 'ns_entity_org_social');
        register_setting('ns_entity_settings', 'ns_entity_setup_complete');
    }

    /**
     * Render the settings page
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Get current settings
        $org_name = get_option('ns_entity_org_name', get_bloginfo('name'));
        $org_type = get_option('ns_entity_org_type', 'Organization');
        $org_email = get_option('ns_entity_org_email', get_option('admin_email'));
        $org_phone = get_option('ns_entity_org_phone', '');
        $org_description = get_option('ns_entity_org_description', '');
        $org_address = get_option('ns_entity_org_address', [
            'street' => '',
            'city' => '',
            'state' => '',
            'zip' => '',
            'country' => 'US'
        ]);
        $org_social = get_option('ns_entity_org_social', [
            'linkedin' => '',
            'facebook' => '',
            'twitter' => '',
            'instagram' => ''
        ]);

        // Check if organization entity exists
        $this->org_entity_id = $this->get_organization_entity_id();

        // Include the view
        include NS_ENTITY_PATH . 'includes/views/settings-page.php';
    }

    /**
     * Save settings and sync with entity
     */
    public function save_settings()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('ns_entity_save_settings');

        // Save organization settings
        update_option('ns_entity_org_name', sanitize_text_field($_POST['org_name']));
        update_option('ns_entity_org_type', sanitize_text_field($_POST['org_type']));
        update_option('ns_entity_org_email', sanitize_email($_POST['org_email']));
        update_option('ns_entity_org_phone', sanitize_text_field($_POST['org_phone']));
        update_option('ns_entity_org_description', sanitize_textarea_field($_POST['org_description']));

        // Save address
        update_option('ns_entity_org_address', [
            'street' => sanitize_text_field($_POST['org_address_street']),
            'city' => sanitize_text_field($_POST['org_address_city']),
            'state' => sanitize_text_field($_POST['org_address_state']),
            'zip' => sanitize_text_field($_POST['org_address_zip']),
            'country' => sanitize_text_field($_POST['org_address_country'])
        ]);

        // Save social profiles
        update_option('ns_entity_org_social', [
            'linkedin' => esc_url_raw($_POST['org_social_linkedin']),
            'facebook' => esc_url_raw($_POST['org_social_facebook']),
            'twitter' => esc_url_raw($_POST['org_social_twitter']),
            'instagram' => esc_url_raw($_POST['org_social_instagram'])
        ]);

        // Sync with entity post
        $this->sync_settings_to_entity();

        // Redirect back to settings page with success message
        wp_redirect(add_query_arg('settings-updated', 'true', admin_url('options-general.php?page=ns-entity-settings')));
        exit;
    }

    /**
     * Sync settings to organization entity post
     */
    private function sync_settings_to_entity()
    {
        $org_name = get_option('ns_entity_org_name');
        $org_type = get_option('ns_entity_org_type', 'Organization');
        $org_email = get_option('ns_entity_org_email');
        $org_phone = get_option('ns_entity_org_phone');
        $org_description = get_option('ns_entity_org_description');
        $org_address = get_option('ns_entity_org_address');
        $org_social = get_option('ns_entity_org_social');

        // Get or create organization entity
        $entity_id = $this->get_organization_entity_id();

        if (!$entity_id) {
            $entity_id = $this->create_organization_entity();
        }

        // Update entity post title
        wp_update_post([
            'ID' => $entity_id,
            'post_title' => $org_name
        ]);

        // Update ACF fields
        $entity_slug = 'org-' . sanitize_title($org_name);
        update_field('entity_id', $entity_slug, $entity_id);
        update_field('canonical_url', home_url('/'), $entity_id);
        update_field('entity_status', 'published', $entity_id);

        // Build same_as URLs (one per line)
        $same_as_urls = array_filter(array_values($org_social));
        update_field('same_as', implode("\n", $same_as_urls), $entity_id);

        // Generate schema JSON from settings
        $schema_json = $this->generate_schema_from_settings();
        update_field('schema_json', $schema_json, $entity_id);

        // Map homepage to organization if not already mapped
        $this->map_homepage_to_organization($entity_id);

        return $entity_id;
    }

    /**
     * Generate schema.org JSON from settings
     */
    private function generate_schema_from_settings()
    {
        $org_type = get_option('ns_entity_org_type', 'Organization');
        $org_email = get_option('ns_entity_org_email');
        $org_phone = get_option('ns_entity_org_phone');
        $org_description = get_option('ns_entity_org_description');
        $org_address = get_option('ns_entity_org_address');

        $schema = [
            '@type' => $org_type ?: 'Organization'
        ];

        // Add description
        if (!empty($org_description)) {
            $schema['description'] = $org_description;
        }

        // Add email
        if (!empty($org_email)) {
            $schema['email'] = $org_email;
        }

        // Add address if any field is filled
        if (!empty($org_address['street']) || !empty($org_address['city'])) {
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $org_address['street'],
                'addressLocality' => $org_address['city'],
                'addressRegion' => $org_address['state'],
                'postalCode' => $org_address['zip'],
                'addressCountry' => $org_address['country'] ?: 'US'
            ];
        }

        // Add contact point if phone or email present
        if (!empty($org_phone) || !empty($org_email)) {
            $schema['contactPoint'] = [
                '@type' => 'ContactPoint',
                'contactType' => 'customer service'
            ];

            if (!empty($org_phone)) {
                $schema['contactPoint']['telephone'] = $org_phone;
            }

            if (!empty($org_email)) {
                $schema['contactPoint']['email'] = $org_email;
            }
        }

        return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get organization entity post ID
     */
    private function get_organization_entity_id()
    {
        $args = [
            'post_type' => 'ns_entity',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'tax_query' => [
                [
                    'taxonomy' => 'ns_entity_type',
                    'field' => 'slug',
                    'terms' => 'organization'
                ]
            ],
            'meta_query' => [
                [
                    'key' => 'entity_id',
                    'value' => 'org-',
                    'compare' => 'LIKE'
                ]
            ]
        ];

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            return $query->posts[0]->ID;
        }

        return null;
    }

    /**
     * Create organization entity post
     */
    private function create_organization_entity()
    {
        $org_name = get_option('ns_entity_org_name', get_bloginfo('name'));

        // Create entity post
        $post_id = wp_insert_post([
            'post_title' => $org_name,
            'post_type' => 'ns_entity',
            'post_status' => 'publish'
        ]);

        // Set entity type taxonomy
        wp_set_object_terms($post_id, 'organization', 'ns_entity_type');

        return $post_id;
    }

    /**
     * Map homepage to organization entity
     */
    private function map_homepage_to_organization($entity_id)
    {
        $homepage_id = get_option('page_on_front');

        if (!$homepage_id) {
            return; // No static homepage set
        }

        // Check if already mapped
        $primary_entity = get_field('primary_entity', $homepage_id);

        if (!$primary_entity) {
            update_field('primary_entity', $entity_id, $homepage_id);
            update_field('about_entities', [$entity_id], $homepage_id);
        }
    }
}
