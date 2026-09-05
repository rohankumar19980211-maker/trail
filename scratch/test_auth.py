import requests

session = requests.Session()

# 1. Admin login
r1 = session.post('https://liveteachcreate.in/api/login.php', json={'username': 'admin', 'password': 'admin123'}, timeout=10)
token = r1.json().get('token')
headers = {'Authorization': f'Bearer {token}'}

# 2. Get all users
r_users = session.get('https://liveteachcreate.in/api/users.php', headers=headers, timeout=10)
print('Users in DB:', [(u.get('id') or u.get('_id'), u.get('username'), u.get('role')) for u in r_users.json()], flush=True)

# 3. Find sanity_emp
sanity_user = next((u for u in r_users.json() if u.get('username') == 'sanity_emp'), None)
if sanity_user:
    uid = sanity_user.get('id') or sanity_user.get('_id')
    print('Found sanity_emp with ID:', uid)
    # Delete and recreate with clean password
    r_del = session.delete(f'https://liveteachcreate.in/api/users.php?id={uid}', headers=headers, timeout=10)
    print('Delete status:', r_del.status_code, r_del.text)

# 4. Create fresh sanity_emp
r_create = session.post('https://liveteachcreate.in/api/users.php', json={
    'username': 'sanity_emp',
    'password': 'SanityPass2026!',
    'name': 'Sanity Check Employee',
    'role': 'employee'
}, headers=headers, timeout=10)
print('Create status:', r_create.status_code, r_create.text)

# 5. Test login
r_login = session.post('https://liveteachcreate.in/api/login.php', json={'username': 'sanity_emp', 'password': 'SanityPass2026!'}, timeout=10)
print('Sanity Employee Login Status:', r_login.status_code, r_login.text)
