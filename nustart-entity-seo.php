<?php
/**
 * Plugin Name: NuStart Entity-First SEO
 * Plugin URI: https://nustart.solutions
 * Description: Entity-first SEO system with schema.org markup generation (ACF + Custom Post Types)
 * Version:           2.5.1
 * Author:            NuStart Solutions
 * Author URI:        https://nustart.solutions
 * License:           GPL-2.0+
 * Text Domain:       nustart-entity-seo
 * Update URI:        https://github.com/nustart-solutions/nustart-entity-first/
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
if (!defined('NS_ENTITY_VERSION')) {
    define('NS_ENTITY_VERSION', '2.5.1');
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
require_once NS_ENTITY_PATH . 'includes/class-rest-api.php';
require_once NS_ENTITY_PATH . 'includes/class-settings-page.php';
require_once NS_ENTITY_PATH . 'includes/class-setup-wizard.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// Initialize Plugin Update Checker with error handling
try {
    require_once NS_ENTITY_PATH . 'vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';

    $nsEntityUpdateChecker = PucFactory::buildUpdateChecker(
        'https://github.com/nustart-solutions/nustart-entity-first/',
        __FILE__,
        'nustart-entity-first'
    );

    // Set the branch to check for updates (default: main)
    $nsEntityUpdateChecker->setBranch('main');

    // To avoid GitHub API rate limits (60 req/hr) or access private repos, 
    // define NUSTART_GITHUB_TOKEN in wp-config.php
    if (defined('NUSTART_GITHUB_TOKEN')) {
        $nsEntityUpdateChecker->setAuthentication(NUSTART_GITHUB_TOKEN);
    }
} catch (Exception $e) {
    // Log error but don't prevent plugin activation
    error_log('NuStart Entity SEO: Plugin Update Checker failed to initialize: ' . $e->getMessage());
}


/**
 * Plugin activation - register post types and flush rewrite rules
 */
function ns_entity_activate()
{
    // Check for ACF
    if (!function_exists('acf_add_local_field_group')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            '<h1>ACF Required</h1>' .
            '<p>The <strong>NuStart Entity-First SEO</strong> plugin requires Advanced Custom Fields (ACF) to be installed and activated.</p>' .
            '<p><a href="https://wordpress.org/plugins/advanced-custom-fields/" target="_blank">Download ACF Free</a> or install it from your WordPress admin.</p>' .
            '<p><a href="' . admin_url('plugins.php') . '">Return to Plugins</a></p>'
        );
    }

    // Register post types and flush rewrite rules
    $post_type = new NS_Entity_Post_Type();
    $post_type->register_post_type();
    $post_type->register_taxonomy();
    flush_rewrite_rules();

    // Set transient to trigger setup wizard on first activation
    if (!get_option('ns_entity_setup_complete')) {
        set_transient('ns_entity_show_setup_wizard', true, 60);
    }
}
register_activation_hook(__FILE__, 'ns_entity_activate');

/**
 * Check for ACF dependency on admin pages
 */
function ns_entity_check_acf_dependency()
{
    if (!function_exists('acf_add_local_field_group')) {
        add_action('admin_notices', 'ns_entity_acf_missing_notice');
    }
}
add_action('admin_init', 'ns_entity_check_acf_dependency');

/**
 * Display admin notice if ACF is not installed
 */
function ns_entity_acf_missing_notice()
{
    ?>
    <div class="notice notice-error">
        <p>
            <strong>NuStart Entity-First SEO</strong> requires Advanced Custom Fields (ACF) to be installed and activated.
            <a href="<?php echo admin_url('plugin-install.php?s=advanced+custom+fields&tab=search&type=term'); ?>">Install
                ACF now</a>
        </p>
    </div>
    <?php
}

/**
 * Initialize settings page and setup wizard
 */
function ns_entity_init_admin()
{
    if (is_admin()) {
        new NS_Entity_Settings_Page();
        new NS_Entity_Setup_Wizard();
    }

    // Initialize REST API endpoints
    new NS_Entity_REST_API();
}
add_action('plugins_loaded', 'ns_entity_init_admin');

/**
 * Output schema in <head>
 */
function ns_entity_output_schema()
{
    // Only output if ACF is available
    if (!function_exists('get_field')) {
        return;
    }

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
