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

### Reliable Confirmation Email Delivery

Supabase sends registration-confirmation and password-recovery emails. This app limits repeated confirmation-email requests for a short time and shows a friendly message if Supabase reports an email rate limit.

For a deployed public demo, configure a custom SMTP provider in the Supabase Dashboard under **Authentication > SMTP Settings**. Resend has a free tier suitable for low-volume testing. Keep the SMTP API key and sender-domain credentials only in Supabase; never add them to Laravel, Render, `.env.example`, or GitHub.

### Test Mode Without Email Confirmation

For short, supervised classroom testing, you can let new donors sign in immediately after registration:

1. In Supabase Dashboard, open **Authentication > Providers > Email** and turn **Confirm Email** off.
2. Set `SUPABASE_EMAIL_CONFIRMATION_REQUIRED=false` in the local `.env` file or the Render environment for the test deployment.

The application then expects Supabase to return a session token at signup and signs the new donor in immediately. No confirmation email or SMTP provider is needed. Existing accounts created while confirmation was enabled remain unconfirmed until they are confirmed or replaced with a new test account.

Re-enable **Confirm Email** and set `SUPABASE_EMAIL_CONFIRMATION_REQUIRED=true` before any public or long-running deployment. Without confirmation, a person can register using an email address they do not own.

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
