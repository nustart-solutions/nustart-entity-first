"""
Page Entity Analysis Tool

Analyzes where and how entities appear in a webpage's structure.

Usage:
    python analyze_page_entities.py --url https://example.com
    python analyze_page_entities.py --url https://example.com --audit-links

Requirements:
    - requests, beautifulsoup4
"""

import os
import sys
import json
import argparse
from datetime import datetime
from typing import Dict, List, Optional
from collections import Counter
import requests
from bs4 import BeautifulSoup
import re

class PageEntityAnalyzer:
    """Analyze entity placement and context on a webpage"""
    
    def __init__(self):
        """Initialize analyzer"""
        self.strategic_locations = ['title', 'h1', 'first_paragraph', 'h2', 'h3']
    
    def fetch_page(self, url: str) -> tuple:
        """Fetch HTML and return soup object and text"""
        try:
            response = requests.get(url, timeout=10, headers={
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            })
            response.raise_for_status()
            
            soup = BeautifulSoup(response.text, 'html.parser')
            return soup, response.text
        
        except Exception as e:
            raise Exception(f"Failed to fetch page: {str(e)}")
    
    def extract_strategic_content(self, soup: BeautifulSoup) -> Dict:
        """Extract content from strategic locations"""
        
        content = {
            'title': '',
            'meta_description': '',
            'h1': [],
            'h2': [],
            'h3': [],
            'first_paragraph': '',
            'all_paragraphs': [],
            'internal_links': [],
            'external_links': []
        }
        
        # Title
        title_tag = soup.find('title')
        if title_tag:
            content['title'] = title_tag.get_text(strip=True)
        
        # Meta description
        meta_desc = soup.find('meta', attrs={'name': 'description'})
        if meta_desc:
            content['meta_description'] = meta_desc.get('content', '')
        
        # Headings
        for h1 in soup.find_all('h1'):
            content['h1'].append(h1.get_text(strip=True))
        
        for h2 in soup.find_all('h2'):
            content['h2'].append(h2.get_text(strip=True))
        
        for h3 in soup.find_all('h3'):
            content['h3'].append(h3.get_text(strip=True))
        
        # Paragraphs (from main content area)
        main_content = soup.find('main') or soup.find('article') or soup.find('body')
        
        if main_content:
            paragraphs = main_content.find_all('p')
            
            for i, p in enumerate(paragraphs):
                text = p.get_text(strip=True)
                if len(text) > 20:  # Ignore very short paragraphs
                    content['all_paragraphs'].append(text)
                    
                    if i == 0 and not content['first_paragraph']:
                        content['first_paragraph'] = text
        
        # Links
        for a in soup.find_all('a', href=True):
            href = a.get('href', '')
            anchor_text = a.get_text(strip=True)
            
            if not anchor_text or len(anchor_text) < 2:
                continue
            
            link_data = {
                'href': href,
                'anchor_text': anchor_text
            }
            
            # Classify as internal or external
            if href.startswith('http'):
                if 'nustart.solutions' in href or 'localhost' in href:  # Adjust domain as needed
                    content['internal_links'].append(link_data)
                else:
                    content['external_links'].append(link_data)
            elif href.startswith('/') or not href.startswith('#'):
                content['internal_links'].append(link_data)
        
        return content
    
    def analyze_entity_placement(self, content: Dict, entities: List[str]) -> Dict:
        """Analyze where specific entities appear"""
        
        placement_analysis = {
            'entities_analyzed': entities,
            'placement_scores': {},
            'location_presence': {}
        }
        
        for entity in entities:
            entity_lower = entity.lower()
            
            placement = {
                'entity': entity,
                'found_in': [],
                'mentions_by_location': {},
                'strategic_score': 0
            }
            
            # Check each strategic location
            locations_to_check = {
                'title': content['title'],
                'meta_description': content['meta_description'],
                'h1': ' '.join(content['h1']),
                'h2': ' '.join(content['h2']),
                'h3': ' '.join(content['h3']),
                'first_paragraph': content['first_paragraph'],
                'body': ' '.join(content['all_paragraphs'][:5])  # First 5 paragraphs
            }
            
            for location, text in locations_to_check.items():
                if entity_lower in text.lower():
                    placement['found_in'].append(location)
                    # Count mentions
                    count = text.lower().count(entity_lower)
                    placement['mentions_by_location'][location] = count
            
            # Calculate strategic score (weighted by importance of location)
            location_weights = {
                'title': 25,
                'h1': 20,
                'first_paragraph': 20,
                'meta_description': 15,
                'h2': 10,
                'h3': 5,
                'body': 5
            }
            
            for location in placement['found_in']:
                placement['strategic_score'] += location_weights.get(location, 0)
            
            placement_analysis['placement_scores'][entity] = placement
        
        return placement_analysis
    
    def analyze_internal_links(self, content: Dict) -> Dict:
        """Analyze internal link anchor text for entity usage"""
        
        internal_links = content['internal_links']
        
        analysis = {
            'total_internal_links': len(internal_links),
            'anchor_text_analysis': {},
            'entity_anchors': [],
            'generic_anchors': []
        }
        
        # Generic anchor text patterns (not entity-focused)
        generic_patterns = [
            r'^click here$', r'^read more$', r'^learn more$', r'^here$',
            r'^more$', r'^link$', r'^this$', r'^download$'
        ]
        
        for link in internal_links:
            anchor = link['anchor_text'].lower()
            
            # Check if generic
            is_generic = any(re.match(pattern, anchor, re.IGNORECASE) for pattern in generic_patterns)
            
            if is_generic:
                analysis['generic_anchors'].append(link)
            else:
                # Likely entity or descriptive anchor
                analysis['entity_anchors'].append(link)
        
        # Calculate entity anchor percentage
        total = len(internal_links)
        entity_count = len(analysis['entity_anchors'])
        
        analysis['entity_anchor_percentage'] = round(
            (entity_count / total * 100) if total > 0 else 0,
            1
        )
        
        analysis['rating'] = self._rate_anchor_quality(analysis['entity_anchor_percentage'])
        
        return analysis
    
    def _rate_anchor_quality(self, percentage: float) -> str:
        """Rate internal link anchor text quality"""
        if percentage >= 80:
            return "Excellent - Entity-focused anchors"
        elif percentage >= 60:
            return "Good - Mostly descriptive"
        elif percentage >= 40:
            return "Fair - Mixed quality"
        else:
            return "Poor - Too many generic anchors"
    
    def save_results(self, results: Dict, output_dir: str = '.tmp') -> str:
        """Save analysis results"""
        
        os.makedirs(output_dir, exist_ok=True)
        
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        filename = f"page_entity_analysis_{timestamp}.json"
        filepath = os.path.join(output_dir, filename)
        
        with open(filepath, 'w', encoding='utf-8') as f:
            json.dump(results, f, indent=2, ensure_ascii=False)
        
        return filepath
    
    def print_summary(self, content: Dict, placement_analysis: Dict, link_analysis: Dict):
        """Print human-readable summary"""
        
        print("\n" + "="*60)
        print("PAGE ENTITY ANALYSIS RESULTS")
        print("="*60)
        
        # Page structure
        print(f"\n📄 PAGE STRUCTURE:")
        print("-" * 60)
        print(f"   Title: {content['title'][:80]}{'...' if len(content['title']) > 80 else ''}")
        print(f"   H1 tags: {len(content['h1'])}")
        print(f"   H2 tags: {len(content['h2'])}")
        print(f"   Paragraphs: {len(content['all_paragraphs'])}")
        print(f"   Internal Links: {len(content['internal_links'])}")
        
        # Entity placement
        if placement_analysis.get('placement_scores'):
            print(f"\n🎯 ENTITY PLACEMENT ANALYSIS:")
            print("-" * 60)
            
            for entity, placement in placement_analysis['placement_scores'].items():
                print(f"\n   Entity: {entity}")
                print(f"   Strategic Score: {placement['strategic_score']}/100")
                print(f"   Found in: {', '.join(placement['found_in']) if placement['found_in'] else 'None'}")
                
                if placement['mentions_by_location']:
                    mentions = ', '.join([f"{loc}({count})" for loc, count in placement['mentions_by_location'].items()])
                    print(f"   Mentions: {mentions}")
        
        # Internal links
        print(f"\n🔗 INTERNAL LINK ANALYSIS:")
        print("-" * 60)
        print(f"   Total Internal Links: {link_analysis['total_internal_links']}")
        print(f"   Entity-focused Anchors: {len(link_analysis['entity_anchors'])}")
        print(f"   Generic Anchors: {len(link_analysis['generic_anchors'])}")
        print(f"   Entity Anchor %: {link_analysis['entity_anchor_percentage']}%")
        print(f"   Rating: {link_analysis['rating']}")
        
        if link_analysis['generic_anchors']:
            print(f"\n   ⚠️  Generic anchors to improve:")
            for link in link_analysis['generic_anchors'][:5]:  # Show first 5
                print(f"      • '{link['anchor_text']}' → {link['href']}")
        
        print("\n" + "="*60 + "\n")


