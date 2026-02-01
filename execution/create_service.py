"""
Create Hierarchical Service Entity

Creates a service entity with proper parent/child relationships and flexible JSON properties.

Usage:
    python create_service.py --entity-id service-new-website --name "New Website Development" \\
        --parent service-web-development --properties properties.json

    Or with inline properties:
    python create_service.py --entity-id service-new-website --name "New Website Development" \\
        --parent service-web-development --service-type "WordPress Website Development" \\
        --description "Custom WordPress websites..." \\
        --platform "WordPress" --accessibility "WCAG 2.1 AA"
"""

import os
import sys
import json
import argparse
import requests
from dotenv import load_dotenv

load_dotenv()

def create_service_entity(args):
    """Create or update a service entity with hierarchical relationships"""
    
    # Get WordPress credentials
    wp_url = os.getenv('WP_SITE_URL', 'https://nustart.solutions').rstrip('/')
    wp_user = os.getenv('WP_API_USER')
    wp_app_password = os.getenv('WP_API_PASSWORD')
    
    if not all([wp_user, wp_app_password]):
        print("[ERROR] Missing WP_API_USER or WP_API_PASSWORD in .env")
        return False
    
    # Build schema JSON
    schema_json = {
        "@type": "Service",
        "name": args.name,
    }
    
    # Load from JSON file if provided
    if args.properties_file:
        with open(args.properties_file, 'r') as f:
            file_props = json.load(f)
            schema_json.update(file_props)
    
    # Add inline properties
    if args.service_type:
        schema_json['serviceType'] = args.service_type
    if args.description:
        schema_json['description'] = args.description
    
    # Build additionalProperty array for custom properties
    additional_properties = []
    
    if args.platform:
        additional_properties.append({
            "@type": "PropertyValue",
            "name": "Platform",
            "value": args.platform
        })
    
    if args.design_system:
        additional_properties.append({
            "@type": "PropertyValue",
            "name": "Design System",
            "value": args.design_system
        })
    
    if args.accessibility:
        additional_properties.append({
            "@type": "PropertyValue",
            "name": "Accessibility Compliance",
            "value": args.accessibility
        })
    
    if args.mobile_first:
        additional_properties.append({
            "@type": "PropertyValue",
            "name": "Mobile-First Design",
            "value": "Yes"
        })
    
    if args.gdpr:
        additional_properties.append({
            "@type": "PropertyValue",
            "name": "GDPR Compliant",
            "value": "Yes"
        })
    
    if args.timeline:
        additional_properties.append({
            "@type": "PropertyValue",
            "name": "Launch Timeline",
            "value": args.timeline
        })
    
    # Add custom key-value pairs
    if args.custom:
        for pair in args.custom:
            key, value = pair.split('=', 1)
            additional_properties.append({
                "@type": "PropertyValue",
                "name": key,
                "value": value
            })
    
    if additional_properties:
        schema_json['additionalProperty'] = additional_properties
    
    # Find parent entity post ID if parent slug provided
    parent_post_id = None
    if args.parent:
        parent_query = requests.get(
            f"{wp_url}/wp-json/wp/v2/ns_entity",
            auth=(wp_user, wp_app_password),
            params={'meta_key': 'entity_id', 'meta_value': args.parent}
        )
        if parent_query.status_code == 200 and parent_query.json():
            parent_post_id = parent_query.json()[0]['id']
            print(f"[INFO] Found parent entity: {args.parent} (ID: {parent_post_id})")
        else:
            print(f"[WARNING] Parent entity '{args.parent}' not found")
    
    # Check if entity already exists
    existing_query = requests.get(
        f"{wp_url}/wp-json/wp/v2/ns_entity",
        auth=(wp_user, wp_app_password),
        params={'meta_key': 'entity_id', 'meta_value': args.entity_id}
    )
    
    entity_data = {
        'title': args.name,
        'status': 'publish',
        'ns_entity_type': [3],  # Service type (ID 3)
        'meta': {
            'entity_id': args.entity_id,
            'canonical_url': args.canonical_url or f"{wp_url}/services/{args.entity_id.replace('service-', '')}/",
            'schema_json': json.dumps(schema_json, indent=2),
        }
    }
    
    # Add parent relationship
    if parent_post_id:
        entity_data['meta']['parent_entity'] = parent_post_id
    
    # Create or update
    if existing_query.status_code == 200 and existing_query.json():
        # Update existing
        post_id = existing_query.json()[0]['id']
        print(f"[INFO] Updating existing entity (ID: {post_id})")
        
        response = requests.post(
            f"{wp_url}/wp-json/wp/v2/ns_entity/{post_id}",
            auth=(wp_user, wp_app_password),
            json=entity_data
        )
    else:
        # Create new
        print(f"[INFO] Creating new entity: {args.entity_id}")
        
        response = requests.post(
            f"{wp_url}/wp-json/wp/v2/ns_entity",
            auth=(wp_user, wp_app_password),
            json=entity_data
        )
    
    if response.status_code in [200, 201]:
        result = response.json()
        print(f"\n[OK] Entity created/updated successfully!")
        print(f"  ID: {result['id']}")
        print(f"  Title: {result['title']['rendered']}")
        print(f"  URL: {result['link']}")
        print(f"\n[SCHEMA JSON]")
        print(json.dumps(schema_json, indent=2))
        return True
    else:
        print(f"\n[ERROR] Failed to create/update entity")
        print(f"  Status: {response.status_code}")
        print(f"  Response: {response.text}")
        return False

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description='Create hierarchical service entity')
    
    # Required
    parser.add_argument('--entity-id', required=True, help='Entity ID (e.g., service-new-website)')
    parser.add_argument('--name', required=True, help='Service name')
    
    # Optional
    parser.add_argument('--parent', help='Parent entity ID (e.g., service-web-development)')
    parser.add_argument('--canonical-url', help='Canonical URL for this service')
    parser.add_argument('--service-type', help='Service type (e.g., "WordPress Website Development")')
    parser.add_argument('--description', help='Service description')
    parser.add_argument('--properties-file', help='JSON file with additional properties')
    
    # Common service properties
    parser.add_argument('--platform', help='Platform (e.g., WordPress)')
    parser.add_argument('--design-system', help='Design system (e.g., Custom CSS)')
    parser.add_argument('--accessibility', help='Accessibility compliance (e.g., WCAG 2.1 AA)')
    parser.add_argument('--mobile-first', action='store_true', help='Mobile-first design')
    parser.add_argument('--gdpr', action='store_true', help='GDPR compliant')
    parser.add_argument('--timeline', help='Launch timeline (e.g., "4-week turnaround")')
    
    # Custom properties
    parser.add_argument('--custom', action='append', help='Custom property (format: key=value)', default=[])
    
    args = parser.parse_args()
    
    success = create_service_entity(args)
    sys.exit(0 if success else 1)
