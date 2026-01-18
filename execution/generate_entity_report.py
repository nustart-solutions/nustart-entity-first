"""
Comprehensive Entity Report Generator

Orchestrates all entity measurement tools and generates a unified report.

Usage:
    python generate_entity_report.py --url https://example.com --target "Company Name"
    python generate_entity_report.py --url https://example.com --compare https://competitor.com
    python generate_entity_report.py --url-file urls.txt --export-sheets

Requirements:
    - All other execution scripts in this directory
    - Google Sheets API (optional, for export)
"""

import os
import sys
import json
import argparse
from datetime import datetime
from typing import Dict, List, Optional
import subprocess

class EntityReportGenerator:
    """Generate comprehensive entity optimization reports"""
    
    def __init__(self, output_dir: str = '.tmp'):
        """Initialize report generator"""
        self.output_dir = output_dir
        self.execution_dir = os.path.dirname(os.path.abspath(__file__))
        
        # Ensure output directory exists
        os.makedirs(output_dir, exist_ok=True)
    
    def run_salience_analysis(self, url: str, target_entity: Optional[str] = None) -> Dict:
        """Run entity salience measurement"""
        
        print("  → Running entity salience analysis...")
        
        cmd = [
            sys.executable,
            os.path.join(self.execution_dir, 'measure_entity_salience.py'),
            '--url', url,
            '--output-dir', self.output_dir
        ]
        
        if target_entity:
            cmd.extend(['--target-entity', target_entity])
        
        try:
            result = subprocess.run(cmd, capture_output=True, text=True, check=True)
            
            # Find the output file (most recent)
            files = [f for f in os.listdir(self.output_dir) if f.startswith('entity_salience_')]
            if not files:
                return {'error': 'No output file generated'}
            
            latest_file = sorted(files)[-1]
            
            with open(os.path.join(self.output_dir, latest_file), 'r', encoding='utf-8') as f:
                return json.load(f)
        
        except subprocess.CalledProcessError as e:
            return {'error': f'Salience analysis failed: {e.stderr}'}
        except Exception as e:
            return {'error': str(e)}
    
    def run_schema_audit(self, url: str) -> Dict:
        """Run schema markup audit"""
        
        print("  → Running schema markup audit...")
        
        cmd = [
            sys.executable,
            os.path.join(self.execution_dir, 'audit_schema_markup.py'),
            '--url', url,
            '--output-dir', self.output_dir
        ]
        
        try:
            result = subprocess.run(cmd, capture_output=True, text=True, check=True)
            
            # Find the output file
            files = [f for f in os.listdir(self.output_dir) if f.startswith('schema_audit_')]
            if not files:
                return {'error': 'No output file generated'}
            
            latest_file = sorted(files)[-1]
            
            with open(os.path.join(self.output_dir, latest_file), 'r', encoding='utf-8') as f:
                return json.load(f)
        
        except subprocess.CalledProcessError as e:
            return {'error': f'Schema audit failed: {e.stderr}'}
        except Exception as e:
            return {'error': str(e)}
    
    def run_page_analysis(self, url: str, entities: Optional[List[str]] = None) -> Dict:
        """Run page entity placement analysis"""
        
        print("  → Running page structure analysis...")
        
        cmd = [
            sys.executable,
            os.path.join(self.execution_dir, 'analyze_page_entities.py'),
            '--url', url,
            '--audit-links',
            '--output-dir', self.output_dir
        ]
        
        if entities:
            cmd.extend(['--entities'] + entities)
        
        try:
            result = subprocess.run(cmd, capture_output=True, text=True, check=True)
            
            # Find the output file
            files = [f for f in os.listdir(self.output_dir) if f.startswith('page_entity_analysis_')]
            if not files:
                return {'error': 'No output file generated'}
            
            latest_file = sorted(files)[-1]
            
            with open(os.path.join(self.output_dir, latest_file), 'r', encoding='utf-8') as f:
                return json.load(f)
        
        except subprocess.CalledProcessError as e:
            return {'error': f'Page analysis failed: {e.stderr}'}
        except Exception as e:
            return {'error': str(e)}
    
    def calculate_composite_score(self, salience_data: Dict, schema_data: Dict, page_data: Dict) -> Dict:
        """Calculate composite entity optimization score"""
        
        scores = {
            'entity_salience_score': 0,
            'schema_completeness_score': 0,
            'content_coverage_score': 0,
            'link_quality_score': 0,
            'composite_score': 0,
            'rating': 'Unknown'
        }
        
        # Entity Salience Score (30%)
        if 'target_entity_analysis' in salience_data and salience_data['target_entity_analysis'].get('salience'):
            scores['entity_salience_score'] = salience_data['target_entity_analysis']['salience'] * 100
        elif salience_data.get('entities'):
            # Use highest salience if no target specified
            scores['entity_salience_score'] = salience_data['entities'][0]['salience'] * 100
        
        # Schema Completeness Score (30%)
        if 'overall_score' in schema_data and isinstance(schema_data['overall_score'], dict):
            scores['schema_completeness_score'] = schema_data['overall_score'].get('overall_score', 0)
        
        # Content Coverage Score (25%)
        # Based on entity placement in strategic locations
        if 'entity_placement' in page_data and page_data['entity_placement'].get('placement_scores'):
            placement_scores = page_data['entity_placement']['placement_scores']
            if placement_scores:
                avg_placement = sum(p['strategic_score'] for p in placement_scores.values()) / len(placement_scores)
                scores['content_coverage_score'] = avg_placement
        
        # Link Quality Score (15%)
        if 'link_analysis' in page_data:
            scores['link_quality_score'] = page_data['link_analysis'].get('entity_anchor_percentage', 0)
        
        # Composite Score (weighted average)
        scores['composite_score'] = round(
            scores['entity_salience_score'] * 0.30 +
            scores['schema_completeness_score'] * 0.30 +
            scores['content_coverage_score'] * 0.25 +
            scores['link_quality_score'] * 0.15,
            1
        )
        
        # Rating
        composite = scores['composite_score']
        if composite >= 90:
            scores['rating'] = "Excellent"
        elif composite >= 70:
            scores['rating'] = "Good"
        elif composite >= 50:
            scores['rating'] = "Fair"
        elif composite >= 30:
            scores['rating'] = "Poor"
        else:
            scores['rating'] = "Very Poor"
        
        return scores
    
    def generate_recommendations(self, scores: Dict, salience_data: Dict, schema_data: Dict, page_data: Dict) -> List[Dict]:
        """Generate prioritized recommendations"""
        
        recommendations = []
        
        # Low entity salience
        if scores['entity_salience_score'] < 50:
            recommendations.append({
                'priority': 'high',
                'category': 'Entity Salience',
                'issue': 'Target entity has low prominence in content',
                'action': 'Rewrite title, H1, and first paragraph to prominently feature the target entity',
                'directive': 'directives/improve_entity_score.md - Strategy 4-5'
            })
        
        # Low schema score
        if scores['schema_completeness_score'] < 70:
            recommendations.append({
                'priority': 'high',
                'category': 'Schema Markup',
                'issue': 'Missing or incomplete schema markup',
                'action': 'Implement Organization, Article, and BreadcrumbList schema',
                'directive': 'directives/improve_entity_score.md - Strategy 1-3'
            })
        
        # Low content coverage
        if scores['content_coverage_score'] < 50:
            recommendations.append({
                'priority': 'medium',
                'category': 'Content Structure',
                'issue': 'Entity not present in strategic locations',
                'action': 'Add entity mentions to H2/H3 headings and early paragraphs',
                'directive': 'directives/improve_entity_score.md - Strategy 4'
            })
        
        # Poor link quality
        if scores['link_quality_score'] < 60:
            recommendations.append({
                'priority': 'medium',
                'category': 'Internal Links',
                'issue': 'Too many generic anchor texts (click here, read more, etc.)',
                'action': 'Replace generic anchors with entity-focused descriptive text',
                'directive': 'directives/improve_entity_score.md - Strategy 7'
            })
        
        # No target entity found
        if salience_data.get('target_entity_analysis', {}).get('found') == False:
            recommendations.append({
                'priority': 'critical',
                'category': 'Entity Presence',
                'issue': 'Target entity not detected in content at all',
                'action': 'Add explicit entity definition and context throughout content',
                'directive': 'directives/improve_entity_score.md - Strategy 5'
            })
        
        return recommendations
    
    def generate_html_report(self, report_data: Dict) -> str:
        """Generate HTML report"""
        
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        filename = f"entity_report_{timestamp}.html"
        filepath = os.path.join(self.output_dir, filename)
        
        scores = report_data['composite_score']
        recommendations = report_data['recommendations']
        
        # Simple HTML template
        html = f"""<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Entity Optimization Report</title>
    <style>
        body {{ font-family: Arial, sans-serif; max-width: 1200px; margin: 40px auto; padding: 20px; }}
        h1 {{ color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }}
        h2 {{ color: #555; margin-top: 30px; }}
        .score-card {{ background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0; }}
        .score-large {{ font-size: 48px; font-weight: bold; color: #4CAF50; }}
        .rating {{ font-size: 24px; color: #777; }}
        .metric {{ display: inline-block; width: 45%; margin: 10px 2%; }}
        .metric-label {{ font-weight: bold; color: #555; }}
        .metric-value {{ font-size: 24px; color: #4CAF50; }}
        .recommendation {{ background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 10px 0; }}
        .priority-critical {{ border-left-color: #dc3545; }}
        .priority-high {{ border-left-color: #ff6b6b; }}
        .priority-medium {{ border-left-color: #ffc107; }}
        table {{ width: 100%; border-collapse: collapse; margin: 20px 0; }}
        th, td {{ padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }}
        th {{ background: #4CAF50; color: white; }}
        .footer {{ margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #777; font-size: 14px; }}
    </style>
</head>
<body>
    <h1>Entity-First SEO Report</h1>
    
    <div class="score-card">
        <div class="score-large">{scores['composite_score']}/100</div>
        <div class="rating">{scores['rating']}</div>
        <p><strong>URL:</strong> {report_data['url']}</p>
        <p><strong>Generated:</strong> {report_data['timestamp']}</p>
    </div>
    
    <h2>Score Breakdown</h2>
    <div class="score-card">
        <div class="metric">
            <div class="metric-label">Entity Salience (30%)</div>
            <div class="metric-value">{scores['entity_salience_score']:.1f}</div>
        </div>
        <div class="metric">
            <div class="metric-label">Schema Completeness (30%)</div>
            <div class="metric-value">{scores['schema_completeness_score']:.1f}</div>
        </div>
        <div class="metric">
            <div class="metric-label">Content Coverage (25%)</div>
            <div class="metric-value">{scores['content_coverage_score']:.1f}</div>
        </div>
        <div class="metric">
            <div class="metric-label">Link Quality (15%)</div>
            <div class="metric-value">{scores['link_quality_score']:.1f}</div>
        </div>
    </div>
    
    <h2>Recommendations</h2>
"""
        
        for rec in recommendations:
            priority_class = f"priority-{rec['priority']}"
            html += f"""
    <div class="recommendation {priority_class}">
        <strong>Priority: {rec['priority'].upper()}</strong> - {rec['category']}<br>
        <strong>Issue:</strong> {rec['issue']}<br>
        <strong>Action:</strong> {rec['action']}<br>
        <small><em>See: {rec['directive']}</em></small>
    </div>
"""
        
        html += """
    <div class="footer">
        <p>Generated by Entity-First SEO Toolkit</p>
        <p>For detailed implementation guidance, see directives in your project directory</p>
    </div>
</body>
</html>
"""
        
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(html)
        
        return filepath
    
    def save_json_report(self, report_data: Dict) -> str:
        """Save comprehensive JSON report"""
        
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        filename = f"entity_report_{timestamp}.json"
        filepath = os.path.join(self.output_dir, filename)
        
        with open(filepath, 'w', encoding='utf-8') as f:
            json.dump(report_data, f, indent=2, ensure_ascii=False)
        
        return filepath


