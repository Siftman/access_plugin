import requests
import json
from cryptography.hazmat.primitives import hashes, serialization
from cryptography.hazmat.primitives.asymmetric import padding, rsa
from cryptography.hazmat.backends import default_backend
import base64
from urllib.parse import urlencode
import time
import sys

API_ENDPOINT = "http://localhost:8080/wp-json/api/v1/products"

def load_private_key():
    try:
        with open('private.pem', 'rb') as key_file:
            key_contents = key_file.read()
            print("Key file contents:", key_contents.decode('utf-8')) 
            
            try:
                return serialization.load_pem_private_key(
                    key_contents,
                    password=None,
                    backend=default_backend()
                )
            except ValueError as e:
                print(f"Error loading key without password: {e}")
                if b"BEGIN PRIVATE KEY" in key_contents:
                    print("Key appears to be in PKCS#8 format")
                elif b"BEGIN RSA PRIVATE KEY" in key_contents:
                    print("Key appears to be in PKCS#1 format")
                else:
                    print("Key format not recognized")
                
                return serialization.load_pem_private_key(
                    key_contents,
                    password=b"",
                    backend=default_backend()
                )
    except FileNotFoundError:
        print("Error: private.pem file not found!")
        sys.exit(1)
    except Exception as e:
        print(f"Error loading private key: {e}")
        print("Please ensure private.pem is in the correct format and has proper permissions")
        sys.exit(1)

try:
    private_key = load_private_key()
    print("Successfully loaded private key")
except Exception as e:
    print(f"Failed to load private key: {e}")
    sys.exit(1)

def sign_payload(payload: str, private_key) -> str:
    if isinstance(payload, str):
        payload = payload.encode('utf-8')
    
    digest = hashes.Hash(hashes.SHA256())
    digest.update(payload)
    payload_hash = digest.finalize()
    
    print(f"Payload hash (hex): {payload_hash.hex()}")
    
    signature = private_key.sign(
        payload,
        padding.PKCS1v15(),
        hashes.SHA256()
    )
    
    print(f"Raw signature (hex): {signature.hex()}")
    encoded_sig = base64.b64encode(signature).decode('utf-8')
    print(f"Base64 encoded signature: {encoded_sig}")
    
    return encoded_sig

def get_products():
    params = {
        'page': 1,
        'per_page': 10,
        'timestamp': str(int(time.time()))
    }
    
    canonical_string = urlencode(sorted(params.items()))
    print(f"\nCanonical string: {canonical_string}")
    
    signature = sign_payload(canonical_string, private_key)
    
    headers = {
        'X-Shopino-Signature': signature
    }
    
    try:
        print(f"\nMaking request to: {API_ENDPOINT}")
        print(f"With parameters: {params}")
        print(f"With signature: {signature}")
        
        response = requests.get(
            API_ENDPOINT,
            params=params,
            headers=headers,
            verify=False
        )
        
        print(f"\nFull URL: {response.url}")
        
        response.raise_for_status()
        
        print("\nResponse Status:", response.status_code)
        print("Response Body:", response.json())
        
    except requests.exceptions.RequestException as e:
        print(f"\nError occurred: {e}")
        if hasattr(e, 'response') and e.response is not None:
            print(f"Response Status: {e.response.status_code}")
            print(f"Response Body: {e.response.text}")

if __name__ == "__main__":
    get_products() 