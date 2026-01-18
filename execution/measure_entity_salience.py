"""
Entity Salience Measurement Tool

Uses Google's Natural Language API to extract entities and calculate salience scores.

Usage:
    python measure_entity_salience.py --url https://example.com
    python measure_entity_salience.py --text "Your content here" --target-entity "Company Name"
    python measure_entity_salience.py --file content.txt

Requirements:
    - Google Cloud account with Natural Language API enabled
    - API key in .env file: GOOGLE_NLP_API_KEY=your_key_here
"""

import os
import sys
import json
import argparse
from datetime import datetime
from typing import Dict, List, Optional
from dotenv import load_dotenv
import requests
from bs4 import BeautifulSoup

# Load environment variables
load_dotenv()

class EntitySalienceAnalyzer:
    """Analyze entity salience using Google Natural Language API"""
    
    def __init__(self, api_key: Optional[str] = None):
        """Initialize analyzer with API key"""
        self.api_key = api_key or os.getenv('GOOGLE_NLP_API_KEY')
        if not self.api_key:
            raise ValueError("Google NLP API key not found. Set GOOGLE_NLP_API_KEY in .env file")
        
        self.api_url = "https://language.googleapis.com/v1/documents:analyzeEntities"
    
    def extract_text_from_url(self, url: str) -> str:
        """Extract main text content from a URL"""
        try:
            response = requests.get(url, timeout=10, headers={
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            })
            response.raise_for_status()
            
            soup = BeautifulSoup(response.text, 'html.parser')
            
            # Remove script and style elements
            for script in soup(["script", "style", "nav", "footer", "header"]):
                script.decompose()
            
            # Get text from main content areas
            main_content = soup.find('main') or soup.find('article') or soup.find('body')
            
            if main_content:
                text = main_content.get_text(separator=' ', strip=True)
            else:
                text = soup.get_text(separator=' ', strip=True)
            
            # Clean up whitespace
            text = ' '.join(text.split())
            
            return text
        
        except Exception as e:
            raise Exception(f"Failed to extract text from URL: {str(e)}")
    
    def analyze_entities(self, text: str) -> Dict:
        """Call Google NLP API to analyze entities"""
        
        # Prepare request
        request_data = {
            "document": {
                "type": "PLAIN_TEXT",
                "content": text[:10000]  # Limit to 10k characters for API efficiency
            },
            "encodingType": "UTF8"
        }
        
        # Make API request
        response = requests.post(
            f"{self.api_url}?key={self.api_key}",
            json=request_data,
            headers={'Content-Type': 'application/json'}
        )
        
        if response.status_code != 200:
            raise Exception(f"API request failed: {response.status_code} - {response.text}")
        
        return response.json()
    
    def format_entity_results(self, api_response: Dict, target_entity: Optional[str] = None) -> Dict:
        """Format entity analysis results"""
        
        entities = api_response.get('entities', [])
        
        # Sort by salience (highest first)
        entities_sorted = sorted(entities, key=lambda x: x.get('salience', 0), reverse=True)
        
        results = {
            'timestamp': datetime.now().isoformat(),
            'total_entities': len(entities),
            'target_entity_analysis': None,
            'entities': []
        }
        
        # Process each entity
        for entity in entities_sorted:
            entity_data = {
                'name': entity.get('name'),
                'type': entity.get('type'),
                'salience': round(entity.get('salience', 0), 4),
                'mentions_count': len(entity.get('mentions', [])),
                'metadata': entity.get('metadata', {}),
                'wikipedia_url': entity.get('metadata', {}).get('wikipedia_url')
            }
            results['entities'].append(entity_data)
            
            # Check if this is the target entity
            if target_entity and entity.get('name', '').lower() == target_entity.lower():
                results['target_entity_analysis'] = {
                    'name': entity.get('name'),
                    'salience': round(entity.get('salience', 0), 4),
                    'type': entity.get('type'),
                    'mentions_count': len(entity.get('mentions', [])),
                    'rating': self._rate_salience(entity.get('salience', 0))
                }
        
        # If target entity specified but not found
        if target_entity and not results['target_entity_analysis']:
            results['target_entity_analysis'] = {
                'name': target_entity,
                'found': False,
                'message': 'Target entity not detected in content'
            }
        
        return results
    
    def _rate_salience(self, salience: float) -> str:
        """Rate salience score"""
        if salience >= 0.8:
            return "Excellent - Dominant entity"
        elif salience >= 0.5:
            return "Good - Strong presence"
        elif salience >= 0.3:
            return "Moderate - Some presence"
        else:
            return "Weak - Low prominence"
    
    def save_results(self, results: Dict, output_dir: str = '.tmp') -> str:
        """Save results to JSON file"""
        
        # Create output directory if it doesn't exist
        os.makedirs(output_dir, exist_ok=True)
        
        # Generate filename
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        filename = f"entity_salience_{timestamp}.json"
        filepath = os.path.join(output_dir, filename)
        
        # Save JSON
        with open(filepath, 'w', encoding='utf-8') as f:
            json.dump(results, f, indent=2, ensure_ascii=False)
        
        return filepath
    
    def print_summary(self, results: Dict):
        """Print human-readable summary"""
        
        print("\n" + "="*60)
        print("ENTITY SALIENCE ANALYSIS RESULTS")
        print("="*60)
        
        # Target entity analysis
        if results.get('target_entity_analysis'):
            target = results['target_entity_analysis']
            print(f"\n🎯 TARGET ENTITY ANALYSIS:")
            
            if target.get('found') == False:
                print(f"   Entity: {target['name']}")
                print(f"   Status: ❌ NOT FOUND")
                print(f"   Message: {target['message']}")
            else:
                print(f"   Entity: {target['name']}")
                print(f"   Type: {target['type']}")
                print(f"   Salience Score: {target['salience']}")
                print(f"   Rating: {target['rating']}")
                print(f"   Mentions: {target['mentions_count']}")
        
        # Top entities
        print(f"\n📊 TOP ENTITIES DETECTED ({results['total_entities']} total):")
        print("-" * 60)
        
        top_entities = results['entities'][:10]  # Show top 10
        
        for i, entity in enumerate(top_entities, 1):
            print(f"\n{i}. {entity['name']}")
            print(f"   Type: {entity['type']}")
            print(f"   Salience: {entity['salience']}")
            print(f"   Mentions: {entity['mentions_count']}")
            if entity.get('wikipedia_url'):
                print(f"   Wikipedia: {entity['wikipedia_url']}")
        
        # Entity type distribution
        print(f"\n📋 ENTITY TYPE DISTRIBUTION:")
        print("-" * 60)
        
        type_counts = {}
        for entity in results['entities']:
            entity_type = entity['type']
            type_counts[entity_type] = type_counts.get(entity_type, 0) + 1
        
        for entity_type, count in sorted(type_counts.items(), key=lambda x: x[1], reverse=True):
            print(f"   {entity_type}: {count}")
        
        print("\n" + "="*60 + "\n")


