"""
Map URL to Entity via WordPress REST API

Usage:
    python map_url_to_entity.py --url "https://nustart.solutions/services/ada" --page-type "service" --primary-entity "service-ada"

Examples:
    # Map a service page
    python map_url_to_entity.py \
        --url "https://nustart.solutions/services/emergency" \
        --page-type "service" \
        --primary-entity "service-emergency" \
        --about "service-emergency,org-nustart"
    
    # Map a blog post with FAQs
    python map_url_to_entity.py \
        --url "https://nustart.solutions/blog/wordpress-security" \
        --page-type "blog_post" \
        --primary-entity "org-nustart" \
        --mentions "person-anne" \
        --faq "What is WordPress security?|Security best practices for WordPress sites"
"""

import os
import sys
import json
import argparse
import requests
from dotenv import load_dotenv
from requests.auth import HTTPBasicAuth

load_dotenv()

def map_url_to_entity(args):
    """Map a URL to entities via REST API"""
    
    # Get WordPress credentials from .env
    wp_url = os.getenv('WP_API_URL', 'https://nustart.solutions/wp-json')
    wp_user = os.getenv('WP_API_USER')
    wp_password = os.getenv('WP_API_PASSWORD')
    
    if not all([wp_user, wp_password]):
        print("[ERROR] Missing WordPress credentials in .env file")
        print("Required: WP_API_USER, WP_API_PASSWORD")
        return False
    
    # Build mapping data
    mapping_data = {
        'url': args.url,
        'page_type': args.page_type,
        'primary_entity_id': args.primary_entity,
        'robots': args.robots,
        'sitemap_include': args.sitemap
    }
    
    # Parse entity lists
    if args.about:
        mapping_data['about_entity_ids'] = args.about.split(',')
    
    if args.mentions:
        mapping_data['mentions_entity_ids'] = args.mentions.split(',')
    
    # Parse FAQ data
    if args.faq:
        faq_data = []
        faq_pairs = args.faq.split(';')
        for pair in faq_pairs:
            if '|' in pair:
                q, a = pair.split('|', 1)
                faq_data.append({'question': q.strip(), 'answer': a.strip()})
        mapping_data['faq_data'] = faq_data
    
    if args.title:
        mapping_data['title_override'] = args.title
    
    if args.description:
        mapping_data['meta_description_override'] = args.description
    
    # Auto-lookup wp_post_id for blog posts
    if args.page_type == 'BlogPosting':
        print(f"[INFO] Looking up wp_post_id for BlogPosting...")
        slug = args.url.rstrip('/').split('/')[-1]
        posts_endpoint = f"{wp_url.rstrip('/')}/wp/v2/posts"
        
        try:
            posts_response = requests.get(
                f"{posts_endpoint}?slug={slug}",
                auth=HTTPBasicAuth(wp_user, wp_password)
            )
            
            if posts_response.status_code == 200:
                posts = posts_response.json()
                if posts and len(posts) > 0:
                    post_id = posts[0]['id']
                    mapping_data['wp_post_id'] = post_id
                    print(f"[OK] Found wp_post_id: {post_id}")
                else:
                    print(f"[WARNING] No post found with slug: {slug}")
                    print(f"[WARNING] BlogPosting schema may not generate correctly")
            else:
                print(f"[WARNING] Failed to lookup post: {posts_response.status_code}")
        except Exception as e:
            print(f"[WARNING] Error looking up post ID: {e}")

    
    try:
        # Make API request
        api_endpoint = f"{wp_url.rstrip('/')}/nustart-entity/v1/page-mappings"
        
        print(f"[INFO] Sending request to: {api_endpoint}")
        
        response = requests.post(
            api_endpoint,
            json=mapping_data,
            auth=HTTPBasicAuth(wp_user, wp_password),
            headers={'Content-Type': 'application/json'}
        )
        
        if response.status_code in [200, 201]:
            result = response.json()
            print(f"\n[OK] Page mapping created/updated successfully!")
            print(f"\n{'='*60}")
            print(f"URL: {result['url']}")
            print(f"Page Type: {result['page_type']}")
            print(f"Primary Entity: {result['primary_entity_id']}")
            if result.get('about_entity_ids'):
                print(f"About: {', '.join(result['about_entity_ids'])}")
            if result.get('mentions_entity_ids'):
                print(f"Mentions: {', '.join(result['mentions_entity_ids'])}")
            if result.get('faq_data'):
                print(f"FAQs: {len(result['faq_data'])} questions")
            print(f"{'='*60}\n")
            return True
        else:
            print(f"[ERROR] API request failed: {response.status_code}")
            print(f"Response: {response.text}")
            return False
            
    except Exception as e:
        print(f"[ERROR] {str(e)}")
        import traceback
        traceback.print_exc()
        return False

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description='Map URL to entities via REST API')
    
    # Required arguments
    parser.add_argument('--url', required=True, help='Full URL to map')
    parser.add_argument('--page-type', required=True, help='Page type (home, service, blog_post, etc.)')
    parser.add_argument('--primary-entity', required=True, help='Primary entity ID')
    
    # Optional arguments
    parser.add_argument('--about', help='Comma-separated entity IDs this page is about')
    parser.add_argument('--mentions', help='Comma-separated entity IDs mentioned')
    parser.add_argument('--faq', help='FAQ data: "Q1?|A1;Q2?|A2"')
    parser.add_argument('--robots', default='index,follow', help='Robots directive')
    parser.add_argument('--sitemap', type=bool, default=True, help='Include in sitemap')
    parser.add_argument('--title', help='Override page title')
    parser.add_argument('--description', help='Override meta description')
    
    args = parser.parse_args()
    
    success = map_url_to_entity(args)
    sys.exit(0 if success else 1)
