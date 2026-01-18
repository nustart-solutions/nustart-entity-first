"""
Create or Update Entity via WordPress REST API

Usage:
    python create_entity.py --entity-id "service-ada" --type "Service" --name "ADA Compliance" --description "WCAG compliance services"

Examples:
    # Create a Service entity
    python create_entity.py \
        --entity-id "service-emergency" \
        --type "Service" \
        --name "WordPress Emergency Support" \
        --description "24/7 WordPress emergency fixes" \
        --service-type "Emergency Support"
    
    # Create a Person entity
    python create_entity.py \
        --entity-id "person-john" \
        --type "Person" \
        --name "John Doe" \
        --job-title "WordPress Developer" \
        --linkedin "https://linkedin.com/in/johndoe"
"""

import os
import sys
import json
import argparse
import requests
from dotenv import load_dotenv
from requests.auth import HTTPBasicAuth

load_dotenv()

def create_entity(args):
    """Create or update an entity via REST API"""
    
    # Get WordPress credentials from .env
    wp_url = os.getenv('WP_API_URL', 'https://nustart.solutions/wp-json')
    wp_user = os.getenv('WP_API_USER')
    wp_password = os.getenv('WP_API_PASSWORD')
    
    if not all([wp_user, wp_password]):
        print("[ERROR] Missing WordPress credentials in .env file")
        print("Required: WP_API_USER, WP_API_PASSWORD")
        return False
    
    # Build entity data
    entity_data = {
        'entity_id': args.entity_id,
        'entity_type': args.type,
        'name': args.name,
        'slug': args.slug or args.entity_id.replace('_', '-'),
        'canonical_url': args.url,
        'status': args.status
    }
    
    # Build properties
    properties = {}
    if args.description:
        properties['description'] = args.description
    if args.job_title:
        properties['jobTitle'] = args.job_title
    if args.service_type:
        properties['serviceType'] = args.service_type
    if args.email:
        properties['email'] = args.email
    if args.phone:
        properties['telephone'] = args.phone
    if args.logo:
        properties['logo'] = {'@type': 'ImageObject', 'url': args.logo}
    if args.image:
        properties['image'] = args.image
    
    if properties:
        entity_data['properties'] = properties
    
    # Build sameAs array
    same_as = []
    if args.twitter:
        same_as.append(args.twitter)
    if args.linkedin:
        same_as.append(args.linkedin)
    if args.facebook:
        same_as.append(args.facebook)
    
    if same_as:
        entity_data['same_as'] = same_as
    
    if args.parent:
        entity_data['parent_entity_id'] = args.parent
    
    try:
        # Make API request
        api_endpoint = f"{wp_url.rstrip('/')}/nustart-entity/v1/entities"
        
        print(f"[INFO] Sending request to: {api_endpoint}")
        
        response = requests.post(
            api_endpoint,
            json=entity_data,
            auth=HTTPBasicAuth(wp_user, wp_password),
            headers={'Content-Type': 'application/json'}
        )
        
        if response.status_code in [200, 201]:
            try:
                result = response.json()
            except:
                print(f"[ERROR] Could not parse JSON response")
                print(f"Response text: {response.text}")
                return False
            print(f"\n[OK] Entity created/updated successfully!")
            print(f"\n{'='*60}")
            print(f"Entity ID: {result['entity_id']}")
            print(f"Type: {result['entity_type']}")
            print(f"Name: {result['name']}")
            print(f"Slug: {result['slug']}")
            if result.get('canonical_url'):
                print(f"URL: {result['canonical_url']}")
            print(f"Status: {result['status']}")
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
    parser = argparse.ArgumentParser(description='Create or update an entity via REST API')
    
    # Required arguments
    parser.add_argument('--entity-id', required=True, help='Unique entity ID (e.g., "service-ada")')
    parser.add_argument('--type', required=True, help='Entity type (Organization, Person, Service, etc.)')
    parser.add_argument('--name', required=True, help='Display name')
    
    # Optional arguments
    parser.add_argument('--slug', help='URL-friendly slug (defaults to entity-id)')
    parser.add_argument('--url', help='Canonical URL for this entity')
    parser.add_argument('--description', help='Description')
    parser.add_argument('--parent', help='Parent entity ID')
    parser.add_argument('--status', default='published', choices=['draft', 'published'])
    
    # Type-specific properties
    parser.add_argument('--job-title', help='Job title (for Person)')
    parser.add_argument('--service-type', help='Service type (for Service)')
    parser.add_argument('--email', help='Email address')
    parser.add_argument('--phone', help='Phone number')
    parser.add_argument('--logo', help='Logo URL')
    parser.add_argument('--image', help='Image URL')
    
    # Social profiles
    parser.add_argument('--twitter', help='Twitter URL')
    parser.add_argument('--linkedin', help='LinkedIn URL')
    parser.add_argument('--facebook', help='Facebook URL')
    
    args = parser.parse_args()
    
    success = create_entity(args)
    sys.exit(0 if success else 1)
