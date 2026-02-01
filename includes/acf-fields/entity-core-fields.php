<?php
/**
 * ACF Field Group: Entity Core Fields
 * Registers core fields for ns_entity post type
 */

if (function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group([
        'key' => 'group_entity_core',
        'title' => 'Entity Core Fields',
        'fields' => [
            [
                'key' => 'field_entity_id',
                'label' => 'Entity ID',
                'name' => 'entity_id',
                'type' => 'text',
                'instructions' => 'Unique identifier (e.g., org-nustart, person-anne). Use lowercase with hyphens.',
                'required' => 1,
                'wrapper' => [
                    'width' => '50',
                ],
            ],
            [
                'key' => 'field_entity_status',
                'label' => 'Status',
                'name' => 'entity_status',
                'type' => 'select',
                'instructions' => 'Publication status for this entity',
                'required' => 1,
                'choices' => [
                    'published' => 'Published',
                    'draft' => 'Draft',
                ],
                'default_value' => 'draft',
                'wrapper' => [
                    'width' => '50',
                ],
            ],
            [
                'key' => 'field_canonical_url',
                'label' => 'Canonical URL',
                'name' => 'canonical_url',
                'type' => 'url',
                'instructions' => 'The primary URL for this entity',
                'required' => 0,
            ],
            [
                'key' => 'field_same_as',
                'label' => 'Same As (External Profiles)',
                'name' => 'same_as',
                'type' => 'repeater',
                'instructions' => 'External profile URLs (LinkedIn, Twitter, etc.)',
                'required' => 0,
                'layout' => 'table',
                'button_label' => 'Add Profile URL',
                'sub_fields' => [
                    [
                        'key' => 'field_same_as_url',
                        'label' => 'URL',
                        'name' => 'url',
                        'type' => 'url',
                        'required' => 1,
                    ],
                ],
            ],
            [
                'key' => 'field_parent_entity',
                'label' => 'Parent Entity',
                'name' => 'parent_entity',
                'type' => 'relationship',
                'instructions' => 'Parent entity (e.g., Person works for Organization)',
                'required' => 0,
                'post_type' => ['ns_entity'],
                'filters' => ['search', 'taxonomy'],
                'return_format' => 'id',
                'max' => 1,
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'ns_entity',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
    ]);
}
