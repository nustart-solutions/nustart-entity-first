<?php
/**
 * REST API extensions for NuStart Entity-First SEO.
 * Merges the standalone nustart-seo-tools plugin functionality into the main entity plugin.
 */

if (!defined('ABSPATH')) {
    exit;
}

class NS_Entity_REST_API
{
    /**
     * Initialize REST API hooks
     */
    public function __construct()
    {
        // Register REST API fields for metadata and builder data
        add_action('rest_api_init', [$this, 'register_rest_fields']);

        // Register the full-site Entity Graph endpoint
        add_action('rest_api_init', [$this, 'register_entity_graph_route']);

        // Injects <link rel="alternate"> into <head>
        add_action('wp_head', [$this, 'output_entity_graph_link'], 1);
    }

    /**
     * Registers standard SEO metadata and Breakdance builder data into the REST API.
     */
    public function register_rest_fields()
    {
        $post_types = ['post', 'page'];

        foreach ($post_types as $post_type) {
            register_rest_field($post_type, 'seo_metadata', [
                'get_callback' => [$this, 'get_seo_metadata'],
                'update_callback' => [$this, 'update_seo_metadata'],
                'schema' => [
                    'description' => __('Standardized SEO Metadata Object for AI Agents', 'nustart-entity-seo'),
                    'type' => 'object',
                ],
            ]);
        }

        // Register Breakdance Data
        register_rest_field('page', '_breakdance_data', [
            'get_callback' => function ($object) {
                return get_post_meta($object['id'], '_breakdance_data', true);
            },
            'update_callback' => function ($value, $object) {
                return update_post_meta($object->ID, '_breakdance_data', $value);
            },
            'schema' => [
                'type' => 'string',
                'description' => 'Breakdance Builder Data Object',
            ],
        ]);

        // Register Breakdance Mode
        register_rest_field('page', '_breakdance_mode', [
            'get_callback' => function ($object) {
                return get_post_meta($object['id'], '_breakdance_mode', true);
            },
            'update_callback' => function ($value, $object) {
                return update_post_meta($object->ID, '_breakdance_mode', $value);
            },
            'schema' => [
                'type' => 'string',
                'description' => 'Breakdance Mode (custom/default)',
            ],
        ]);
    }

    /**
     * Retrieves SEO metadata from active SEO plugins (RankMath or Yoast).
     */
    public function get_seo_metadata($post_array)
    {
        $post_id = $post_array['id'];
        $permalink = get_permalink($post_id);

        $data = [
            'title' => '',
            'description' => '',
            'keywords' => '',
            'seo_score' => 0,
            'is_noindex' => false,
            'permanent_id' => '',
            'source' => 'none'
        ];

        // 1. Rank Math Logic
        if (class_exists('RankMath')) {
            $data['title'] = get_post_meta($post_id, 'rank_math_title', true);
            $data['description'] = get_post_meta($post_id, 'rank_math_description', true);
            $data['keywords'] = get_post_meta($post_id, 'rank_math_focus_keyword', true);
            $data['seo_score'] = (int) get_post_meta($post_id, 'rank_math_seo_score', true);
            $data['source'] = 'rank-math';

            $robots = get_post_meta($post_id, 'rank_math_robots', true);
            $data['is_noindex'] = (is_array($robots) && in_array('noindex', $robots));
        }
        // 2. Yoast SEO Logic
        elseif (defined('WPSEO_VERSION')) {
            $data['title'] = get_post_meta($post_id, '_yoast_wpseo_title', true);
            $data['description'] = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
            $data['keywords'] = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
            $data['seo_score'] = (int) get_post_meta($post_id, '_yoast_wpseo_metascore', true);
            $data['source'] = 'yoast';

            $noindex = get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true);
            $data['is_noindex'] = ($noindex === '1' || $noindex === 1);
        }

        // 3. Fallback title
        if (empty($data['title'])) {
            $data['title'] = get_the_title($post_id);
        }

        // 4. Determine fragment
        $type_fragment = 'article';
        if ($post_id == get_option('page_on_front')) {
            $type_fragment = 'organization';
        } elseif (get_post_type($post_id) === 'page') {
            $type_fragment = 'service';
        }

        // 5. Build permanent ID
        $clean_url = untrailingslashit($permalink);
        $data['permanent_id'] = $clean_url . '#' . $type_fragment;