def main():
    """Main execution function"""
    
    parser = argparse.ArgumentParser(
        description='Measure entity salience using Google Natural Language API'
    )
    
    # Input options
    input_group = parser.add_mutually_exclusive_group(required=True)
    input_group.add_argument('--url', help='URL to analyze')
    input_group.add_argument('--text', help='Direct text content to analyze')
    input_group.add_argument('--file', help='Path to text file to analyze')
    
    # Optional parameters
    parser.add_argument('--target-entity', help='Specific entity to focus on')
    parser.add_argument('--output-dir', default='.tmp', help='Output directory (default: .tmp)')
    parser.add_argument('--api-key', help='Google NLP API key (or set in .env)')
    
    args = parser.parse_args()
    
    try:
        # Initialize analyzer
        analyzer = EntitySalienceAnalyzer(api_key=args.api_key)
        
        # Get text content
        if args.url:
            print(f"Extracting content from URL: {args.url}")
            text = analyzer.extract_text_from_url(args.url)
            print(f"✓ Extracted {len(text)} characters")
        elif args.text:
            text = args.text
        elif args.file:
            print(f"Reading content from file: {args.file}")
            with open(args.file, 'r', encoding='utf-8') as f:
                text = f.read()
            print(f"✓ Read {len(text)} characters")
        
        # Validate text length
        if len(text) < 20:
            print("⚠️  Warning: Content is very short. Entity detection may be limited.")
        
        # Analyze entities
        print("Analyzing entities with Google Natural Language API...")
        api_response = analyzer.analyze_entities(text)
        
        # Format results
        results = analyzer.format_entity_results(api_response, target_entity=args.target_entity)
        
        # Save results
        output_path = analyzer.save_results(results, output_dir=args.output_dir)
        print(f"✓ Results saved to: {output_path}")
        
        # Print summary
        analyzer.print_summary(results)
        
        return 0
    
    except Exception as e:
        print(f"\n❌ Error: {str(e)}", file=sys.stderr)
        return 1


if __name__ == '__main__':
    sys.exit(main())
