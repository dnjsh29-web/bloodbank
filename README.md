# RedCross Blood Bank Laravel Portal

Laravel demo portal for RedCross-style donor scheduling, blood requests, inventory, reports, notifications, and Supabase-backed authentication/data.

## Safety Notes

- Do not commit `.env`, Supabase service role keys, JWT secrets, database passwords, user session files, logs, local SQLite databases, or uploaded documents.
- This repository uses `.env.example` with placeholders only.
- Use demo/test data only for public deployments. Do not enter real donor medical data, home addresses, phone numbers, or private account details into a public test project.
- The app only needs a Supabase URL and anon/publishable key for normal Laravel operation. Do not add a Supabase service role key unless the backend is redesigned to require server-only privileged actions.

## Local Setup

```powershell
composer install
npm install --ignore-scripts
copy .env.example .env
php artisan key:generate
npm run build
php artisan serve
```

Then open `http://127.0.0.1:8000`.

Set these values in your local `.env`:

```txt
SUPABASE_URL=https://YOUR-PROJECT-REF.supabase.co
SUPABASE_ANON_KEY=YOUR-PUBLISHABLE-OR-LEGACY-ANON-KEY
SUPABASE_STORAGE_BUCKET=blood-request-documents
SUPABASE_VERIFY_SSL=true
```

## Supabase Setup

Use `supabase-schema.sql` in the Supabase SQL Editor to create/update the schema, RLS policies, demo tables, and storage bucket setup. See `SUPABASE_SETUP.md` for auth and setup notes.

For password reset and email confirmation to work in a deployed demo, add your deployed URL to Supabase:

- Site URL: `https://YOUR-RENDER-SERVICE.onrender.com`
- Redirect URLs:
  - `https://YOUR-RENDER-SERVICE.onrender.com/login`
  - `https://YOUR-RENDER-SERVICE.onrender.com/password/reset`

## Render Deployment

This repo includes a Docker-based `render.yaml` Blueprint for Render free web services.

1. Push this repository to GitHub.
2. Open the Render Blueprint link for the repo:
   `https://dashboard.render.com/blueprint/new?repo=https://github.com/dnjsh29-web/bloodbank`
3. Fill these private environment variables in Render:
   - `APP_URL`
   - `APP_KEY`
   - `SUPABASE_URL`
   - `SUPABASE_ANON_KEY`
4. Use `php artisan key:generate --show` locally to create the private `APP_KEY` value for Render.
5. Apply the Blueprint and wait for the service to deploy.

The Render free plan can sleep after inactivity, so first load after a quiet period may be slow.

## Demo Accounts

Create demo users in Supabase Auth and matching `profiles` rows before testing login flows:

```txt
donor@redcross.test
admin@redcross.test
superadmin@redcross.test
```

Use test-only passwords and never reuse real account passwords in a public demo.

## Checks

```powershell
npm run build
php artisan test
```
