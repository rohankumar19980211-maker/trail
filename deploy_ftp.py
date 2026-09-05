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

# The FTP-Deploy-Action this replaced used server-dir: /public_html/, so that
# absolute path is the known-good target. FTP_TARGET_DIR overrides it for
# addon domains, whose root is /public_html/<domain>/.
target_dir = os.environ.get('FTP_TARGET_DIR') or '/public_html'
target_found = False
for candidate in [target_dir, target_dir.lstrip('/'), 'public_html']:
    try:
        ftp.cwd(candidate)
        print(f"✅ Navigated to '{candidate}'. Current directory: {ftp.pwd()}")
        target_found = True
        break
    except Exception as e:
        print(f"Candidate '{candidate}' failed: {e}")

if not target_found:
    print(f"ℹ️ No candidate worked; using login directory '{ftp.pwd()}' as deployment root.")

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
print(f"\nUploaded {uploaded_count} files to {base_target}")
print(f"Files now in {base_target}: {final_items}")

# Read version.txt back off the server. A green build that changed nothing is
# worse than a red one, so a mismatch here fails the job.
local_version = open(os.path.join(local_dir, 'version.txt')).read().strip()
chunks = []
ftp.retrbinary('RETR version.txt', chunks.append)
remote_version = b''.join(chunks).decode().strip()

# Surface the layout on the run summary: on shared hosting the real document
# root for an addon domain is a sibling folder here, not base_target itself.
siblings = []
try:
    ftp.cwd('/')
    for name in ftp.nlst():
        try:
            ftp.cwd('/' + name.lstrip('/'))
            siblings.append(name + '/')
        except Exception:
            pass
except Exception as e:
    siblings.append(f'(listing failed: {e})')
ftp.cwd(base_target)

print(f"::notice title=DEPLOY_PATH::uploaded_to={base_target} remote_version={remote_version}")
print(f"::notice title=FTP_LAYOUT::login_dir={pwd} dirs_at_root={','.join(siblings[:25])}")

if remote_version != local_version:
    print(f"❌ Verification FAILED: {base_target}/version.txt reads '{remote_version}', expected '{local_version}'")
    sys.exit(1)

print(f"🎉 Verified: {base_target} is serving '{remote_version}'")
ftp.quit()
