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

            // Add service to WebPage.mainEntity
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
                'name' => get_the_title($post_id),
                'description' => !empty($seo_overrides['meta_description_override']) ? $seo_overrides['meta_description_override'] : (get_post_meta($post_id, '_yoast_wpseo_metadesc', true) ?: (get_post_meta($post_id, 'rank_math_description', true) ?: (has_excerpt($post_id) ? get_the_excerpt($post_id) : wp_trim_words($post->post_content, 25)))),
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
                'description' => !empty($seo_overrides['meta_description_override']) ? $seo_overrides['meta_description_override'] : (get_post_meta($post_id, '_yoast_wpseo_metadesc', true) ?: (get_post_meta($post_id, 'rank_math_description', true) ?: (has_excerpt($post_id) ? get_the_excerpt($post_id) : wp_trim_words($post->post_content, 25)))),
                'datePublished' => get_the_date('c', $post),
                'dateModified' => get_the_modified_date('c', $post),
                'inLanguage' => 'en-CA'
            ];
        }

        // Add primary entity (skip for blog posts)
        if (!$is_blog_post && $primary_entity_id) {
            $webpage_id = get_permalink($post_id) . '#webpage';
            $primary_schema = $this->entity_to_schema($primary_entity_id, $webpage_id);
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
                'name' => !empty($seo_overrides['title_override']) ? $seo_overrides['title_override'] : get_the_title($post_id),
                'description' => !empty($seo_overrides['meta_description_override']) ? $seo_overrides['meta_description_override'] : (get_post_meta($post_id, '_yoast_wpseo_metadesc', true) ?: (get_post_meta($post_id, 'rank_math_description', true) ?: (has_excerpt($post_id) ? get_the_excerpt($post_id) : wp_trim_words($post->post_content, 25)))),
                'isPartOf' => ['@id' => home_url() . '#website']
            ];

            // Only add mainEntity if we have a valid reference
            if ($about_ref) {
                $webpage_schema['mainEntity'] = $about_ref;
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
    private function entity_to_schema($entity_post_id, $linked_webpage_id = null)
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

        // Add mainEntityOfPage if linked to a specific WebPage (Primary Entity)
        if ($linked_webpage_id) {
            $schema['mainEntityOfPage'] = ['@id' => $linked_webpage_id];
        }

        // Check for child services (Hub detection)
        $child_services = ($schema['@type'] ?? '') === 'Service' ? $this->get_child_services($entity_post_id) : [];
        $is_hub_service = !empty($child_services);

        // Add parent relationship (isRelatedTo) - only for Service entities that are NOT Hubs
        // Person entities use worksFor instead
        if ($parent_entity_id && ($schema['@type'] ?? '') === 'Service' && !$is_hub_service) {
            $parent_canonical = get_field('canonical_url', $parent_entity_id);
            $parent_entity_slug = get_field('entity_id', $parent_entity_id);

            // Only add if parent has valid entity_id
            if (!empty($parent_entity_slug)) {
                $schema['isRelatedTo'] = [
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

        // HARD FIX: Explicitly remove isPartOf for Services to prevent it coming from manual JSON
        // This causes validation errors because Services are Intangibles, not CreativeWorks
        // Expanded to handle array types just in case
        $type = $schema['@type'] ?? '';
        if (($type === 'Service' || (is_array($type) && in_array('Service', $type))) && isset($schema['isPartOf'])) {
            unset($schema['isPartOf']);
        }

        if (($schema['@type'] ?? '') === 'Service') {
            $provider_ref = null;

            // Try to find specific parent Organization first
            if ($parent_entity_id) {
                $parent_type = '';
                $parent_terms = get_the_terms($parent_entity_id, 'ns_entity_type');
                if ($parent_terms && !is_wp_error($parent_terms)) {
                    $parent_type = $parent_terms[0]->name;
                }

                if ($parent_type === 'Organization') {
                    $parent_canonical = get_field('canonical_url', $parent_entity_id);
                    $parent_slug = get_field('entity_id', $parent_entity_id);
                    if ($parent_slug) {
                        $provider_ref = ['@id' => ($parent_canonical ?: home_url()) . '#' . $parent_slug];
                    }
                }
            }

            // If strictly no parent org, try a global option or fallback (Removed hardcoded nustart string)
            // Ideally we would check: $provider_ref = $provider_ref ?: $this->get_global_organization_ref();

            if ($provider_ref) {
                $schema['provider'] = $provider_ref;
            } else {
                // Determine if we should warn
                // We can't output HTML comment INSIDE the JSON-LD array structure here directly as this function returns array
                // But we can add it to a debug key or handle it in output
                // For now, let's just NOT add the provider key.
                // The output_schema function handles the HTML wrapping, so adding a comment there is hard based on this internal state.
                // WE WILL ADD A DEBUG ENTRY which appears in the HTML comment log
                // Or if user wants <!-- Need Organization --> specifically in output, we might need a placeholder or handle it in output_schema

                // User said: "put an <!--Need Organization--> note in the head"
                // The generating function is deep inside.
                // I'll leave provider unset.
            }
        }

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

        // Add hasOfferCatalog for HUB services (Services with children)
        if ($is_hub_service) {

            // Build the catalog items (Offers)
            $catalog_items = [];
            foreach ($child_services as $child_id) {
                // Determine URL for child
                $child_canonical = get_field('canonical_url', $child_id);
                // Fallback to post permalink if canonical missing? No, entity_to_schema uses canonical.
                // But Offer usually points to the Service URL.
                // We'll use the child entity's URL field if present, or just link to it.
                // User example: Offer > itemOffered > Service

                $child_post = get_post($child_id);
                $child_url = $child_canonical ?: get_permalink($child_id);

                $catalog_items[] = [
                    '@type' => 'Offer',
                    'itemOffered' => [
                        '@type' => 'Service',
                        'name' => $child_post->post_title,
                        'description' => get_field('schema_description', $child_id) ?: $child_post->post_excerpt, // Try to find a description
                        'url' => $child_url
                    ]
                ];
            }

            if (!empty($catalog_items)) {
                $schema['hasOfferCatalog'] = [
                    '@type' => 'OfferCatalog',
                    'name' => $entity_post->post_title . ' Services', // e.g. "WordPress Development Services"
                    'itemListElement' => $catalog_items
                ];
            }
        }

        return $schema;
    }

    /**
     * Get child services (services that have this entity as parent)
     */
    private function get_child_services($parent_entity_post_id)
    {
        // ACF relationship fields are serialized arrays, so use LIKE
        // The ID will be wrapped in quotes like "123" inside the serialized string
        $query = new WP_Query([
            'post_type' => 'ns_entity',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'parent_entity',
                    'value' => '"' . $parent_entity_post_id . '"',
                    'compare' => 'LIKE'
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
            echo "\n" . '<!-- NuStart Entity-First SEO Schema (ACF) v2.3.17 -->' . "\n";

            // Output warning if any Service entity is missing a provider
            if (isset($schema['@graph']) && is_array($schema['@graph'])) {
                foreach ($schema['@graph'] as $entity) {
                    if (($entity['@type'] ?? '') === 'Service' && !isset($entity['provider'])) {
                        echo '<!-- Need Organization check -->' . "\n";
                        break; // Only output once
                    }
                }
            }

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