        return $data;
    }

    /**
     * Updates SEO metadata based on the active SEO plugin.
     */
    public function update_seo_metadata($value, $post, $key)
    {
        if (!is_array($value)) {
            return;
        }

        $post_id = $post->ID;
        $is_rankmath = class_exists('RankMath');
        $is_yoast = defined('WPSEO_VERSION');

        if (isset($value['description'])) {
            $desc = sanitize_text_field($value['description']);
            if ($is_rankmath)
                update_post_meta($post_id, 'rank_math_description', $desc);
            if ($is_yoast)
                update_post_meta($post_id, '_yoast_wpseo_metadesc', $desc);
        }

        if (isset($value['keywords'])) {
            $kw = sanitize_text_field($value['keywords']);
            if ($is_rankmath)
                update_post_meta($post_id, 'rank_math_focus_keyword', $kw);
            if ($is_yoast)
                update_post_meta($post_id, '_yoast_wpseo_focuskw', $kw);
        }

        if (isset($value['title'])) {
            $title = sanitize_text_field($value['title']);
            if ($is_rankmath)
                update_post_meta($post_id, 'rank_math_title', $title);
            if ($is_yoast)
                update_post_meta($post_id, '_yoast_wpseo_title', $title);
        }

        return true;
    }


    /**
     * Register the /wp-json/nustart/v1/entity-graph endpoint
     */
    public function register_entity_graph_route()
    {
        register_rest_route('nustart/v1', '/entity-graph', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'entity_graph_handler'],
            'permission_callback' => '__return_true',
            'args' => [
                'post_type' => [
                    'default' => 'post,page',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    /**
     * Handler for the site-wide Entity Graph.
     * Delegates to the NS_Schema_Generator_ACF class.
     */
    public function entity_graph_handler(\WP_REST_Request $request): \WP_REST_Response
    {
        if (!class_exists('NS_Schema_Generator_ACF')) {
            return new \WP_REST_Response([
                '@context' => 'https://schema.org',
                '@graph' => [],
                '_info' => 'NuStart Entity-First SEO Plugin is missing Schema Generator.',
            ], 200);
        }

        $generator = new NS_Schema_Generator_ACF();
        $graph = [];

        $query = new \WP_Query([
            'post_type' => ['post', 'page'],
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        foreach ($query->posts as $post_id) {
            if ($this->post_is_noindex($post_id)) {
                continue;
            }

            $schema = $generator->generate_for_post($post_id);
            if (!$schema || empty($schema['@graph'])) {
                continue;
            }

            foreach ($schema['@graph'] as $entity) {
                $id = $entity['@id'] ?? null;
                if ($id && isset($graph[$id])) {
                    $graph[$id] = $this->merge_entity($graph[$id], $entity);
                } elseif ($id) {
                    $graph[$id] = $entity;
                } else {
                    $graph[] = $entity;
                }
            }
        }

        return new \WP_REST_Response([
            '@context' => 'https://schema.org',
            '@graph' => array_values($graph),
        ], 200);
    }

    /**
     * Injects the application/ld+json alternative link head tag
     */
    public function output_entity_graph_link()
    {
        $endpoint = rest_url('nustart/v1/entity-graph');
        echo '<link rel="alternate" type="application/ld+json" href="' . esc_url($endpoint) . '">' . "\n";
    }

    /**
     * Helper to merge duplicate entities in the graph.
     */
    protected function merge_entity(array $base, array $override): array
    {
        foreach ($override as $key => $val) {
            if (in_array($key, ['@id', '@context'], true)) {
                continue;
            }
            if (!array_key_exists($key, $base)) {
                $base[$key] = $val;
            } elseif (is_array($val) && is_array($base[$key])) {
                if ($key === '@type') {
                    continue;
                }

                $base_is_list = array_is_list($base[$key]);
                $val_is_list = array_is_list($val);

                if ($base_is_list && $val_is_list) {
                    $base[$key] = array_values(array_unique(
                        array_merge($base[$key], $val),
                        SORT_REGULAR
                    ));
                } else {
                    $base[$key] = $val;
                }
            } else {
                $base[$key] = $val;
            }
        }
        return $base;
    }

    /**
     * Helper to check noindex.
     */
    protected function post_is_noindex(int $post_id): bool
    {
        if (class_exists('RankMath')) {
            $robots = get_post_meta($post_id, 'rank_math_robots', true);
            return is_array($robots) && in_array('noindex', $robots, true);
        }
        if (defined('WPSEO_VERSION')) {
            $noindex = get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true);
            return ($noindex === '1' || $noindex === 1);
        }
        return false;
    }
}
