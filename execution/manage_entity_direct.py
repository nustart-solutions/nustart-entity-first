"""
Direct Database Entity Manager

Creates entities directly in WordPress database, bypassing REST API.
Uses MySQL connection from .env file.

Usage:
    python manage_entity_direct.py create --entity-id "org-nustart" --type "Organization" --name "NuStart Solutions"
"""

import os
import sys
import json
import argparse
import mysql.connector
from dotenv import load_dotenv

load_dotenv()

def get_db_connection():
    """Get database connection from .env"""
    # Try to get from .env or use defaults
    config = {
        'host': os.getenv('WP_DB_HOST', 'localhost'),
        'user': os.getenv('WP_DB_USER'),
        'password': os.getenv('WP_DB_PASSWORD'),
        'database': os.getenv('WP_DB_NAME')
    }
    
    if not all([config['user'], config['password'], config['database']]):
        print("[ERROR] Missing database credentials in .env")
        print("Need: WP_DB_HOST, WP_DB_USER, WP_DB_PASSWORD, WP_DB_NAME")
        return None
    
    return mysql.connector.connect(**config)

def create_entity(args):
    """Create or update entity directly in database"""
    
    conn = get_db_connection()
    if not conn:
        return False
    
    cursor = conn.cursor()
    table = os.getenv('WP_TABLE_PREFIX', 'wp_') + 'ns_entities'
    
    # Build entity data
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
    
    same_as = []
    if args.linkedin:
        same_as.append(args.linkedin)
    if args.twitter:
        same_as.append(args.twitter)
    if args.facebook:
        same_as.append(args.facebook)
    if args.instagram:
        same_as.append(args.instagram)
    if args.github:
        same_as.append(args.github)
    if args.reddit:
        same_as.append(args.reddit)
    if args.google_maps:
        same_as.append(args.google_maps)
    if args.same_as:
        # Support multiple URLs via comma-separated list
        for url in args.same_as.split(','):
            same_as.append(url.strip())
    
    try:
        # Check if exists
        cursor.execute(f"SELECT entity_id FROM {table} WHERE entity_id = %s", (args.entity_id,))
        exists = cursor.fetchone()
        
        if exists:
            # Update
            sql = f"""
                UPDATE {table}
                SET entity_type = %s, name = %s, slug = %s, canonical_url = %s,
                    same_as = %s, properties = %s, status = %s
                WHERE entity_id = %s
            """
            values = (
                args.type, args.name, args.slug or args.entity_id,
                args.url, json.dumps(same_as), json.dumps(properties),
                args.status, args.entity_id
            )
            cursor.execute(sql, values)
            print(f"[OK] Updated entity: {args.entity_id}")
        else:
            # Insert
            sql = f"""
                INSERT INTO {table}
                (entity_id, entity_type, name, slug, canonical_url, same_as, properties, status)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
            """
            values = (
                args.entity_id, args.type, args.name, args.slug or args.entity_id,
                args.url, json.dumps(same_as), json.dumps(properties), args.status
            )
            cursor.execute(sql, values)
            print(f"[OK] Created entity: {args.entity_id}")
        
        conn.commit()
        
        # Show result
        cursor.execute(f"SELECT * FROM {table} WHERE entity_id = %s", (args.entity_id,))
        result = cursor.fetchone()
        print(f"\n{'='*60}")
        print(f"Entity ID: {result[0]}")
        print(f"Type: {result[1]}")
        print(f"Name: {result[2]}")
        print(f"URL: {result[4]}")
        print(f"Status: {result[8]}")
        print(f"{'='*60}\n")
        
        cursor.close()
        conn.close()
        return True
        
    except Exception as e:
        print(f"[ERROR] {e}")
        import traceback
        traceback.print_exc()
        return False

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description='Manage entities directly in database')
    parser.add_argument('action', choices=['create'], help='Action to perform')
    parser.add_argument('--entity-id', required=True, help='Entity ID')
    parser.add_argument('--type', required=True, help='Entity type')
    parser.add_argument('--name', required=True, help='Name')
    parser.add_argument('--slug', help='Slug')
    parser.add_argument('--url', help='Canonical URL')
    parser.add_argument('--description', help='Description')
    parser.add_argument('--status', default='published', choices=['draft', 'published'])
    parser.add_argument('--job-title', help='Job title (Person)')
    parser.add_argument('--service-type', help='Service type (Service)')
    parser.add_argument('--email', help='Email')
    parser.add_argument('--phone', help='Phone')
    parser.add_argument('--linkedin', help='LinkedIn URL')
    parser.add_argument('--twitter', help='Twitter URL')
    parser.add_argument('--facebook', help='Facebook URL')
    parser.add_argument('--instagram', help='Instagram URL')
    parser.add_argument('--github', help='GitHub URL')
    parser.add_argument('--reddit', help='Reddit URL')
    parser.add_argument('--google-maps', help='Google Maps URL')
    parser.add_argument('--same-as', help='Additional sameAs URLs (comma-separated)')
    
    args = parser.parse_args()
    
    if args.action == 'create':
        success = create_entity(args)
        sys.exit(0 if success else 1)