def main():
    """Main execution function"""
    
    parser = argparse.ArgumentParser(
        description='Generate comprehensive entity optimization report'
    )
    
    # Input options
    input_group = parser.add_mutually_exclusive_group(required=True)
    input_group.add_argument('--url', help='URL to analyze')
    input_group.add_argument('--url-file', help='File containing URLs to analyze (one per line)')
    
    # Optional parameters
    parser.add_argument('--target', help='Target entity name to focus on')
    parser.add_argument('--compare', nargs='+', help='Competitor URLs to compare against')
    parser.add_argument('--output-dir', default='.tmp', help='Output directory (default: .tmp)')
    parser.add_argument('--output-format', default='html', choices=['html', 'json', 'both'], help='Report format')
    parser.add_argument('--export-sheets', action='store_true', help='Export to Google Sheets (requires credentials)')
    
    args = parser.parse_args()
    
    try:
        # Initialize generator
        generator = EntityReportGenerator(output_dir=args.output_dir)
        
        # Get URLs to analyze
        urls = []
        if args.url:
            urls = [args.url]
        elif args.url_file:
            with open(args.url_file, 'r') as f:
                urls = [line.strip() for line in f if line.strip()]
        
        if not urls:
            print("❌ No URLs to analyze", file=sys.stderr)
            return 1
        
        # Process each URL
        for url in urls:
            print(f"\n{'='*60}")
            print(f"Analyzing: {url}")
            print('='*60)
            
            # Run analyses
            salience_data = generator.run_salience_analysis(url, target_entity=args.target)
            schema_data = generator.run_schema_audit(url)
            
            entities_to_track = [args.target] if args.target else []
            page_data = generator.run_page_analysis(url, entities=entities_to_track)
            
            # Calculate composite score
            print("\n  → Calculating composite entity score...")
            scores = generator.calculate_composite_score(salience_data, schema_data, page_data)
            
            # Generate recommendations
            print("  → Generating recommendations...")
            recommendations = generator.generate_recommendations(scores, salience_data, schema_data, page_data)
            
            # Compile report
            report_data = {
                'timestamp': datetime.now().isoformat(),
                'url': url,
                'target_entity': args.target,
                'composite_score': scores,
                'salience_analysis': salience_data,
                'schema_audit': schema_data,
                'page_analysis': page_data,
                'recommendations': recommendations
            }
            
            # Save reports
            if args.output_format in ['html', 'both']:
                html_path = generator.generate_html_report(report_data)
                print(f"\n✓ HTML report saved: {html_path}")
            
            if args.output_format in ['json', 'both']:
                json_path = generator.save_json_report(report_data)
                print(f"✓ JSON report saved: {json_path}")
            
            # Print summary
            print(f"\n🎯 COMPOSITE ENTITY SCORE: {scores['composite_score']}/100 ({scores['rating']})")
            print(f"\n📋 TOP RECOMMENDATIONS:")
            for rec in recommendations[:3]:
                print(f"   • [{rec['priority'].upper()}] {rec['action']}")
        
        print("\n" + "="*60)
        print("✓ Analysis complete!")
        print("="*60 + "\n")
        
        return 0
    
    except Exception as e:
        print(f"\n❌ Error: {str(e)}", file=sys.stderr)
        import traceback
        traceback.print_exc()
        return 1


if __name__ == '__main__':
    sys.exit(main())
