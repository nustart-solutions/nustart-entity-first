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

        // Add sameAs
        if (!empty($same_as)) {
            $same_as_urls = array_column($same_as, 'url');
            $schema['sameAs'] = array_filter($same_as_urls);
        }

        // Add parent relationship (isPartOf)
        if ($parent_entity_id) {
            $parent_canonical = get_field('canonical_url', $parent_entity_id);
            $parent_entity_slug = get_field('entity_id', $parent_entity_id);
            $schema['isPartOf'] = [
                '@id' => ($parent_canonical ?: home_url()) . '#' . $parent_entity_slug
            ];

            // Add category from parent name
            $parent_post = get_post($parent_entity_id);
            if ($parent_post) {
                $schema['category'] = $parent_post->post_title;
            }
        }

        // Merge all other properties from schema_json
        foreach ($schema_data as $key => $value) {
            if (!in_array($key, ['@type', '@id', 'name', 'url'])) {
                $schema[$key] = $value;
            }
        }

        // Handle provider relationship (for Services)
        if (($schema['@type'] ?? '') === 'Service' && !isset($schema['provider'])) {
            // Default to org-nustart
            $schema['provider'] = ['@id' => home_url() . '/#org-nustart'];
        }

        // Handle worksFor relationship (for Persons)
        if (($schema['@type'] ?? '') === 'Person' && $parent_entity_id && !isset($schema['worksFor'])) {
            $parent_canonical = get_field('canonical_url', $parent_entity_id);
            $parent_entity_slug = get_field('entity_id', $parent_entity_id);
            $schema['worksFor'] = [
                '@id' => ($parent_canonical ?: home_url()) . '#' . $parent_entity_slug
            ];
        }

        // Add hasOfferCatalog for parent services
        if (($schema['@type'] ?? '') === 'Service') {
            $child_services = $this->get_child_services($entity_post_id);
            if (!empty($child_services)) {
                $schema['hasOfferCatalog'] = [
                    '@type' => 'OfferCatalog',
                    'name' => $entity_post->post_title,
                    'itemListElement' => array_map(function ($child_id) {
                        $child_canonical = get_field('canonical_url', $child_id);
                        $child_entity_slug = get_field('entity_id', $child_id);
                        return ['@id' => ($child_canonical ?: home_url()) . '#' . $child_entity_slug];
                    }, $child_services)
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
            echo "\n" . '<!-- NuStart Entity-First SEO Schema (ACF) -->' . "\n";
            echo '<script type="application/ld+json">' . "\n";
            echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            echo "\n" . '</script>' . "\n";
        }
    }
}
