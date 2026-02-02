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
    public function generate_for_current_url(&$debug = [])
    {
        global $post;

        if (!$post) {
            return null;
        }

        return $this->generate_for_post($post->ID, $debug);
    }

    /**
     * Generate complete schema graph for a post
     */
    public function generate_for_post($post_id, &$debug = [])
    {
        $post = get_post($post_id);
        if (!$post) {
            return null;
        }

        $graph = [];
        $added_entities = []; // Track which entities we've already added

        // Helper to get ID from mixed return (Object, Array, or ID)
        $get_id = function ($mixed) use (&$get_id) {
            if (is_array($mixed)) {
                return !empty($mixed) ? $get_id(reset($mixed)) : null;
            }
            if (is_object($mixed) && isset($mixed->ID)) {
                return $mixed->ID;
            }
            return is_numeric($mixed) ? (int) $mixed : null;
        };

        // Get page entity mappings from ACF and normalize to IDs
        $primary_raw = get_field('primary_entity', $post_id);
        $primary_entity_id = $get_id($primary_raw);

        $about_raw = get_field('about_entities', $post_id) ?: [];
        $about_entity_ids = array_filter(array_map($get_id, is_array($about_raw) ? $about_raw : []));

        $mentions_raw = get_field('mentions_entities', $post_id) ?: [];
        $mentions_entity_ids = array_filter(array_map($get_id, is_array($mentions_raw) ? $mentions_raw : []));

        $faq_data = get_field('faq_data', $post_id) ?: [];

        // Determine if this is a blog post
        $is_blog_post = ($post->post_type === 'post');

        // For blog posts, generate WebPage + BlogPosting schema
        if ($is_blog_post) {
            // Build about references for WebPage
            $webpage_about = [];
            foreach ($about_entity_ids as $entity_post_id) {
                // Normalized check using new loop variable
                $entity_id_raw = get_field('entity_id', $entity_post_id);
                if ($entity_id_raw) {
                    $webpage_about[] = ['@id' => home_url() . '/#' . $entity_id_raw];
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
                $entity_id_raw = get_field('entity_id', $entity_post_id);
                if ($entity_id_raw) {
                    $article_about[] = ['@id' => home_url() . '/#' . $entity_id_raw];
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

        // Add parent entities for any entities that have isPartOf relationships
        // This ensures referenced parent entities are included in the graph
        if (!$is_blog_post) {
            $entities_to_check = array_unique(array_merge(
                $primary_entity_id ? [$primary_entity_id] : [],
                $about_entity_ids,
                $mentions_entity_ids
            ));

            $debug[] = "Entities to check for parents: " . implode(', ', $entities_to_check);

            foreach ($entities_to_check as $entity_id) {
                // Determine existing parent logic: get field, handle Array/Object/ID
                $parent_raw = get_field('parent_entity', $entity_id);
                $parent_entity_id = $get_id($parent_raw);

                $type_raw = gettype($parent_raw);
                $debug[] = "Checking Entity ID: $entity_id - Parent Raw Type: $type_raw - ID: " . ($parent_entity_id ?: 'null');

                if ($parent_entity_id) {
                    if (!in_array($parent_entity_id, $added_entities)) {
                        $parent_schema = $this->entity_to_schema($parent_entity_id);
                        if ($parent_schema) {
                            $debug[] = "Adding Parent Entity: $parent_entity_id";
                            $debug[] = "Parent Schema Keys: " . implode(', ', array_keys($parent_schema));
                            $debug[] = "Parent Schema Type: " . ($parent_schema['@type'] ?? 'MISSING');
                            $debug[] = "Parent Schema ID: " . ($parent_schema['@id'] ?? 'MISSING');
                            $graph[] = $parent_schema;
                            $added_entities[] = $parent_entity_id;
                        } else {
                            $debug[] = "Failed to generate schema for Parent: $parent_entity_id";
                        }
                    } else {
                        $debug[] = "Parent $parent_entity_id already in graph";
                    }
                }
            }
        }

        // Add WebSite entity (if homepage)
        if (is_front_page()) {
            // Get publisher reference from primary entity
            $publisher_ref = null;
            if ($primary_entity_id) {
                $entity_id = get_field('entity_id', $primary_entity_id);
                $canonical_url = get_field('canonical_url', $primary_entity_id);

                if (!empty($entity_id)) {
                    $publisher_ref = ['@id' => ($canonical_url ?: home_url()) . '#' . $entity_id];
                }
            }

            $website_schema = [
                '@type' => 'WebSite',
                '@id' => home_url() . '#website',
                'url' => home_url(),
                'name' => get_bloginfo('name')
            ];

            // Only add publisher if we have a valid reference
            if ($publisher_ref) {
                $website_schema['publisher'] = $publisher_ref;
            }

            $graph[] = $website_schema;
        }

        // Add WebPage entity (skip for blog posts - handled above)
        if (!$is_blog_post) {
            $seo_overrides = get_field('seo_overrides', $post_id);

            // Build about reference only if primary entity has valid entity_id
            $about_ref = null;
            if ($primary_entity_id) {
                $entity_id = get_field('entity_id', $primary_entity_id);
                $canonical_url = get_field('canonical_url', $primary_entity_id);

                if (!empty($entity_id)) {
                    $about_ref = ['@id' => ($canonical_url ?: home_url()) . '#' . $entity_id];
                }
            }

            $webpage_schema = [
                '@type' => 'WebPage',
                '@id' => get_permalink($post_id) . '#webpage',
                'url' => get_permalink($post_id),
                'name' => $seo_overrides['title_override'] ?? wp_get_document_title(),
                'description' => $seo_overrides['meta_description_override'] ?? '',
                'isPartOf' => ['@id' => home_url() . '#website']
            ];

            // Only add about if we have a valid reference
            if ($about_ref) {
                $webpage_schema['about'] = $about_ref;
            }

            $graph[] = $webpage_schema;
        }

        // Add FAQPage if FAQ data exists
        if (!empty($faq_data)) {
            $graph[] = [
                '@type' => 'FAQPage',
                'mainEntity' => $this->format_faq_schema($faq_data)
            ];
        }

        // Wrap in @graph
        // Ensure array_values resets keys so checking logic works and JSON is an array
        $final_graph = array_values(array_filter($graph));

        $debug[] = "Final Graph Count: " . count($final_graph);
        $debug[] = "Final Graph Keys: " . implode(', ', array_keys($final_graph));

        return [
            '@context' => 'https://schema.org',
            '@graph' => $final_graph
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
        $schema_json = get_field('schema_json', $entity_post_id);
        $parent_entity_id = get_field('parent_entity', $entity_post_id);

        // Parse schema JSON
        $schema_data = [];
        if (!empty($schema_json)) {
            $decoded = json_decode($schema_json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $schema_data = $decoded;
            }
        }

        // Build base schema
        $schema = [
            '@type' => $schema_data['@type'] ?? $entity_type,
            '@id' => ($canonical_url ?: home_url()) . '#' . $entity_id,
            'name' => $schema_data['name'] ?? $entity_post->post_title,
            'url' => $canonical_url
        ];

        // Add sameAs (parse from textarea - one URL per line)
        if (!empty($same_as)) {
            // Handle both string (textarea) and array formats
            if (is_string($same_as)) {
                $same_as_urls = array_filter(array_map('trim', explode("\n", $same_as)));
            } elseif (is_array($same_as)) {
                $same_as_urls = array_map('trim', $same_as);
            } else {
                $same_as_urls = [];
            }

            // Filter out non-URLs (like post IDs)
            $same_as_urls = array_filter($same_as_urls, function ($url) {
                return filter_var($url, FILTER_VALIDATE_URL);
            });

            if (!empty($same_as_urls)) {
                $schema['sameAs'] = array_values($same_as_urls);
            }
        }

        // Add parent relationship (isPartOf) - only for Service entities
        // Person entities use worksFor instead
        if ($parent_entity_id && ($schema['@type'] ?? '') === 'Service') {
            $parent_canonical = get_field('canonical_url', $parent_entity_id);
            $parent_entity_slug = get_field('entity_id', $parent_entity_id);

            // Only add if parent has valid entity_id
            if (!empty($parent_entity_slug)) {
                $schema['isPartOf'] = [
                    '@id' => ($parent_canonical ?: home_url()) . '#' . $parent_entity_slug
                ];
            }

            // Add category from parent name
            $parent_post = get_post($parent_entity_id);
            if ($parent_post) {
                $schema['category'] = $parent_post->post_title;
            }
        }

        // Merge all other properties from schema_json (exclude @context and core properties)
        foreach ($schema_data as $key => $value) {
            if (!in_array($key, ['@context', '@type', '@id', 'name', 'url'])) {
                $schema[$key] = $value;
            }
        }

        // Handle provider relationship (for Services)
        // Only add if not already set in schema_json
        // Provider should be explicitly set or inherited from parent

        // Handle worksFor relationship (for Persons)
        if (($schema['@type'] ?? '') === 'Person' && $parent_entity_id && !isset($schema['worksFor'])) {
            $parent_canonical = get_field('canonical_url', $parent_entity_id);
            $parent_entity_slug = get_field('entity_id', $parent_entity_id);

            // Only add if parent has valid entity_id
            if (!empty($parent_entity_slug)) {
                $schema['worksFor'] = [
                    '@id' => ($parent_canonical ?: home_url()) . '#' . $parent_entity_slug
                ];
            }
        }

        // Add hasOfferCatalog for parent services
        if (($schema['@type'] ?? '') === 'Service') {
            $child_services = $this->get_child_services($entity_post_id);
            if (!empty($child_services)) {
                // Filter out children without valid entity_id
                $valid_children = array_filter(array_map(function ($child_id) {
                    $child_canonical = get_field('canonical_url', $child_id);
                    $child_entity_slug = get_field('entity_id', $child_id);

                    if (!empty($child_entity_slug)) {
                        return ['@id' => ($child_canonical ?: home_url()) . '#' . $child_entity_slug];
                    }
                    return null;
                }, $child_services));

                if (!empty($valid_children)) {
                    $schema['hasOfferCatalog'] = [
                        '@type' => 'OfferCatalog',
                        'name' => $entity_post->post_title,
                        'itemListElement' => array_values($valid_children)
                    ];
                }
            }
        }

        return $schema;
    }

    /**
     * Get child services (services that have this entity as parent)
     */
    private function get_child_services($parent_entity_post_id)
    {
        $query = new WP_Query([
            'post_type' => 'ns_entity',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'parent_entity',
                    'value' => $parent_entity_post_id,
                    'compare' => '='
                ]
            ]
        ]);

        return wp_list_pluck($query->posts, 'ID');
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
        // Capture debug info
        $debug = [];
        $schema = $this->generate_for_current_url($debug);

        if ($schema) {
            echo "\n" . '<!-- NuStart Entity-First SEO Schema (ACF) v2.3.8 -->' . "\n";
            if (!empty($debug)) {
                echo '<!-- Debug Log:' . "\n";
                foreach ($debug as $line) {
                    echo " - " . esc_html($line) . "\n";
                }
                echo '-->' . "\n";
            }
            echo '<script type="application/ld+json">' . "\n";
            echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            echo "\n" . '</script>' . "\n";
        }
    }
}
