<?php
/**
 * Schema Generator - Generates JSON-LD schema for pages
 * Updated to use ACF fields and ns_entity custom post type
 */
class NS_Schema_Generator_ACF
{
    /**
     * Generate complete schema graph for current URL
     */
    public function generate_for_current_url()
    {
        global $post;

        if (!$post) {
            return null;
        }

        return $this->generate_for_post($post->ID);
    }

    /**
     * Generate complete schema graph for a post
     */
    public function generate_for_post($post_id)
    {
        $post = get_post($post_id);
        if (!$post) {
            return null;
        }

        $graph = [];
        $added_entities = []; // Track which entities we've already added

        // Get page entity mappings from ACF
        $primary_entity_id = get_field('primary_entity', $post_id);
        $about_entity_ids = get_field('about_entities', $post_id) ?: [];
        $mentions_entity_ids = get_field('mentions_entities', $post_id) ?: [];
        $faq_data = get_field('faq_data', $post_id) ?: [];

        // Determine if this is a blog post
        $is_blog_post = ($post->post_type === 'post');

        // For blog posts, generate WebPage + BlogPosting schema
        if ($is_blog_post) {
            // Build about references for WebPage
            $webpage_about = [];
            foreach ($about_entity_ids as $entity_post_id) {
                $entity_id_slug = get_field('entity_id', $entity_post_id);
                if ($entity_id_slug) {
                    $webpage_about[] = ['@id' => home_url() . '/#' . $entity_id_slug];
                }
            }

            // Add service to WebPage.about
            foreach ($mentions_entity_ids as $entity_post_id) {
                $entity_schema = $this->entity_to_schema($entity_post_id);
                if ($entity_schema) {
                    $webpage_about[] = ['@id' => $entity_schema['@id']];
                }
            }

            // Add WebPage entity
            $graph[] = [
                '@type' => 'WebPage',
                '@id' => get_permalink($post_id) . '#webpage',
                'url' => get_permalink($post_id),
                'name' => $post->post_title,
                'isPartOf' => ['@id' => home_url() . '#website'],
                'about' => $webpage_about,
                'publisher' => ['@id' => home_url() . '#org-nustart'],
                'inLanguage' => 'en-CA'
            ];

            // Build about references for BlogPosting
            $article_about = [];
            foreach ($about_entity_ids as $entity_post_id) {
                $entity_id_slug = get_field('entity_id', $entity_post_id);
                if ($entity_id_slug) {
                    $article_about[] = ['@id' => home_url() . '/#' . $entity_id_slug];
                }
            }

            // Build mentions
            $article_mentions = [];
            foreach ($mentions_entity_ids as $entity_post_id) {
                $entity_schema = $this->entity_to_schema($entity_post_id);
                if ($entity_schema) {
                    $article_mentions[] = ['@id' => $entity_schema['@id']];
                }
            }

            // Add BlogPosting entity
            $graph[] = [
                '@type' => 'BlogPosting',
                '@id' => get_permalink($post_id) . '#article',
                'mainEntityOfPage' => ['@id' => get_permalink($post_id) . '#webpage'],
                'headline' => $post->post_title,
                'author' => ['@id' => home_url() . '/about-nustart-web-solutions/#person-anne'],
                'publisher' => ['@id' => home_url() . '#org-nustart'],
                'about' => $article_about,
                'mentions' => $article_mentions,
                'datePublished' => get_the_date('c', $post),
                'dateModified' => get_the_modified_date('c', $post),
                'inLanguage' => 'en-CA'
            ];
        }

        // Add primary entity (skip for blog posts)
        if (!$is_blog_post && $primary_entity_id) {
            $primary_schema = $this->entity_to_schema($primary_entity_id);
            if ($primary_schema) {
                $graph[] = $primary_schema;
                $added_entities[] = $primary_entity_id;
            }
        }

        // Add about entities
        foreach ($about_entity_ids as $entity_post_id) {
            if (in_array($entity_post_id, $added_entities)) {
                continue;
            }
            $schema = $this->entity_to_schema($entity_post_id);
            if ($schema) {
                $graph[] = $schema;
                $added_entities[] = $entity_post_id;
            }
        }

        // Add mentioned entities (skip if already added or if blog post)
        if (!$is_blog_post) {
            foreach ($mentions_entity_ids as $entity_post_id) {
                if (in_array($entity_post_id, $added_entities)) {
                    continue;
                }
                $schema = $this->entity_to_schema($entity_post_id);
                if ($schema) {
                    $graph[] = $schema;
                    $added_entities[] = $entity_post_id;
                }
            }
        }

        // Add WebSite entity (if homepage)
        if (is_front_page()) {
            $graph[] = [
                '@type' => 'WebSite',
                '@id' => home_url() . '#website',
                'url' => home_url(),
                'name' => get_bloginfo('name'),
                'publisher' => ['@id' => home_url() . '#org-nustart']
            ];
        }

        // Add WebPage entity (skip for blog posts)
        if (!$is_blog_post) {
            $seo_overrides = get_field('seo_overrides', $post_id);
            $graph[] = [
                '@type' => 'WebPage',
                '@id' => get_permalink($post_id) . '#webpage',
                'url' => get_permalink($post_id),
                'name' => $seo_overrides['title_override'] ?? wp_get_document_title(),
                'description' => $seo_overrides['meta_description_override'] ?? '',
                'isPartOf' => ['@id' => home_url() . '#website'],
                'about' => $primary_entity_id ?
                    ['@id' => home_url() . '#' . get_field('entity_id', $primary_entity_id)] : null
            ];
        }

        // Add FAQPage if FAQ data exists
        if (!empty($faq_data)) {
            $graph[] = [
                '@type' => 'FAQPage',
                'mainEntity' => $this->format_faq_schema($faq_data)
            ];
        }

        // Wrap in @graph
        return [
            '@context' => 'https://schema.org',
            '@graph' => array_filter($graph) // Remove nulls
        ];
    }

