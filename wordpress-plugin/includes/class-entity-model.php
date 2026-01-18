<?php
/**
 * Entity Model - Handles CRUD operations for ns_entities table
 */
class NS_Entity_Model
{
    private $wpdb;
    private $table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'ns_entities';
    }

    /**
     * Create or update an entity
     */
    public function upsert($entity_id, $data)
    {
        $existing = $this->get($entity_id);

        $prepared = [
            'entity_id' => $entity_id,
            'entity_type' => $data['entity_type'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'canonical_url' => $data['canonical_url'] ?? null,
            'same_as' => wp_json_encode($data['same_as'] ?? []),
            'parent_entity_id' => $data['parent_entity_id'] ?? null,
            'properties' => wp_json_encode($data['properties'] ?? []),
            'status' => $data['status'] ?? 'draft'
        ];

        if ($existing) {
            // Update
            $this->wpdb->update(
                $this->table,
                $prepared,
                ['entity_id' => $entity_id]
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
    public function get($entity_id)
    {
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
     * Get all entities of a specific type
     */
    public function get_by_type($entity_type, $status = 'published')
    {
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
    public function to_schema($entity_id)
    {
        $entity = $this->get($entity_id);
        if (!$entity)
            return null;

        $schema = [
            '@type' => ucfirst($entity['entity_type']),
            '@id' => ($entity['canonical_url'] ?? '') . '#' . $entity['entity_id'],
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

    /**
     * Delete an entity
     */
    public function delete($entity_id)
    {
        return $this->wpdb->delete(
            $this->table,
            ['entity_id' => $entity_id]
        );
    }
}
