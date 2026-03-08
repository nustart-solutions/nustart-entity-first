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

        // Get publisher reference from front page's primary entity to use safely
        $publisher_ref = null;
        $homepage_id = get_option('page_on_front');
        if ($homepage_id) {
            $org_entity_id = get_field('primary_entity', $homepage_id);
            if ($org_entity_id) {
                $org_slug = get_field('entity_id', $org_entity_id);
                $org_url = get_field('canonical_url', $org_entity_id) ?: home_url();
                if ($org_slug) {
                    $publisher_ref = ['@id' => rtrim($org_url, '/') . '/#' . ltrim($org_slug, '#')];
                }
            }
        }

        // For blog posts, generate WebPage + BlogPosting schema
        if ($is_blog_post) {
            // Build about references for WebPage
            $webpage_about = [];
            foreach ($about_entity_ids as $entity_post_id) {
                // Normalized check using new loop variable
                $entity_id_raw = get_field('entity_id', $entity_post_id);
                if ($entity_id_raw) {
                    $webpage_about[] = ['@id' => home_url() . '/#' . ltrim($entity_id_raw, '#')];
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
            $webpage = [
                '@type' => 'WebPage',
                '@id' => get_permalink($post_id) . '#webpage',
                'url' => get_permalink($post_id),
                'name' => $post->post_title,
                'isPartOf' => ['@id' => trailingslashit(home_url()) . '#website'],
                'about' => $webpage_about,
                'inLanguage' => 'en-CA'
            ];
            if ($publisher_ref) {
                $webpage['publisher'] = $publisher_ref;
            }
            $graph[] = $webpage;

            // Build about references for BlogPosting
            $article_about = [];
            foreach ($about_entity_ids as $entity_post_id) {
                $entity_id_raw = get_field('entity_id', $entity_post_id);
                if ($entity_id_raw) {
                    $article_about[] = ['@id' => home_url() . '/#' . ltrim($entity_id_raw, '#')];
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
            $blog_posting = [
                '@type' => 'BlogPosting',
                '@id' => get_permalink($post_id) . '#article',
                'mainEntityOfPage' => ['@id' => get_permalink($post_id) . '#webpage'],
                'headline' => $post->post_title,
                'about' => $article_about,
                'mentions' => $article_mentions,
                'datePublished' => get_the_date('c', $post),
                'dateModified' => get_the_modified_date('c', $post),
                'inLanguage' => 'en-CA'
            ];

            // Post author minimal fallback
            $author_id = $post->post_author;
            $author_name = get_the_author_meta('display_name', $author_id);
            $blog_posting['author'] = [
                '@type' => 'Person',
                'name' => $author_name ?: 'Author'
            ];

            if ($publisher_ref) {
                $blog_posting['publisher'] = $publisher_ref;
            }
            $graph[] = $blog_posting;
        }

        // Add primary entity (skip for blog posts)
        if (!$is_blog_post && $primary_entity_id) {
            $primary_schemas = $this->entity_to_schema($primary_entity_id);
            if ($primary_schemas) {
                // Handle both single objects and arrays of objects
                if (isset($primary_schemas['@type'])) {
                    $graph[] = $primary_schemas;
                } else {
                    foreach ($primary_schemas as $s) {
                        $graph[] = $s;
                    }
                }
                $added_entities[] = $primary_entity_id;
            }
        }

        // Add about entities
        foreach ($about_entity_ids as $entity_post_id) {
            if (in_array($entity_post_id, $added_entities)) {
                continue;
            }
            $schemas = $this->entity_to_schema($entity_post_id);
            if ($schemas) {
                if (isset($schemas['@type'])) {
                    $graph[] = $schemas;
                } else {
                    foreach ($schemas as $s) {
                        $graph[] = $s;
                    }
                }
                $added_entities[] = $entity_post_id;
            }
        }

        // Add mentioned entities (skip if already added or if blog post)
        if (!$is_blog_post) {
            foreach ($mentions_entity_ids as $entity_post_id) {
                if (in_array($entity_post_id, $added_entities)) {
                    continue;
                }
                $schemas = $this->entity_to_schema($entity_post_id);
                if ($schemas) {
                    if (isset($schemas['@type'])) {
                        $graph[] = $schemas;
                    } else {
                        foreach ($schemas as $s) {
                            $graph[] = $s;
                        }
                    }
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

            foreach ($entities_to_check as $entity_id) {
                // Determine existing parent logic: get field, handle Array/Object/ID
                $parent_raw = get_field('parent_entity', $entity_id);
                $parent_entity_id = $get_id($parent_raw);

                // Prevent self-referencing parents logic loop (though in_array helps, catching early is safer)
                if ($parent_entity_id && $parent_entity_id != $entity_id && !in_array($parent_entity_id, $added_entities)) {
                    $parent_schemas = $this->entity_to_schema($parent_entity_id);
                    if ($parent_schemas) {
                        if (isset($parent_schemas['@type'])) {
                            $graph[] = $parent_schemas;
                        } else {
                            foreach ($parent_schemas as $s) {
                                $graph[] = $s;
                            }
                        }
                        $added_entities[] = $parent_entity_id;
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
                '@id' => trailingslashit(home_url()) . '#website',
                'url' => trailingslashit(home_url()),
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
                'isPartOf' => ['@id' => trailingslashit(home_url()) . '#website']
            ];

            // Only add about/mainEntity if we have a valid reference
            if ($about_ref) {
                $webpage_schema['about'] = $about_ref;
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

        // Add parent relationship (isPartOf) for child services
        if ($parent_entity_id && $parent_entity_id != $entity_post_id && ($schema['@type'] ?? '') === 'Service') {
            $parent_canonical = get_field('canonical_url', $parent_entity_id);
            $parent_entity_slug = get_field('entity_id', $parent_entity_id);

            if (!empty($parent_entity_slug)) {
                $schema['isPartOf'] = [
                    '@id' => ($parent_canonical ?: trailingslashit(home_url())) . '#' . $parent_entity_slug
                ];
            }
        }

        // Merge all other properties from schema_json (exclude @context and core properties)
        if (!isset($schema_data['@graph']) && !isset($schema_data[0])) {
            foreach ($schema_data as $key => $value) {
                if (!in_array($key, ['@context', '@type', '@id', 'name', 'url'])) {
                    $schema[$key] = $value;
                }
            }
        }

        // Handle provider relationship (for Services)
        // Ensure every service is connected to the primary Organization entity
        if (($schema['@type'] ?? '') === 'Service' && !isset($schema['provider'])) {
            $homepage_id = get_option('page_on_front');
            if ($homepage_id) {
                $org_entity_id = get_field('primary_entity', $homepage_id);
                if ($org_entity_id) {
                    $org_slug = get_field('entity_id', $org_entity_id);
                    $org_url = get_field('canonical_url', $org_entity_id) ?: home_url();
                    if ($org_slug) {
                        $schema['provider'] = [
                            '@id' => trailingslashit($org_url) . '#' . ltrim($org_slug, '#')
                        ];
                    }
                }
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

        // Add hasOfferCatalog for parent services
        if (($schema['@type'] ?? '') === 'Service') {
            $child_services = $this->get_child_services($entity_post_id);
            if (!empty($child_services)) {
                $catalog_items = [];

                foreach ($child_services as $child_id) {
                    $child_post = get_post($child_id);
                    if (!$child_post)
                        continue;

                    // Get Minimal Child Data
                    $child_canonical = get_field('canonical_url', $child_id);
                    $child_entity_id = get_field('entity_id', $child_id);
                    $child_desc = get_field('seo_overrides', $child_id)['meta_description_override'] ?? '';

                    if ($child_canonical && $child_entity_id) {
                        $child_schema = [
                            '@type' => 'Service',
                            '@id' => $child_canonical . '#' . $child_entity_id,
                            'name' => $child_post->post_title,
                            'url' => $child_canonical,
                            'description' => $child_desc
                        ];

                        $child_schema['serviceType'] = $child_post->post_title; // Fallback

                        $catalog_items[] = [
                            '@type' => 'Offer',
                            'itemOffered' => $child_schema
                        ];
                    }
                }

                if (!empty($catalog_items)) {
                    $schema['hasOfferCatalog'] = [
                        '@type' => 'OfferCatalog',
                        'name' => $entity_post->post_title,
                        'itemListElement' => $catalog_items
                    ];
                }
            }
        }

        // If the parsed JSON was actually a graph or array of multiple objects,
        // we should return all of them instead of just the merged one.
        if (isset($schema_data['@graph']) && is_array($schema_data['@graph'])) {
            $result_schemas = [];
            // We need to apply our standard properties (like sameAs) to the primary object within the graph
            $primary_type = $entity_type;
            if (isset($schema_data['@type'])) {
                $primary_type = $schema_data['@type'];
            }

            // If it's a multi-type array like ["LocalBusiness", "EmploymentAgency"], handle it
            $is_primary = function ($item_type) use ($primary_type) {
                if (is_array($item_type)) {
                    return in_array($primary_type, $item_type);
                }
                return $item_type === $primary_type;
            };

            $merged_primary = false;
            foreach ($schema_data['@graph'] as $item) {
                if (!$merged_primary && isset($item['@type']) && $is_primary($item['@type'])) {
                    // Update the schema we built earlier with this item's props
                    foreach ($item as $k => $v) {
                        if (!in_array($k, ['@context', '@type', '@id', 'name', 'url'])) {
                            $schema[$k] = $v;
                        }
                    }
                    $result_schemas[] = $schema;
                    $merged_primary = true;
                } else {
                    $result_schemas[] = $item;
                }
            }

            if (!$merged_primary) {
                // If we didn't find the primary in the graph to merge with our base schema, just prepend our base schema
                array_unshift($result_schemas, $schema);
            }
            return $result_schemas;

        } elseif (isset($schema_data[0]) && is_array($schema_data[0])) {
            // It's a direct array of objects [{...}, {...}]
            $result_schemas = [];
            $primary_type = $entity_type;

            $is_primary = function ($item_type) use ($primary_type) {
                if (is_array($item_type)) {
                    return in_array($primary_type, $item_type);
                }
                return $item_type === $primary_type;
            };

            $merged_primary = false;
            foreach ($schema_data as $item) {
                if (!$merged_primary && isset($item['@type']) && $is_primary($item['@type'])) {
                    foreach ($item as $k => $v) {
                        if (!in_array($k, ['@context', '@type', '@id', 'name', 'url'])) {
                            $schema[$k] = $v;
                        }
                    }
                    $result_schemas[] = $schema;
                    $merged_primary = true;
                } else {
                    $result_schemas[] = $item;
                }
            }

            if (!$merged_primary) {
                array_unshift($result_schemas, $schema);
            }
            return $result_schemas;
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
        $schema = $this->generate_for_current_url();

        if ($schema) {
            echo "\n" . '<!-- NuStart Entity-First SEO Schema (ACF) v2.3.12 -->' . "\n";
            echo '<script type="application/ld+json">' . "\n";
            echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            echo "\n" . '</script>' . "\n";
        }
    }
}
