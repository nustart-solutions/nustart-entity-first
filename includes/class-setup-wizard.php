<?php
/**
 * Setup Wizard for Entity-First SEO
 * 
 * Runs on first activation to help users set up their organization entity
 */

if (!defined('ABSPATH')) {
    exit;
}

class NS_Entity_Setup_Wizard
{
    public function __construct()
    {
        add_action('admin_init', [$this, 'redirect_to_setup']);
        add_action('admin_menu', [$this, 'add_setup_page']);
        add_action('admin_post_ns_entity_run_setup', [$this, 'process_setup']);
        add_action('admin_post_ns_entity_skip_setup', [$this, 'skip_setup']);
    }

    /**
     * Redirect to setup wizard if transient is set
     */
    public function redirect_to_setup()
    {
        if (get_transient('ns_entity_show_setup_wizard')) {
            delete_transient('ns_entity_show_setup_wizard');
            wp_safe_redirect(admin_url('admin.php?page=ns-entity-setup'));
            exit;
        }
    }

    /**
     * Add setup page (hidden from menu)
     */
    public function add_setup_page()
    {
        add_submenu_page(
            null, // No parent = hidden from menu
            'Entity SEO Setup',
            'Entity SEO Setup',
            'manage_options',
            'ns-entity-setup',
            [$this, 'render_setup_page']
        );
    }

    /**
     * Render setup wizard page
     */
    public function render_setup_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Pre-fill with defaults
        $org_name = get_bloginfo('name');
        $org_type = 'Organization';
        $org_email = get_option('admin_email');
        $org_phone = '';
        $org_description = get_bloginfo('description');
        $org_address = [
            'street' => '',
            'city' => '',
            'state' => '',
            'zip' => '',
            'country' => 'US'
        ];
        $org_social = [
            'linkedin' => '',
            'facebook' => '',
            'twitter' => '',
            'instagram' => ''
        ];

        // Include the view
        include NS_ENTITY_PATH . 'includes/views/setup-wizard.php';
    }

    /**
     * Process setup wizard submission
     */
    public function process_setup()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('ns_entity_run_setup');

        // Save organization settings
        update_option('ns_entity_org_name', sanitize_text_field($_POST['org_name']));
        update_option('ns_entity_org_type', sanitize_text_field($_POST['org_type'] ?? 'Organization'));
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

        // Create organization entity
        $this->create_organization_entity();

        // Mark setup as complete
        update_option('ns_entity_setup_complete', true);

        // Redirect to success page
        wp_redirect(admin_url('admin.php?page=ns-entity-setup-success'));
        exit;
    }

    /**
     * Skip setup wizard
     */
    public function skip_setup()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('ns_entity_skip_setup');

        // Mark setup as complete (skipped)
        update_option('ns_entity_setup_complete', true);

        // Redirect to entities page
        wp_redirect(admin_url('edit.php?post_type=ns_entity'));
        exit;
    }

    /**
     * Create organization entity from settings
     */
    private function create_organization_entity()
    {
        $org_name = get_option('ns_entity_org_name');
        $org_email = get_option('ns_entity_org_email');
        $org_phone = get_option('ns_entity_org_phone');
        $org_description = get_option('ns_entity_org_description');
        $org_address = get_option('ns_entity_org_address');
        $org_social = get_option('ns_entity_org_social');

        // Create entity post
        $post_id = wp_insert_post([
            'post_title' => $org_name,
            'post_type' => 'ns_entity',
            'post_status' => 'publish'
        ]);

        // Set entity type taxonomy
        wp_set_object_terms($post_id, 'organization', 'ns_entity_type');

        // Update ACF fields
        $entity_slug = 'org-' . sanitize_title($org_name);
        update_field('entity_id', $entity_slug, $post_id);
        update_field('canonical_url', home_url('/'), $post_id);
        update_field('entity_status', 'published', $post_id);

        // Build same_as URLs (one per line)
        $same_as_urls = array_filter(array_values($org_social));
        update_field('same_as', implode("\n", $same_as_urls), $post_id);

        // Generate schema JSON from settings
        $schema_json = $this->generate_schema_from_settings();
        update_field('schema_json', $schema_json, $post_id);

        // Map homepage to organization
        $this->map_homepage_to_organization($post_id);

        return $post_id;
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
     * Map homepage to organization entity
     */
    private function map_homepage_to_organization($entity_id)
    {
        $homepage_id = get_option('page_on_front');

        if (!$homepage_id) {
            return; // No static homepage set
        }

        // Set primary entity and about entities
        update_field('primary_entity', $entity_id, $homepage_id);
        update_field('about_entities', [$entity_id], $homepage_id);
    }
}
