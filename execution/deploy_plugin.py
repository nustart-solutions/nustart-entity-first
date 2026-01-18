"""
Deploy WordPress Plugin via SFTP

Uploads the nustart-entity-seo plugin to WordPress using SFTP credentials from .env

Usage:
    python deploy_plugin.py
"""

import os
import sys
from pathlib import Path
import paramiko
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

def deploy_plugin():
    """Upload plugin to WordPress via SFTP"""
    
    # Get SFTP credentials from .env
    sftp_host = os.getenv('SFTP_HOST')
    sftp_port = int(os.getenv('SFTP_PORT', 22))
    sftp_user = os.getenv('SFTP_USER')
    sftp_password = os.getenv('SFTP_PASSWORD')
    wp_path = os.getenv('SFTP_WP_PATH', '/srv/htdocs/')
    remote_path = wp_path.rstrip('/') + '/wp-content/plugins/nustart-entity-seo'
    
    if not all([sftp_host, sftp_user, sftp_password]):
        print("[ERROR] Missing SFTP credentials in .env file")
        print("Required: SFTP_HOST, SFTP_USER, SFTP_PASSWORD")
        return False
    
    # Local plugin path
    local_plugin_path = Path(__file__).parent.parent / 'wordpress-plugin'
    
    if not local_plugin_path.exists():
        print(f"[ERROR] Plugin directory not found: {local_plugin_path}")
        return False
    
    print(f"[INFO] Connecting to {sftp_host}:{sftp_port}...")
    
    try:
        # Create SSH client
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        
        # Connect
        ssh.connect(
            hostname=sftp_host,
            port=sftp_port,
            username=sftp_user,
            password=sftp_password
        )
        
        print(f"[OK] Connected to {sftp_host}")
        
        # Open SFTP session
        sftp = ssh.open_sftp()
        
        # Create remote plugin directory (create nested dirs)
        print(f"[INFO] Creating remote directory: {remote_path}")
        dirs = remote_path.split('/')
        current = ''
        for d in dirs:
            if not d:
                continue
            current += '/' + d
            try:
                sftp.stat(current)
            except:
                try:
                    sftp.mkdir(current)
                    print(f"  Created: {current}")
                except Exception as e:
                    pass  # May already exist
        
        
        # Change to remote plugin directory
        sftp.chdir(remote_path)
        print(f"[INFO] Changed to remote directory: {remote_path}")
        
        # Upload all files recursively
        print("[INFO] Uploading plugin files...")
        uploaded_count = 0
        
        for local_file in local_plugin_path.rglob('*'):
            if local_file.is_file():
                # Calculate relative path
                relative_path = local_file.relative_to(local_plugin_path)
                remote_file_path = str(relative_path).replace('\\', '/')
                
                # Create remote directory structure (relative to current dir)
                remote_dir = os.path.dirname(remote_file_path)
                if remote_dir:
                    dirs = remote_dir.split('/')
                    current_path = ''
                    for dir_name in dirs:
                        current_path = current_path + '/' + dir_name if current_path else dir_name
                        try:
                            sftp.mkdir(current_path)
                            print(f"  Created dir: {current_path}")
                        except:
                            pass
                
                # Upload file
                print(f"  Uploading: {remote_file_path}")
                sftp.put(str(local_file), remote_file_path)
                uploaded_count += 1
        
        print(f"\n[OK] Successfully uploaded {uploaded_count} files")
        print(f"\n[NEXT STEP] Go to WordPress admin and activate the plugin:")
        print(f"  1. Login to WordPress admin")
        print(f"  2. Go to Plugins > Installed Plugins")
        print(f"  3. Find 'NuStart Entity-First SEO'")
        print(f"  4. Click 'Activate'")
        print(f"\n[INFO] On activation, the plugin will:")
        print(f"  - Create ns_entities table")
        print(f"  - Create ns_page_entity_map table")
        print(f"  - Seed NuStart Solutions organization entity")
        print(f"  - Seed Anne Allen person entity")
        print(f"  - Start outputting schema on homepage")
        
        # Close connections
        sftp.close()
        ssh.close()
        
        return True
            
    except Exception as e:
        print(f"\n[ERROR] Deployment failed: {str(e)}")
        import traceback
        traceback.print_exc()
        return False

if __name__ == '__main__':
    success = deploy_plugin()
    sys.exit(0 if success else 1)
