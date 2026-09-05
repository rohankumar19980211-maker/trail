import os
import sys
import ftplib

server = os.environ.get('FTP_SERVER')
username = os.environ.get('FTP_USERNAME')
password = os.environ.get('FTP_PASSWORD')

if not server or not username or not password:
    print("❌ ERROR: Missing FTP secrets (FTP_SERVER, FTP_USERNAME, or FTP_PASSWORD)")
    sys.exit(1)

print(f"Connecting to FTP server: {server} as {username}...")
try:
    ftp = ftplib.FTP(server, timeout=30)
    ftp.login(username, password)
    print("✅ FTP Login successful!")
except Exception as e:
    print(f"❌ FTP Login failed: {e}")
    sys.exit(1)

pwd = ftp.pwd()
print(f"Initial FTP working directory: {pwd}")

items = ftp.nlst()
print(f"Directory listing at root ({len(items)} items): {items[:20]}")

# Determine target directory
target_dir = ""
if 'public_html' in items:
    print("Found 'public_html' subdirectory. Navigating into public_html...")
    ftp.cwd('public_html')
    target_dir = "public_html"
    sub_items = ftp.nlst()
    print(f"Inside public_html ({len(sub_items)} items): {sub_items[:20]}")
else:
    print("No 'public_html' subdirectory found; FTP user is already at web root.")

def upload_directory(local_path):
    for root, dirs, files in os.walk(local_path):
        rel_dir = os.path.relpath(root, local_path).replace('\\', '/')
        
        if rel_dir != ".":
            # Ensure remote directory exists
            try:
                ftp.mkd(rel_dir)
                print(f"📁 Created remote dir: {rel_dir}")
            except Exception:
                pass # Already exists
            try:
                ftp.cwd(rel_dir)
            except Exception as e:
                print(f"Failed to cwd to {rel_dir}: {e}")
                continue

        for file in files:
            file_path = os.path.join(root, file)
            with open(file_path, 'rb') as f:
                print(f"⬆️ Uploading: {rel_dir}/{file}...")
                ftp.storbinary(f"STOR {file}", f)

        if rel_dir != ".":
            # Go back up
            depth = len(rel_dir.split('/'))
            for _ in range(depth):
                ftp.cwd('..')

local_dir = sys.argv[1] if len(sys.argv) > 1 else 'public_html_package'
print(f"\nStarting upload from {local_dir}...")
upload_directory(local_dir)
print("\n🎉 All files uploaded successfully to shared hosting!")

ftp.quit()
