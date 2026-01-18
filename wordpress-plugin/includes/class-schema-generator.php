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

        // Add primary entity
        if ($page['primary_entity_id']) {
            $primary_schema = $this->entity_model->to_schema($page['primary_entity_id']);
            if ($primary_schema) {
                $graph[] = $primary_schema;
                $added_entities[] = $page['primary_entity_id']; // Mark as added
            }
        }

        // Add about entities (skip if already added)
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

        // Add mentioned entities (skip if already added)
        if (!empty($page['mentions_entity_ids'])) {
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

        // Add WebPage entity
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
