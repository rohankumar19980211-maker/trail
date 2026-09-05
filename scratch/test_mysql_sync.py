import requests

session = requests.Session()

# 1. Check DB connection
r_db = session.get('https://liveteachcreate.in/api/db.php', timeout=10)
print('DB status:', r_db.text, flush=True)

# 2. Login admin
r_admin = session.post('https://liveteachcreate.in/api/login.php', json={'username': 'admin', 'password': 'admin123'}, timeout=10)
token = r_admin.json().get('token')
headers = {'Authorization': f'Bearer {token}'}
print('Admin Token OK:', bool(token), flush=True)

# 3. Create or update sanity_emp in MySQL
r_upsert = session.post('https://liveteachcreate.in/api/users.php', json={
    'username': 'sanity_emp',
    'password': 'SanityPass2026!',
    'name': 'Sanity Employee',
    'role': 'employee'
}, headers=headers, timeout=10)
print('Upsert sanity_emp status:', r_upsert.status_code, r_upsert.text, flush=True)

# 4. Test sanity_emp login
r_emp = session.post('https://liveteachcreate.in/api/login.php', json={'username': 'sanity_emp', 'password': 'SanityPass2026!'}, timeout=10)
print('Sanity Employee Login Status:', r_emp.status_code, r_emp.text, flush=True)
