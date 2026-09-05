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
print(f"Directory listing at root ({len(items)} items): {items}")

# Navigate to public_html if present
target_found = False
for candidate in ['public_html', '/public_html', './public_html']:
    try:
        ftp.cwd(candidate)
        print(f"✅ Successfully navigated to '{candidate}'. Current directory: {ftp.pwd()}")
        target_found = True
        break
    except Exception as e:
        print(f"Candidate '{candidate}' failed: {e}")

if not target_found:
    print(f"ℹ️ Using current directory '{ftp.pwd()}' as deployment root.")

base_target = ftp.pwd()

target_files = ftp.nlst()
print(f"::notice title=FTP_FILES::in_public_html={','.join(target_files[:30])}")
print(f"Target upload directory: {base_target}")
print(f"Existing files in target before upload: {target_files}")

local_dir = sys.argv[1] if len(sys.argv) > 1 else 'public_html_package'
print(f"\nStarting upload from '{local_dir}'...")

uploaded_count = 0
for root, dirs, files in os.walk(local_dir):
    rel_dir = os.path.relpath(root, local_dir).replace('\\', '/')
    
    # Reset to base_target for every folder
    ftp.cwd(base_target)
    
    if rel_dir != ".":
        parts = rel_dir.split('/')
        for part in parts:
            try:
                ftp.mkd(part)
            except Exception:
                pass
            ftp.cwd(part)

    for file in files:
        file_path = os.path.join(root, file)
        size = os.path.getsize(file_path)
        with open(file_path, 'rb') as f:
            print(f"⬆️ [{ftp.pwd()}] Uploading: {file} ({size} bytes)...")
            ftp.storbinary(f"STOR {file}", f)
            uploaded_count += 1

ftp.cwd(base_target)
final_items = ftp.nlst()
print(f"\n🎉 Deployment completed! Uploaded {uploaded_count} files.")
print(f"Current files in {base_target}: {final_items}")

ftp.quit()
