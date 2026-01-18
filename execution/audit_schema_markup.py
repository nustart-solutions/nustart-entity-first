"""
Schema Markup Audit Tool

Analyzes existing schema markup on a webpage and identifies gaps.

Usage:
    python audit_schema_markup.py --url https://example.com
    python audit_schema_markup.py --url https://example.com --validate

Requirements:
    - extruct library for structured data extraction
    - requests, beautifulsoup4
"""

import os
import sys
import json
import argparse
from datetime import datetime
from typing import Dict, List, Optional
import requests
from bs4 import BeautifulSoup
import extruct

class SchemaAuditor:
    """Audit schema markup on webpages"""
    
    def __init__(self):
        """Initialize auditor"""
        self.schema_recommendations = {
            'Organization': ['name', 'url', 'logo', 'description', 'sameAs'],
            'Article': ['headline', 'author', 'publisher', 'datePublished', 'image'],
            'Person': ['name', 'jobTitle', 'url', 'sameAs'],
            'Service': ['name', 'provider', 'serviceType', 'description'],
            'LocalBusiness': ['name', 'address', 'telephone', 'openingHours'],
            'Product': ['name', 'description', 'image', 'brand', 'offers'],
            'BreadcrumbList': ['itemListElement'],
            'WebPage': ['name', 'description', 'url']
        }
    
    def fetch_page(self, url: str) -> str:
        """Fetch HTML content from URL"""
        try:
            response = requests.get(url, timeout=10, headers={
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            })
            response.raise_for_status()
            return response.text
        
        except Exception as e:
            raise Exception(f"Failed to fetch page: {str(e)}")
    
    def extract_schema(self, html: str, url: str) -> Dict:
        """Extract all structured data from HTML"""
        
        # Use extruct to extract all structured data formats
        data = extruct.extract(
            html,
            base_url=url,
            syntaxes=['json-ld', 'microdata', 'rdfa', 'opengraph']
        )
        
        return data
    
    def analyze_jsonld(self, jsonld_data: List) -> Dict:
        """Analyze JSON-LD schema markup"""
        
        analysis = {
            'total_schemas': 0,
            'schema_types': {},
            'completeness_scores': {},
            'errors': [],
            'warnings': [],
            'recommendations': []
        }
        
        if not jsonld_data:
            analysis['warnings'].append("No JSON-LD schema markup found")
            return analysis
        
        # Flatten @graph structures and analyze each schema object
        all_schemas = []
        for idx, schema in enumerate(jsonld_data):
            # Check if this is a @graph structure
            if '@graph' in schema:
                # Extract all entities from the graph
                graph_entities = schema['@graph']
                all_schemas.extend(graph_entities)
            else:
                # Regular schema object
                all_schemas.append(schema)
        
        analysis['total_schemas'] = len(all_schemas)
        
        # Analyze each entity
        for idx, schema in enumerate(all_schemas):
            schema_type = schema.get('@type')
            
            if not schema_type:
                continue  # Skip entities without @type (may be nested objects)
            
            # Handle array of types
            if isinstance(schema_type, list):
                schema_type = schema_type[0]
            
            # Count schema types
            analysis['schema_types'][schema_type] = analysis['schema_types'].get(schema_type, 0) + 1
            
            # Check completeness
            if schema_type in self.schema_recommendations:
                completeness = self._check_schema_completeness(schema, schema_type)
                
                # Store completeness score
                key = f"{schema_type}_{analysis['schema_types'][schema_type]}"
                analysis['completeness_scores'][key] = completeness
                
                # Add recommendations for missing fields
                if completeness['missing_fields']:
                    analysis['recommendations'].append({
                        'schema_type': schema_type,
                        'missing_fields': completeness['missing_fields'],
                        'priority': 'high' if len(completeness['missing_fields']) > 2 else 'medium'
                    })
        
        return analysis
    
    def _check_schema_completeness(self, schema: Dict, schema_type: str) -> Dict:
        """Check completeness of a schema object"""
        
        recommended_fields = self.schema_recommendations.get(schema_type, [])
        
        present_fields = []
        missing_fields = []
        
        for field in recommended_fields:
            if field in schema and schema[field]:
                present_fields.append(field)
            else:
                missing_fields.append(field)
        
        total_recommended = len(recommended_fields)
        present_count = len(present_fields)
        
        completeness_percent = (present_count / total_recommended * 100) if total_recommended > 0 else 0
        
        return {
            'schema_type': schema_type,
            'completeness_percent': round(completeness_percent, 1),
            'present_fields': present_fields,
            'missing_fields': missing_fields,
            'rating': self._rate_completeness(completeness_percent)
        }
    
    def _rate_completeness(self, percent: float) -> str:
        """Rate schema completeness"""
        if percent >= 90:
            return "Excellent"
        elif percent >= 75:
            return "Good"
        elif percent >= 50:
            return "Fair"
        else:
            return "Poor"
    
    def calculate_overall_score(self, analysis: Dict) -> Dict:
        """Calculate overall schema markup score"""
        
        if not analysis['completeness_scores']:
            return {
                'overall_score': 0,
                'rating': 'No Schema',
                'has_schema': False
            }
        
        # Average completeness scores
        completeness_values = [
            score['completeness_percent'] 
            for score in analysis['completeness_scores'].values()
        ]
        
        avg_completeness = sum(completeness_values) / len(completeness_values)
        
        # Bonus points for having multiple schema types
        type_diversity_bonus = min(len(analysis['schema_types']) * 5, 20)
        
        # Penalty for errors
        error_penalty = len(analysis['errors']) * 10
        
        overall_score = max(0, min(100, avg_completeness + type_diversity_bonus - error_penalty))
        
        return {
            'overall_score': round(overall_score, 1),
            'rating': self._rate_completeness(overall_score),
            'has_schema': True,
            'schema_type_count': len(analysis['schema_types']),
            'avg_completeness': round(avg_completeness, 1)
        }
    
    def save_results(self, results: Dict, output_dir: str = '.tmp') -> str:
        """Save audit results to JSON file"""
        
        os.makedirs(output_dir, exist_ok=True)
        
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        filename = f"schema_audit_{timestamp}.json"
        filepath = os.path.join(output_dir, filename)
        
        with open(filepath, 'w', encoding='utf-8') as f:
            json.dump(results, f, indent=2, ensure_ascii=False)
        
        return filepath
    
    def print_summary(self, analysis: Dict, overall_score: Dict):
        """Print human-readable audit summary"""
        
        print("\n" + "="*60)
        print("SCHEMA MARKUP AUDIT RESULTS")
        print("="*60)
        
        # Overall score
        print(f"\n[SCORE] OVERALL SCHEMA SCORE: {overall_score['overall_score']}/100")
        print(f"   Rating: {overall_score['rating']}")
        
        if not overall_score['has_schema']:
            print("\n[ERROR] No schema markup detected on this page!")
            print("\n[TIP] RECOMMENDATION: Implement basic schema markup")
            print("   - Start with Organization schema (if homepage)")
            print("   - Add Article schema (if blog/content)")
            print("   - Include BreadcrumbList schema")
            return
        
        print(f"   Schema Types Found: {overall_score['schema_type_count']}")
        print(f"   Average Completeness: {overall_score['avg_completeness']}%")
        
        # Schema types detected
        print(f"\n[TYPES] SCHEMA TYPES DETECTED:")
        print("-" * 60)
        for schema_type, count in analysis['schema_types'].items():
            print(f"   * {schema_type} ({count})")
        
        # Completeness scores
        print(f"\n[ANALYSIS] COMPLETENESS ANALYSIS:")
        print("-" * 60)
        
        for key, score in analysis['completeness_scores'].items():
            print(f"\n   {score['schema_type']}:")
            print(f"   Completeness: {score['completeness_percent']}% ({score['rating']})")
            print(f"   Present fields: {', '.join(score['present_fields']) if score['present_fields'] else 'None'}")
            
            if score['missing_fields']:
                print(f"   [WARN] Missing fields: {', '.join(score['missing_fields'])}")
        
        # Errors
        if analysis['errors']:
            print(f"\n[ERROR] ERRORS ({len(analysis['errors'])}):")
            print("-" * 60)
            for error in analysis['errors']:
                print(f"   * {error}")
        
        # Warnings
        if analysis['warnings']:
            print(f"\n[WARN] WARNINGS ({len(analysis['warnings'])}):")
            print("-" * 60)
            for warning in analysis['warnings']:
                print(f"   * {warning}")
        
        # Recommendations
        if analysis['recommendations']:
            print(f"\n[TIP] RECOMMENDATIONS ({len(analysis['recommendations'])}):")
            print("-" * 60)
            
            # Sort by priority
            high_priority = [r for r in analysis['recommendations'] if r['priority'] == 'high']
            medium_priority = [r for r in analysis['recommendations'] if r['priority'] == 'medium']
            
            if high_priority:
                print("\n   HIGH PRIORITY:")
                for rec in high_priority:
                    print(f"   * {rec['schema_type']}: Add {', '.join(rec['missing_fields'])}")
            
            if medium_priority:
                print("\n   MEDIUM PRIORITY:")
                for rec in medium_priority:
                    print(f"   * {rec['schema_type']}: Add {', '.join(rec['missing_fields'])}")
        
        print("\n" + "="*60 + "\n")


