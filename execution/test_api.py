"""Quick test of REST API endpoint"""
import os
import requests
from requests.auth import HTTPBasicAuth
from dotenv import load_dotenv

load_dotenv()

url = f"{os.getenv('WP_API_URL')}/nustart-entity/v1/entities"
auth = HTTPBasicAuth(os.getenv('WP_API_USER'), os.getenv('WP_API_PASSWORD'))

print(f"Testing: {url}")
print(f"User: {os.getenv('WP_API_USER')}")
response = requests.get(url, auth=auth)
print(f"Status: {response.status_code}")
print(f"Headers: {response.headers.get('content-type')}")
print(f"Response: {response.text[:500]}")
