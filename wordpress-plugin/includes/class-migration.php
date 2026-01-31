<?php
/**
 * Migration Script: Custom Tables → ACF + Custom Post Types
 * Migrates data from ns_entities and ns_page_entity_map to WordPress posts with ACF fields
 */
class NS_Entity_Migration
{
    private $wpdb;
    private $entity_mapping = []; // old_entity_id => new_post_id

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        // Add WP-CLI command if available
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('nustart-entity migrate', [$this, 'cli_migrate']);
            WP_CLI::add_command('nustart-entity verify', [$this, 'cli_verify']);
        }

        // Add admin menu for migration
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    /**
     * Add admin menu for migration
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'edit.php?post_type=ns_entity',
            'Migrate Data',
            'Migrate Data',
            'manage_options',
            'ns-entity-migrate',
            [$this, 'admin_page']
        );
    }

    /**
     * Admin page for migration
     */
    public function admin_page()
    {
        if (isset($_POST['run_migration']) && check_admin_referer('ns_entity_migration')) {
            $dry_run = isset($_POST['dry_run']);
            $result = $this->migrate($dry_run);
            echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
        }

        ?>
        <div class="wrap">
            <h1>Entity Migration: Custom Tables → ACF + Custom Post Types</h1>

            <?php $stats = $this->get_migration_stats(); ?>

            <div class="card">
                <h2>Current Status</h2>
                <table class="widefat">
                    <tr>
                        <th>Entities in old table (ns_entities)</th>
                        <td>
                            <?php echo esc_html($stats['old_entities']); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Entities in new system (ns_entity posts)</th>
                        <td>
                            <?php echo esc_html($stats['new_entities']); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Page mappings in old table</th>
                        <td>
                            <?php echo esc_html($stats['old_mappings']); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Migration needed?</th>
                        <td>
                            <?php echo $stats['needs_migration'] ? '<strong>Yes</strong>' : 'No'; ?>
                        </td>
                    </tr>
                </table>
            </div>

            <?php if ($stats['needs_migration']): ?>
                <div class="card">
                    <h2>Run Migration</h2>
                    <form method="post">
                        <?php wp_nonce_field('ns_entity_migration'); ?>
                        <p>
                            <label>
                                <input type="checkbox" name="dry_run" value="1" checked>
                                Dry Run (preview only, don't save data)
                            </label>
                        </p>
                        <p>
                            <button type="submit" name="run_migration" class="button button-primary">
                                Run Migration
                            </button>
                        </p>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get migration statistics
     */
    private function get_migration_stats()
    {
        $old_entities = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->prefix}ns_entities");
        $new_entities = wp_count_posts('ns_entity');
        $new_entities_count = ($new_entities->publish ?? 0) + ($new_entities->draft ?? 0);
        $old_mappings = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->prefix}ns_page_entity_map");

        return [
            'old_entities' => $old_entities,
            'new_entities' => $new_entities_count,
            'old_mappings' => $old_mappings,
            'needs_migration' => ($old_entities > 0 && $new_entities_count === 0),
        ];
    }

    /**
     * WP-CLI command handler
     */
    public function cli_migrate($args, $assoc_args)
    {
        $dry_run = isset($assoc_args['dry-run']);

        WP_CLI::line('Starting migration...');
        $result = $this->migrate($dry_run);

        if ($result['success']) {
            WP_CLI::success($result['message']);
        } else {
            WP_CLI::error($result['message']);
        }
    }

    /**
     * Main migration function
     */
    public function migrate($dry_run = false)
    {
        // Pre-flight checks
        $checks = $this->preflight_checks();
        if (!$checks['success']) {
            return $checks;
        }

        $mode = $dry_run ? 'DRY RUN' : 'LIVE';
        $log = ["=== Entity Migration ($mode) ===\n"];

        // Step 1: Migrate entities
        $entity_result = $this->migrate_entities($dry_run);
        $log[] = $entity_result['log'];

        // Step 2: Migrate page mappings
        $mapping_result = $this->migrate_page_mappings($dry_run);
        $log[] = $mapping_result['log'];

        $message = implode("\n", $log);

        return [
            'success' => true,
            'message' => $message,
            'entities_migrated' => $entity_result['count'],
            'mappings_migrated' => $mapping_result['count'],
        ];
    }

    /**
     * Pre-flight checks
     */
    private function preflight_checks()
    {
        // Check if ACF Pro is active
        if (!function_exists('acf_add_local_field_group')) {
            return [
                'success' => false,
                'message' => 'ACF Pro is not active. Please activate ACF Pro before migrating.',
            ];
        }

        // Check if old tables exist
        $entities_table = $this->wpdb->get_var(
            "SHOW TABLES LIKE '{$this->wpdb->prefix}ns_entities'"
        );
        if (!$entities_table) {
            return [
                'success' => false,
                'message' => 'Old ns_entities table not found. Nothing to migrate.',
            ];
        }

        return ['success' => true];
    }

    /**
     * Migrate entities from ns_entities table to ns_entity posts
     */
    private function migrate_entities($dry_run = false)
    {
        $entities = $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}ns_entities",
            ARRAY_A
        );

        $count = 0;
        $log = "Migrating {count} entities...\n";

        foreach ($entities as $entity) {
            // Decode JSON fields
            $same_as = json_decode($entity['same_as'], true) ?? [];
            $properties = json_decode($entity['properties'], true) ?? [];

            if (!$dry_run) {
                // Create post
                $post_id = wp_insert_post([
                    'post_type' => 'ns_entity',
                    'post_title' => $entity['name'],
                    'post_status' => $entity['status'] === 'published' ? 'publish' : 'draft',
                    'post_name' => $entity['slug'],
                ]);

                if (is_wp_error($post_id)) {
                    $log .= "  ERROR: Failed to create post for {$entity['entity_id']}\n";
                    continue;
                }

                // Set taxonomy term
                wp_set_object_terms($post_id, $entity['entity_type'], 'ns_entity_type');

                // Set ACF fields
                update_field('entity_id', $entity['entity_id'], $post_id);
                update_field('entity_status', $entity['status'], $post_id);
                update_field('canonical_url', $entity['canonical_url'], $post_id);

                // Convert same_as array to ACF repeater format
                $same_as_formatted = [];
                foreach ($same_as as $url) {
                    $same_as_formatted[] = ['url' => $url];
                }
                update_field('same_as', $same_as_formatted, $post_id);

                // Convert properties to ACF flexible content
                $this->migrate_entity_properties($post_id, $entity['entity_type'], $properties);

                // Store mapping
                $this->entity_mapping[$entity['entity_id']] = $post_id;

                $log .= "  ✓ Migrated: {$entity['entity_id']} → Post ID {$post_id}\n";
            } else {
                $log .= "  [DRY RUN] Would migrate: {$entity['entity_id']} ({$entity['name']})\n";
            }

            $count++;
        }

        // Update parent entity relationships (second pass)
        if (!$dry_run) {
            foreach ($entities as $entity) {
                if (!empty($entity['parent_entity_id'])) {
                    $child_post_id = $this->entity_mapping[$entity['entity_id']] ?? null;
                    $parent_post_id = $this->entity_mapping[$entity['parent_entity_id']] ?? null;

                    if ($child_post_id && $parent_post_id) {
                        update_field('parent_entity', $parent_post_id, $child_post_id);
                        $log .= "  ✓ Linked parent: {$entity['entity_id']} → {$entity['parent_entity_id']}\n";
                    }
                }
            }
        }

        return [
            'count' => $count,
            'log' => str_replace('{count}', $count, $log),
        ];
    }

    /**
     * Migrate entity properties to ACF flexible content
     */
    private function migrate_entity_properties($post_id, $entity_type, $properties)
    {
        $flexible_content = [];

        switch ($entity_type) {
            case 'Organization':
                $layout = [
                    'acf_fc_layout' => 'organization_properties',
                    'description' => $properties['description'] ?? '',
                ];

                // Area served
                if (!empty($properties['areaServed'])) {
                    $layout['area_served'] = [];
                    foreach ($properties['areaServed'] as $area) {
                        $layout['area_served'][] = ['country_name' => $area['name'] ?? ''];
                    }
                }

                // Contact points
                if (!empty($properties['contactPoint'])) {
                    $layout['contact_point'] = [];
                    foreach ($properties['contactPoint'] as $contact) {
                        $layout['contact_point'][] = [
                            'contact_type' => $contact['contactType'] ?? '',
                            'telephone' => $contact['telephone'] ?? '',
                            'email' => $contact['email'] ?? '',
                            'available_language' => is_array($contact['availableLanguage'] ?? null)
                                ? implode(', ', $contact['availableLanguage'])
                                : '',
                        ];
                    }
                }

                $flexible_content[] = $layout;
                break;

            case 'Person':
                $knows_about = [];
                if (!empty($properties['knowsAbout'])) {
                    foreach ($properties['knowsAbout'] as $topic) {
                        $knows_about[] = ['topic' => $topic];
                    }
                }

                $flexible_content[] = [
                    'acf_fc_layout' => 'person_properties',
                    'job_title' => $properties['jobTitle'] ?? '',
                    'email' => $properties['email'] ?? '',
                    'knows_about' => $knows_about,
                ];
                break;

            case 'Service':
                $flexible_content[] = [
                    'acf_fc_layout' => 'service_properties',
                    'description' => $properties['description'] ?? '',
                    'service_type' => $properties['serviceType'] ?? '',
                    // Provider will be linked in second pass
                ];
                break;
        }

        if (!empty($flexible_content)) {
            update_field('schema_properties', $flexible_content, $post_id);
        }
    }

    /**
     * Migrate page mappings
     */
    private function migrate_page_mappings($dry_run = false)
    {
        $mappings = $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}ns_page_entity_map",
            ARRAY_A
        );

        $count = 0;
        $log = "\nMigrating {count} page mappings...\n";

        foreach ($mappings as $mapping) {
            // Find WordPress post by wp_post_id or URL
            $post_id = $mapping['wp_post_id'];
            if (!$post_id) {
                $post_id = url_to_postid($mapping['url']);
            }

            if (!$post_id) {
                $log .= "  SKIP: Could not find post for URL {$mapping['url']}\n";
                continue;
            }

            // Decode JSON fields
            $about_entity_ids = json_decode($mapping['about_entity_ids'], true) ?? [];
            $mentions_entity_ids = json_decode($mapping['mentions_entity_ids'], true) ?? [];
            $faq_data = json_decode($mapping['faq_data'], true) ?? [];

            if (!$dry_run) {
                // Map entity IDs to post IDs
                $primary_entity = $this->entity_mapping[$mapping['primary_entity_id']] ?? null;
                $about_entities = array_filter(array_map(function ($id) {
                    return $this->entity_mapping[$id] ?? null;
                }, $about_entity_ids));
                $mentions_entities = array_filter(array_map(function ($id) {
                    return $this->entity_mapping[$id] ?? null;
                }, $mentions_entity_ids));

                // Update ACF fields
                if ($primary_entity) {
                    update_field('primary_entity', $primary_entity, $post_id);
                }
                if (!empty($about_entities)) {
                    update_field('about_entities', $about_entities, $post_id);
                }
                if (!empty($mentions_entities)) {
                    update_field('mentions_entities', $mentions_entities, $post_id);
                }
                if (!empty($faq_data)) {
                    update_field('faq_data', $faq_data, $post_id);
                }

                // SEO overrides
                update_field('seo_overrides', [
                    'canonical_url' => $mapping['canonical_url'] ?? '',
                    'robots' => $mapping['robots'] ?? 'index,follow',
                    'sitemap_include' => $mapping['sitemap_include'] ?? true,
                    'title_override' => $mapping['title_override'] ?? '',
                    'meta_description_override' => $mapping['meta_description_override'] ?? '',
                ], $post_id);

                $log .= "  ✓ Migrated mapping for post ID {$post_id}\n";
            } else {
                $log .= "  [DRY RUN] Would migrate mapping for: {$mapping['url']}\n";
            }

            $count++;
        }

        return [
            'count' => $count,
            'log' => str_replace('{count}', $count, $log),
        ];
    }

    /**
     * Verify migration
     */
    public function cli_verify()
    {
        WP_CLI::line('Verifying migration...');

        $old_count = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->prefix}ns_entities");
        $new_count = wp_count_posts('ns_entity');
        $new_total = ($new_count->publish ?? 0) + ($new_count->draft ?? 0);

        WP_CLI::line("Old entities: $old_count");
        WP_CLI::line("New entities: $new_total");

        if ($old_count === $new_total) {
            WP_CLI::success('Entity counts match!');
        } else {
            WP_CLI::warning('Entity counts do not match.');
        }

        // Check for unique entity_ids
        $entity_ids = [];
        $query = new WP_Query([
            'post_type' => 'ns_entity',
            'posts_per_page' => -1,
            'post_status' => 'any',
        ]);

        foreach ($query->posts as $post) {
            $entity_id = get_field('entity_id', $post->ID);
            if (in_array($entity_id, $entity_ids)) {
                WP_CLI::warning("Duplicate entity_id found: $entity_id");
            }
            $entity_ids[] = $entity_id;
        }

        WP_CLI::success('Verification complete.');
    }
}

// Initialize migration
new NS_Entity_Migration();
