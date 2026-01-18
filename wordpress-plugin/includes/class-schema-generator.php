<?php
/**
 * Schema Generator - Generates JSON-LD schema for pages
 */
class NS_Schema_Generator
{
    private $entity_model;
    private $page_model;

    public function __construct()
    {
        $this->entity_model = new NS_Entity_Model();
        $this->page_model = new NS_Page_Entity_Map_Model();
    }

    /**
     * Generate complete schema graph for current URL
     */
    public function generate_for_current_url()
    {
        $url = home_url(add_query_arg(NULL, NULL));
        return $this->generate_for_url($url);
    }

    /**
     * Generate complete schema graph for a URL
     */
    public function generate_for_url($url)
    {
        $page = $this->page_model->get_by_url($url);
        if (!$page)
            return null;

        $graph = [];
        $added_entities = []; // Track which entities we've already added

        // Check if this is a BlogPosting page type
        $is_blog_post = ($page['page_type'] === 'BlogPosting');

        // Get post ID - either from mapping or lookup from URL
        $post_id = $page['wp_post_id'];
        if ($is_blog_post && !$post_id) {
            // Fallback: try to get post ID from URL
            $post_id = url_to_postid($url);
        }

        // For blog posts, generate WebPage + BlogPosting schema dynamically
        if ($is_blog_post && $post_id) {
            $post = get_post($post_id);
            if ($post) {
                // Build about references for WebPage (topic + service)
                $webpage_about = [];
                if (!empty($page['about_entity_ids'])) {
                    foreach ($page['about_entity_ids'] as $entity_id) {
                        $entity = $this->entity_model->get($entity_id);
                        if ($entity) {
                            $webpage_about[] = ['@id' => home_url() . '/#' . $entity_id];
                        }
                    }
                }
                // Add service to WebPage.about
                if (!empty($page['mentions_entity_ids'])) {
                    foreach ($page['mentions_entity_ids'] as $entity_id) {
                        $entity = $this->entity_model->get($entity_id);
                        if ($entity) {
                            $entity_schema = $this->entity_model->to_schema($entity_id);
                            $webpage_about[] = ['@id' => $entity_schema['@id']];
                        }
                    }
                }

                // Add WebPage entity for blog posts
                $graph[] = [
                    '@type' => 'WebPage',
                    '@id' => $url . '#webpage',
                    'url' => $url,
                    'name' => $post->post_title,
                    'isPartOf' => ['@id' => home_url() . '#website'],
                    'about' => $webpage_about,
                    'publisher' => ['@id' => home_url() . '#org-nustart'],
                    'inLanguage' => 'en-CA'
                ];

                // Build about references for BlogPosting (topic only)
                $article_about = [];
                if (!empty($page['about_entity_ids'])) {
                    foreach ($page['about_entity_ids'] as $entity_id) {
                        $entity = $this->entity_model->get($entity_id);
                        if ($entity) {
                            $article_about[] = ['@id' => home_url() . '/#' . $entity_id];
                        }
                    }
                }

                // Build mentions (service @id + inline WCAG Thing)
                $mentions = [];
                if (!empty($page['mentions_entity_ids'])) {
                    foreach ($page['mentions_entity_ids'] as $entity_id) {
                        $entity = $this->entity_model->get($entity_id);
                        if ($entity) {
                            $entity_schema = $this->entity_model->to_schema($entity_id);
                            $mentions[] = ['@id' => $entity_schema['@id']];
                        }
                    }
                }

                // Add inline WCAG Thing entity
                $mentions[] = [
                    '@type' => 'Thing',
                    'name' => 'Web Content Accessibility Guidelines (WCAG)',
                    'sameAs' => 'https://www.wikidata.org/wiki/Q5364439'
                ];

                // Add BlogPosting entity
                $article_schema = [
                    '@type' => 'BlogPosting',
                    '@id' => $url . '#article',
                    'mainEntityOfPage' => ['@id' => $url . '#webpage'],
                    'headline' => $post->post_title,
                    'author' => [
                        '@id' => home_url() . '/about-nustart-web-solutions/#person-anne'
                    ],
                    'publisher' => [
                        '@id' => home_url() . '#org-nustart'
                    ],
                    'about' => $article_about,
                    'mentions' => $mentions,
                    'datePublished' => get_the_date('c', $post),
                    'dateModified' => get_the_modified_date('c', $post),
                    'inLanguage' => 'en-CA'
                ];

                $graph[] = $article_schema;
            }
        }

        // Add primary entity (skip for blog posts as we don't want full service schemas)
        if (!$is_blog_post && $page['primary_entity_id']) {
            $primary_schema = $this->entity_model->to_schema($page['primary_entity_id']);
            if ($primary_schema) {
                $graph[] = $primary_schema;
                $added_entities[] = $page['primary_entity_id'];
            }
        }

        // Add about entities (these are topic entities for blog posts)
        if (!empty($page['about_entity_ids'])) {
            foreach ($page['about_entity_ids'] as $entity_id) {
                if (in_array($entity_id, $added_entities)) {
                    continue; // Skip duplicates
                }
                $schema = $this->entity_model->to_schema($entity_id);
                if ($schema) {
                    $graph[] = $schema;
                    $added_entities[] = $entity_id;
                }
            }
        }

        // Add mentioned entities (skip if already added) - for blog posts, these are just referenced, not fully output
        if (!$is_blog_post && !empty($page['mentions_entity_ids'])) {
            foreach ($page['mentions_entity_ids'] as $entity_id) {
                if (in_array($entity_id, $added_entities)) {
                    continue; // Skip duplicates
                }
                $schema = $this->entity_model->to_schema($entity_id);
                if ($schema) {
                    $graph[] = $schema;
                    $added_entities[] = $entity_id;
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

        // Add WebPage entity (skip for blog posts as we have BlogPosting)
        if (!$is_blog_post) {
            $graph[] = [
                '@type' => 'WebPage',
                '@id' => $url . '#webpage',
                'url' => $url,
                'name' => $page['title_override'] ?? wp_get_document_title(),
                'description' => $page['meta_description_override'],
                'isPartOf' => ['@id' => home_url() . '#website'],
                'about' => $page['primary_entity_id'] ?
                    ['@id' => home_url() . '#' . $page['primary_entity_id']] : null
            ];
        }

        // Add FAQPage if FAQ data exists
        if (!empty($page['faq_data'])) {
            $graph[] = [
                '@type' => 'FAQPage',
                'mainEntity' => $this->format_faq_schema($page['faq_data'])
            ];
        }

        // Wrap in @graph
        return [
            '@context' => 'https://schema.org',
            '@graph' => array_filter($graph) // Remove nulls
        ];
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
            echo "\n" . '<!-- NuStart Entity-First SEO Schema -->' . "\n";
            echo '<script type="application/ld+json">' . "\n";
            echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            echo "\n" . '</script>' . "\n";
        }
    }
}