def main():
    """Main execution function"""
    
    parser = argparse.ArgumentParser(
        description='Analyze entity placement on a webpage'
    )
    
    parser.add_argument('--url', required=True, help='URL to analyze')
    parser.add_argument('--entities', nargs='+', help='Specific entities to track')
    parser.add_argument('--audit-links', action='store_true', help='Analyze internal link anchors')
    parser.add_argument('--output-dir', default='.tmp', help='Output directory (default: .tmp)')
    
    args = parser.parse_args()
    
    try:
        # Initialize analyzer
        analyzer = PageEntityAnalyzer()
        
        # Fetch page
        print(f"Fetching page: {args.url}")
        soup, html = analyzer.fetch_page(args.url)
        print(f"✓ Fetched page successfully")
        
        # Extract strategic content
        print("Extracting page structure...")
        content = analyzer.extract_strategic_content(soup)
        print(f"✓ Extracted content from {len(content['all_paragraphs'])} paragraphs")
        
        # Analyze entity placement
        entities_to_track = args.entities or []
        
        if entities_to_track:
            print(f"Analyzing placement for entities: {', '.join(entities_to_track)}")
            placement_analysis = analyzer.analyze_entity_placement(content, entities_to_track)
        else:
            placement_analysis = {'placement_scores': {}}
        
        # Analyze internal links
        print("Analyzing internal links...")
        link_analysis = analyzer.analyze_internal_links(content)
        
        # Prepare results
        results = {
            'timestamp': datetime.now().isoformat(),
            'url': args.url,
            'page_content': {
                'title': content['title'],
                'meta_description': content['meta_description'],
                'h1_count': len(content['h1']),
                'h2_count': len(content['h2']),
                'paragraph_count': len(content['all_paragraphs']),
                'internal_link_count': len(content['internal_links'])
            },
            'entity_placement': placement_analysis,
            'link_analysis': link_analysis
        }
        
        # Save results
        output_path = analyzer.save_results(results, output_dir=args.output_dir)
        print(f"✓ Results saved to: {output_path}")
        
        # Print summary
        analyzer.print_summary(content, placement_analysis, link_analysis)
        
        return 0
    
    except Exception as e:
        print(f"\n❌ Error: {str(e)}", file=sys.stderr)
        return 1


if __name__ == '__main__':
    sys.exit(main())
