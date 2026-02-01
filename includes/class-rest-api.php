<?php
/**
 * REST API Endpoints for Entity Management
 */
class NS_Entity_REST_API
{

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register REST API routes
     */
    public function register_routes()
    {
        $namespace = 'nustart-entity/v1';

        // Entity endpoints
        register_rest_route($namespace, '/entities', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_entity'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => 'GET',
                'callback' => [$this, 'list_entities'],
                'permission_callback' => '__return_true'
            ]
        ]);

        register_rest_route($namespace, '/entities/(?P<entity_id>[a-zA-Z0-9_-]+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_entity'],
                'permission_callback' => '__return_true'
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_entity'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_entity'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);

        // Page mapping endpoints
        register_rest_route($namespace, '/page-mappings', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_page_mapping'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => 'GET',
                'callback' => [$this, 'list_page_mappings'],
                'permission_callback' => '__return_true'
            ]
        ]);

        register_rest_route($namespace, '/page-mappings/by-url', [
            'methods' => 'GET',
            'callback' => [$this, 'get_page_mapping_by_url'],
            'permission_callback' => '__return_true'
        ]);
    }

    /**
     * Check if user has permission
     */
    public function check_permission()
    {
        return current_user_can('edit_posts');
    }

    public function create_entity($request)
    {
        error_log('NS_Entity_REST_API: create_entity called');

        $model = new NS_Entity_Model();

        $params = $request->get_json_params();
        error_log('NS_Entity_REST_API: params = ' . print_r($params, true));

        $entity_id = $params['entity_id'] ?? '';

        if (empty($entity_id)) {
            error_log('NS_Entity_REST_API: missing entity_id');
            return new WP_Error('missing_entity_id', 'Entity ID is required', ['status' => 400]);
        }

        try {
            error_log('NS_Entity_REST_API: calling upsert');
            $result = $model->upsert($entity_id, $params);
            error_log('NS_Entity_REST_API: upsert result = ' . print_r($result, true));

            $entity = $model->get($entity_id);
            error_log('NS_Entity_REST_API: retrieved entity = ' . print_r($entity, true));

            return new WP_REST_Response($entity, 201);
        } catch (Exception $e) {
            error_log('NS_Entity_REST_API: exception = ' . $e->getMessage());
            return new WP_Error('create_failed', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Get entity
     */
    public function get_entity($request)
    {
        $model = new NS_Entity_Model();
        $entity_id = $request['entity_id'];

        $entity = $model->get($entity_id);

        if (!$entity) {
            return new WP_Error('not_found', 'Entity not found', ['status' => 404]);
        }

        return new WP_REST_Response($entity, 200);
    }

    /**
     * Update entity
     */
    public function update_entity($request)
    {
        $model = new NS_Entity_Model();
        $entity_id = $request['entity_id'];
        $params = $request->get_json_params();

        try {
            $model->upsert($entity_id, $params);
            $entity = $model->get($entity_id);

            return new WP_REST_Response($entity, 200);
        } catch (Exception $e) {
            return new WP_Error('update_failed', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Delete entity
     */
    public function delete_entity($request)
    {
        $model = new NS_Entity_Model();
        $entity_id = $request['entity_id'];

        $result = $model->delete($entity_id);

        if ($result) {
            return new WP_REST_Response(['deleted' => true], 200);
        }

        return new WP_Error('delete_failed', 'Failed to delete entity', ['status' => 500]);
    }

    /**
     * List entities
     */
    public function list_entities($request)
    {
        $model = new NS_Entity_Model();
        $type = $request->get_param('type');
        $status = $request->get_param('status') ?? 'published';

        if ($type) {
            $entities = $model->get_by_type($type, $status);
        } else {
            // Get all entities (you'd need to add this method to the model)
            global $wpdb;
            $table = $wpdb->prefix . 'ns_entities';
            $entities = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM $table WHERE status = %s", $status),
                ARRAY_A
            );

            foreach ($entities as &$entity) {
                $entity['same_as'] = json_decode($entity['same_as'], true);
                $entity['properties'] = json_decode($entity['properties'], true);
            }
        }

        return new WP_REST_Response($entities, 200);
    }

    /**
     * Create page mapping
     */
    public function create_page_mapping($request)
    {
        $model = new NS_Page_Entity_Map_Model();

        $params = $request->get_json_params();
        $url = $params['url'] ?? '';

        if (empty($url)) {
            return new WP_Error('missing_url', 'URL is required', ['status' => 400]);
        }

        try {
            $model->upsert($url, $params);
            $mapping = $model->get_by_url($url);

            return new WP_REST_Response($mapping, 201);
        } catch (Exception $e) {
            return new WP_Error('create_failed', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Get page mapping by URL
     */
    public function get_page_mapping_by_url($request)
    {
        $model = new NS_Page_Entity_Map_Model();
        $url = $request->get_param('url');

        if (empty($url)) {
            return new WP_Error('missing_url', 'URL parameter is required', ['status' => 400]);
        }

        $mapping = $model->get_by_url($url);

        if (!$mapping) {
            return new WP_Error('not_found', 'Page mapping not found', ['status' => 404]);
        }

        return new WP_REST_Response($mapping, 200);
    }

    /**
     * List page mappings
     */
    public function list_page_mappings($request)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ns_page_entity_map';

        $page_type = $request->get_param('page_type');

        if ($page_type) {
            $mappings = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM $table WHERE page_type = %s", $page_type),
                ARRAY_A
            );
        } else {
            $mappings = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);
        }

        foreach ($mappings as &$mapping) {
            $mapping['schema_graph'] = json_decode($mapping['schema_graph'], true);
            $mapping['about_entity_ids'] = json_decode($mapping['about_entity_ids'], true);
            $mapping['mentions_entity_ids'] = json_decode($mapping['mentions_entity_ids'], true);
            $mapping['faq_data'] = json_decode($mapping['faq_data'], true);
        }

        return new WP_REST_Response($mappings, 200);
    }
}

// Initialize REST API
new NS_Entity_REST_API();