def main():
    """Main execution function"""
    
    parser = argparse.ArgumentParser(
        description='Audit schema markup on a webpage'
    )
    
    parser.add_argument('--url', required=True, help='URL to audit')
    parser.add_argument('--validate', action='store_true', help='Validate schema markup')
    parser.add_argument('--output-dir', default='.tmp', help='Output directory (default: .tmp)')
    
    args = parser.parse_args()
    
    try:
        # Initialize auditor
        auditor = SchemaAuditor()
        
        # Fetch page
        print(f"Fetching page: {args.url}")
        html = auditor.fetch_page(args.url)
        print(f"[OK] Fetched {len(html)} bytes")
        
        # Extract schema
        print("Extracting structured data...")
        structured_data = auditor.extract_schema(html, args.url)
        
        # Focus on JSON-LD (Google's preferred format)
        jsonld_data = structured_data.get('json-ld', [])
        print(f"[OK] Found {len(jsonld_data)} JSON-LD schema objects")
        
        # Analyze
        print("Analyzing schema markup...")
        analysis = auditor.analyze_jsonld(jsonld_data)
        
        # Calculate overall score
        overall_score = auditor.calculate_overall_score(analysis)
        
        # Prepare results
        results = {
            'timestamp': datetime.now().isoformat(),
            'url': args.url,
            'analysis': analysis,
            'overall_score': overall_score,
            'raw_structured_data': {
                'json-ld': jsonld_data,
                'microdata_count': len(structured_data.get('microdata', [])),
                'rdfa_count': len(structured_data.get('rdfa', [])),
                'opengraph': structured_data.get('opengraph', {})
            }
        }
        
        # Save results
        output_path = auditor.save_results(results, output_dir=args.output_dir)
        print(f"[OK] Results saved to: {output_path}")
        
        # Print summary
        auditor.print_summary(analysis, overall_score)
        
        # Validation note
        if args.validate:
            print("[INFO] VALIDATION NOTES:")
            print("   For detailed validation, test your schema with:")
            print("   * Google Rich Results Test: https://search.google.com/test/rich-results")
            print("   * Schema.org Validator: https://validator.schema.org/")
            print()
        
        return 0
    
    except Exception as e:
        print(f"\n[ERROR] Error: {str(e)}", file=sys.stderr)
        return 1


if __name__ == '__main__':
    sys.exit(main())
