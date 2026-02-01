<?php
/**
 * Page Entity Map Model - Handles CRUD operations for ns_page_entity_map table
 */
class NS_Page_Entity_Map_Model
{
    private $wpdb;
    private $table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'ns_page_entity_map';
    }

    /**
     * Create or update page entity mapping
     */
    public function upsert($url, $data)
    {
        $existing = $this->get_by_url($url);

        $prepared = [
            'url' => $url,
            'wp_post_id' => $data['wp_post_id'] ?? null,
            'page_type' => $data['page_type'],
            'primary_entity_id' => $data['primary_entity_id'] ?? null,
            'schema_graph' => wp_json_encode($data['schema_graph'] ?? []),
            'about_entity_ids' => wp_json_encode($data['about_entity_ids'] ?? []),
            'mentions_entity_ids' => wp_json_encode($data['mentions_entity_ids'] ?? []),
            'faq_data' => wp_json_encode($data['faq_data'] ?? []),
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
    public function get_by_url($url)
    {
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
    public function get_by_post_id($post_id)
    {
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

    /**
     * Delete page mapping
     */
    public function delete($url)
    {
        return $this->wpdb->delete(
            $this->table,
            ['url' => $url]
        );
    }
}
