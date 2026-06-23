# RedCross Supabase Free Setup

This project is wired for the Supabase free tier. Passwords must be handled by Supabase Auth only; do not put plain passwords in SQL tables, seed files, localStorage, or screenshots.

## 1. Create the free project

1. Open Supabase and create a new free project.
2. Open the SQL editor.
3. Run `supabase-schema.sql` from this repository.
4. Confirm the private Storage bucket `blood-request-documents` exists.

## 2. Add environment values

Laravel backend `.env`:

```txt
SUPABASE_URL="https://YOUR-PROJECT-REF.supabase.co"
SUPABASE_ANON_KEY="YOUR-PUBLISHABLE-OR-LEGACY-ANON-KEY"
SUPABASE_STORAGE_BUCKET="blood-request-documents"
SUPABASE_VERIFY_SSL=true
```

Use the base project URL only; do not include `/rest/v1`. Do not add Supabase service role, secret, JWT secret, or database password values to this app unless the code is changed to require them.

For Render or any hosted demo, add these as private hosting environment variables instead of committing them to GitHub. Never add the Supabase service role or secret key to public browser variables or repository files.

## 3. Create demo Auth accounts

In Supabase Dashboard > Authentication > Users, create these accounts with your chosen secure passwords:

```txt
donor@redcross.test
admin@redcross.test
superadmin@redcross.test
```

Then open SQL editor and run this after replacing each UUID with the matching Auth user id:

```sql
insert into profiles (id, role, full_name, email, phone, blood_type)
values
  ('DONOR_AUTH_UUID', 'donor', 'Juan Dela Cruz', 'donor@redcross.test', '+63 912 345 6789', 'O+'),
  ('ADMIN_AUTH_UUID', 'admin', 'RedCross Admin', 'admin@redcross.test', null, null),
  ('SUPERADMIN_AUTH_UUID', 'super_admin', 'Alex Rivera', 'superadmin@redcross.test', null, null)
on conflict (id) do update set
  role = excluded.role,
  full_name = excluded.full_name,
  email = excluded.email,
  phone = excluded.phone,
  blood_type = excluded.blood_type,
  updated_at = now();
```

## 4. Login behavior

- Donor login uses the email entered in the Donor Portal form.
- Admin login uses `admin@redcross.test` when the Staff ID is not an email.
- Super Admin login uses `superadmin@redcross.test` when the Staff ID or Department Code contains `super` or `SA-`.

You can also type the actual admin/super admin email into the Staff ID field.

## 5. Run locally

```powershell
composer install
npm install --ignore-scripts
copy .env.example .env
php artisan key:generate
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000/`.
