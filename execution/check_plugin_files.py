"""
Check if plugin files exist on WordPress server via SFTP
"""

import os
import paramiko
from dotenv import load_dotenv

load_dotenv()

def check_plugin_files():
    """List files in the plugin directory on the server"""
    
    sftp_host = os.getenv('SFTP_HOST')
    sftp_port = int(os.getenv('SFTP_PORT', 22))
    sftp_user = os.getenv('SFTP_USER')
    sftp_password = os.getenv('SFTP_PASSWORD')
    wp_path = os.getenv('SFTP_WP_PATH', '/srv/htdocs/')
    plugin_path = wp_path.rstrip('/') + '/wp-content/plugins/nustart-entity-seo'
    
    try:
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        ssh.connect(hostname=sftp_host, port=sftp_port, username=sftp_user, password=sftp_password)
        
        sftp = ssh.open_sftp()
        
        print(f"[INFO] Checking: {plugin_path}")
        print("=" * 60)
        
        try:
            files = sftp.listdir(plugin_path)
            print(f"\n[OK] Plugin directory exists with {len(files)} items:")
            for f in files:
                try:
                    stat = sftp.stat(f"{plugin_path}/{f}")
                    file_type = "DIR" if stat.st_mode & 0o040000 else "FILE"
                    print(f"  [{file_type}] {f}")
                except:
                    print(f"  [?] {f}")
            
            # Check for includes directory
            try:
                includes_files = sftp.listdir(f"{plugin_path}/includes")
                print(f"\n[OK] includes/ directory has {len(includes_files)} files:")
                for f in includes_files:
                    print(f"  {f}")
            except:
                print("\n[WARN] includes/ directory not found")
                
        except FileNotFoundError:
            print(f"\n[ERROR] Plugin directory not found: {plugin_path}")
            print("\nChecking plugins directory...")
            try:
                plugins = sftp.listdir(wp_path.rstrip('/') + '/wp-content/plugins')
                print(f"\nPlugins found ({len(plugins)}):")
                for p in plugins:
                    print(f"  - {p}")
            except:
                print("Could not list plugins directory")
        
        sftp.close()
        ssh.close()
        
    except Exception as e:
        print(f"[ERROR] {str(e)}")
        import traceback
        traceback.print_exc()

if __name__ == '__main__':
    check_plugin_files()
