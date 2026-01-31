<?php
/**
 * ACF Field Group: Entity Schema Properties
 * Flexible content for entity-specific schema properties
 */

if (function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group([
        'key' => 'group_entity_schema',
        'title' => 'Schema Properties',
        'fields' => [
            [
                'key' => 'field_schema_properties',
                'label' => 'Schema Properties',
                'name' => 'schema_properties',
                'type' => 'flexible_content',
                'instructions' => 'Add schema.org properties specific to this entity type',
                'required' => 0,
                'button_label' => 'Add Property Group',
                'layouts' => [
                    // Organization Properties
                    'layout_organization' => [
                        'key' => 'layout_organization',
                        'name' => 'organization_properties',
                        'label' => 'Organization Properties',
                        'display' => 'block',
                        'sub_fields' => [
                            [
                                'key' => 'field_org_description',
                                'label' => 'Description',
                                'name' => 'description',
                                'type' => 'textarea',
                                'rows' => 3,
                            ],
                            [
                                'key' => 'field_org_area_served',
                                'label' => 'Area Served',
                                'name' => 'area_served',
                                'type' => 'repeater',
                                'layout' => 'table',
                                'button_label' => 'Add Country',
                                'sub_fields' => [
                                    [
                                        'key' => 'field_country_name',
                                        'label' => 'Country Name',
                                        'name' => 'country_name',
                                        'type' => 'text',
                                    ],
                                ],
                            ],
                            [
                                'key' => 'field_org_contact_point',
                                'label' => 'Contact Points',
                                'name' => 'contact_point',
                                'type' => 'repeater',
                                'layout' => 'block',
                                'button_label' => 'Add Contact Point',
                                'sub_fields' => [
                                    [
                                        'key' => 'field_contact_type',
                                        'label' => 'Contact Type',
                                        'name' => 'contact_type',
                                        'type' => 'text',
                                        'placeholder' => 'customer support',
                                    ],
                                    [
                                        'key' => 'field_contact_telephone',
                                        'label' => 'Telephone',
                                        'name' => 'telephone',
                                        'type' => 'text',
                                    ],
                                    [
                                        'key' => 'field_contact_email',
                                        'label' => 'Email',
                                        'name' => 'email',
                                        'type' => 'email',
                                    ],
                                    [
                                        'key' => 'field_contact_language',
                                        'label' => 'Available Languages',
                                        'name' => 'available_language',
                                        'type' => 'text',
                                        'placeholder' => 'en-CA, en-US',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // Person Properties
                    'layout_person' => [
                        'key' => 'layout_person',
                        'name' => 'person_properties',
                        'label' => 'Person Properties',
                        'display' => 'block',
                        'sub_fields' => [
                            [
                                'key' => 'field_person_job_title',
                                'label' => 'Job Title',
                                'name' => 'job_title',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'field_person_email',
                                'label' => 'Email',
                                'name' => 'email',
                                'type' => 'email',
                            ],
                            [
                                'key' => 'field_person_knows_about',
                                'label' => 'Knows About',
                                'name' => 'knows_about',
                                'type' => 'repeater',
                                'layout' => 'table',
                                'button_label' => 'Add Topic',
                                'sub_fields' => [
                                    [
                                        'key' => 'field_topic',
                                        'label' => 'Topic',
                                        'name' => 'topic',
                                        'type' => 'text',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // Service Properties
                    'layout_service' => [
                        'key' => 'layout_service',
                        'name' => 'service_properties',
                        'label' => 'Service Properties',
                        'display' => 'block',
                        'sub_fields' => [
                            [
                                'key' => 'field_service_description',
                                'label' => 'Description',
                                'name' => 'description',
                                'type' => 'textarea',
                                'rows' => 3,
                            ],
                            [
                                'key' => 'field_service_type',
                                'label' => 'Service Type',
                                'name' => 'service_type',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'field_service_provider',
                                'label' => 'Provider',
                                'name' => 'provider',
                                'type' => 'relationship',
                                'post_type' => ['ns_entity'],
                                'filters' => ['search', 'taxonomy'],
                                'return_format' => 'id',
                                'max' => 1,
                            ],
                        ],
                    ],
                ],
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
    ]);
}