    /**
     * Convert entity post to schema.org JSON-LD
     */
    private function entity_to_schema($entity_post_id)
    {
        $entity_post = get_post($entity_post_id);
        if (!$entity_post || $entity_post->post_type !== 'ns_entity') {
            return null;
        }

        // Get entity type from taxonomy
        $terms = get_the_terms($entity_post_id, 'ns_entity_type');
        $entity_type = $terms && !is_wp_error($terms) ? $terms[0]->name : 'Thing';

        // Get ACF fields
        $entity_id = get_field('entity_id', $entity_post_id);
        $canonical_url = get_field('canonical_url', $entity_post_id);
        $same_as = get_field('same_as', $entity_post_id) ?: [];
        $schema_properties = get_field('schema_properties', $entity_post_id) ?: [];

        // Build base schema
        $schema = [
            '@type' => $entity_type,
            '@id' => ($canonical_url ?: home_url()) . '#' . $entity_id,
            'name' => $entity_post->post_title,
            'url' => $canonical_url
        ];

        // Add sameAs
        if (!empty($same_as)) {
            $same_as_urls = array_column($same_as, 'url');
            $schema['sameAs'] = array_filter($same_as_urls);
        }

        // Merge schema properties from flexible content
        foreach ($schema_properties as $property_group) {
            $layout = $property_group['acf_fc_layout'];

            switch ($layout) {
                case 'organization_properties':
                    if (!empty($property_group['description'])) {
                        $schema['description'] = $property_group['description'];
                    }

                    // Area served
                    if (!empty($property_group['area_served'])) {
                        $schema['areaServed'] = [];
                        foreach ($property_group['area_served'] as $area) {
                            $schema['areaServed'][] = [
                                '@type' => 'Country',
                                'name' => $area['country_name']
                            ];
                        }
                    }

                    // Contact points
                    if (!empty($property_group['contact_point'])) {
                        $schema['contactPoint'] = [];
                        foreach ($property_group['contact_point'] as $contact) {
                            $contact_schema = [
                                '@type' => 'ContactPoint',
                                'contactType' => $contact['contact_type'],
                            ];
                            if (!empty($contact['telephone'])) {
                                $contact_schema['telephone'] = $contact['telephone'];
                            }
                            if (!empty($contact['email'])) {
                                $contact_schema['email'] = $contact['email'];
                            }
                            if (!empty($contact['available_language'])) {
                                $contact_schema['availableLanguage'] = array_map('trim', explode(',', $contact['available_language']));
                            }
                            $schema['contactPoint'][] = $contact_schema;
                        }
                    }
                    break;

                case 'person_properties':
                    if (!empty($property_group['job_title'])) {
                        $schema['jobTitle'] = $property_group['job_title'];
                    }
                    if (!empty($property_group['email'])) {
                        $schema['email'] = $property_group['email'];
                    }
                    if (!empty($property_group['knows_about'])) {
                        $schema['knowsAbout'] = array_column($property_group['knows_about'], 'topic');
                    }

                    // Add worksFor if parent entity exists
                    $parent_entity_id = get_field('parent_entity', $entity_post_id);
                    if ($parent_entity_id) {
                        $parent_canonical = get_field('canonical_url', $parent_entity_id);
                        $parent_entity_slug = get_field('entity_id', $parent_entity_id);
                        $schema['worksFor'] = [
                            '@id' => ($parent_canonical ?: home_url()) . '#' . $parent_entity_slug
                        ];
                    }
                    break;

                case 'service_properties':
                    if (!empty($property_group['description'])) {
                        $schema['description'] = $property_group['description'];
                    }
                    if (!empty($property_group['service_type'])) {
                        $schema['serviceType'] = $property_group['service_type'];
                    }
                    if (!empty($property_group['provider'])) {
                        $provider_canonical = get_field('canonical_url', $property_group['provider']);
                        $provider_entity_slug = get_field('entity_id', $property_group['provider']);
                        $schema['provider'] = [
                            '@id' => ($provider_canonical ?: home_url()) . '#' . $provider_entity_slug
                        ];
                    }
                    break;
            }
        }

        return $schema;
    }

    /**
     * Format FAQ data into schema
     */
    private function format_faq_schema($faq_data)
    {
        $questions = [];
        foreach ($faq_data as $faq) {
            $questions[] = [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer']
                ]
            ];
        }
        return $questions;
    }

    /**
     * Output schema in <head>
     */
    public function output_schema()
    {
        $schema = $this->generate_for_current_url();

        if ($schema) {
            echo "\n" . '<!-- NuStart Entity-First SEO Schema (ACF) -->' . "\n";
            echo '<script type="application/ld+json">' . "\n";
            echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            echo "\n" . '</script>' . "\n";
        }
    }
}
