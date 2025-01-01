# from Crypto.PublicKey import RSA
# from Crypto.Util.number import inverse
# from math import gcd

# # Define primes
# p = 65537
# q = 65539
# n = p * q
# phi_n = (p - 1) * (q - 1)

# # Find a valid e
# for e in [3, 5, 17, 65537]:
#     if gcd(e, phi_n) == 1:
#         print(e)
#         break
# else:
#     raise ValueError("No valid e found that is coprime to φ(n)")

# # Compute d
# d = inverse(e, phi_n)

# # Construct the key
# key = RSA.construct((n, e, d, p, q))

# # Export private key to PEM
# private_key = key.export_key(format="PEM")
# with open("private.pem", "wb") as private_file:
#     private_file.write(private_key)

# # Export public key to PEM
# public_key = key.publickey().export_key(format="PEM")
# with open("public.pem", "wb") as public_file:
#     public_file.write(public_key)

# print("Keys generated successfully.")

from cryptography.hazmat.primitives.asymmetric import rsa
from cryptography.hazmat.primitives import serialization

# Generate a 2048-bit private key
private_key = rsa.generate_private_key(
    public_exponent=65537,
    key_size=2048,
)

# Serialize the private key to PEM format
pem_private_key = private_key.private_bytes(
    encoding=serialization.Encoding.PEM,
    format=serialization.PrivateFormat.PKCS8,
    encryption_algorithm=serialization.NoEncryption(),
)

# Save to a file
with open("private.pem", "wb") as f:
    f.write(pem_private_key)

print("Generated 2048-bit RSA key and saved to 'private.pem'")
