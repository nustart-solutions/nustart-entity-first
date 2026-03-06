<?php
/**
 * ACF Field Group: Page Entity Mapping
 * Relationship fields for connecting pages to entities
 */

if (function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group([
        'key' => 'group_page_entity_mapping',
        'title' => 'Page Entity Mapping',
        'fields' => [
            [
                'key' => 'field_primary_entity',
                'label' => 'Primary Entity',
                'name' => 'primary_entity',
                'type' => 'relationship',
                'instructions' => 'The main entity this page represents',
                'required' => 0,
                'post_type' => ['ns_entity'],
                'filters' => ['search', 'taxonomy'],
                'return_format' => 'id',
                'max' => 1,
            ],
            [
                'key' => 'field_about_entities',
                'label' => 'About Entities',
                'name' => 'about_entities',
                'type' => 'relationship',
                'instructions' => 'Entities this page is about',
                'required' => 0,
                'post_type' => ['ns_entity'],
                'filters' => ['search', 'taxonomy'],
                'return_format' => 'id',
                'max' => '',
            ],
            [
                'key' => 'field_mentions_entities',
                'label' => 'Mentions Entities',
                'name' => 'mentions_entities',
                'type' => 'relationship',
                'instructions' => 'Entities mentioned on this page',
                'required' => 0,
                'post_type' => ['ns_entity'],
                'filters' => ['search', 'taxonomy'],
                'return_format' => 'id',
                'max' => '',
            ],
            [
                'key' => 'field_faq_data',
                'label' => 'FAQ Schema',
                'name' => 'faq_data',
                'type' => 'repeater',
                'instructions' => 'Add FAQ items for FAQPage schema',
                'required' => 0,
                'layout' => 'block',
                'button_label' => 'Add FAQ',
                'sub_fields' => [
                    [
                        'key' => 'field_faq_question',
                        'label' => 'Question',
                        'name' => 'question',
                        'type' => 'text',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_faq_answer',
                        'label' => 'Answer',
                        'name' => 'answer',
                        'type' => 'textarea',
                        'rows' => 3,
                        'required' => 1,
                    ],
                ],
            ],
            [
                'key' => 'field_seo_overrides',
                'label' => 'SEO Overrides',
                'name' => 'seo_overrides',
                'type' => 'group',
                'layout' => 'block',
                'sub_fields' => [
                    [
                        'key' => 'field_canonical_url_override',
                        'label' => 'Canonical URL',
                        'name' => 'canonical_url',
                        'type' => 'url',
                    ],
                    [
                        'key' => 'field_robots',
                        'label' => 'Robots Meta',
                        'name' => 'robots',
                        'type' => 'text',
                        'default_value' => 'index,follow',
                        'placeholder' => 'index,follow',
                    ],
                    [
                        'key' => 'field_sitemap_include',
                        'label' => 'Include in Sitemap',
                        'name' => 'sitemap_include',
                        'type' => 'true_false',
                        'default_value' => 1,
                        'ui' => 1,
                    ],
                    [
                        'key' => 'field_title_override',
                        'label' => 'Title Override',
                        'name' => 'title_override',
                        'type' => 'text',
                    ],
                    [
                        'key' => 'field_meta_description_override',
                        'label' => 'Meta Description Override',
                        'name' => 'meta_description_override',
                        'type' => 'textarea',
                        'rows' => 2,
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'post',
                ],
            ],
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'page',
                ],
            ],
        ],
        'menu_order' => 10,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'show_in_rest' => true,
    ]);
}
