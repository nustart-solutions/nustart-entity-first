<?php
/**
 * ACF Field Group: Entity Schema Properties
 * Flexible JSON storage for schema.org properties
 */

if (function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group([
        'key' => 'group_entity_schema',
        'title' => 'Schema Properties',
        'fields' => [
            [
                'key' => 'field_schema_json',
                'label' => 'Schema.org JSON',
                'name' => 'schema_json',
                'type' => 'textarea',
                'instructions' => 'Complete schema.org JSON for this entity. Do not include @id (auto-generated). Example:
{
  "@type": "Service",
  "name": "New Website Development",
  "serviceType": "WordPress Website Development",
  "description": "Custom WordPress websites...",
  "additionalProperty": [
    {"@type": "PropertyValue", "name": "Platform", "value": "WordPress"},
    {"@type": "PropertyValue", "name": "Accessibility", "value": "WCAG 2.1 AA"}
  ]
}',
                'required' => 0,
                'rows' => 20,
                'new_lines' => '', // No formatting
                'placeholder' => '{"@type": "Service", "name": "..."}',
            ],
            [
                'key' => 'field_schema_notes',
                'label' => 'Schema Notes',
                'name' => 'schema_notes',
                'type' => 'wysiwyg',
                'instructions' => 'Internal notes about this entity schema (not output to frontend)',
                'required' => 0,
                'toolbar' => 'basic',
                'media_upload' => 0,
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
        'menu_order' => 1,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'show_in_rest' => true,
    ]);
}
