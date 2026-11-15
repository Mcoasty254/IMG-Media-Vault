# IMG Media Vault — PHP + Supabase

A private media vault. Users register (full name, email, password, confirm
password, terms of service), log back in with email + password, then store
**images, videos, audio and documents** — either as real file uploads or as
links. White cards on a dark-green background.

PHP handles pages and sessions. **Supabase** stores users, media rows and the
uploaded files.

## 1. Create a Supabase project

1. Go to [https://supabase.com](https://supabase.com) and create a project.
2. Wait until the database is ready.

## 2. Create tables and the storage buckets

1. In the dashboard open **SQL Editor**.
2. Paste everything in `supabase-schema.sql` and click **Run**.
3. Confirm **Storage → Buckets** shows two public buckets: `avatars` and `media`.

If a bucket is missing, create it manually:
**Storage → New bucket → name it `avatars` / `media` → Public**.

## 3. Copy your API keys

Open **Project Settings → API**:

| Value | Where it goes in `.env` |
| --- | --- |
| Project URL | `SUPABASE_URL` |
| `anon` `public` key | `SUPABASE_ANON_KEY` |
| `service_role` key | `SUPABASE_SERVICE_ROLE_KEY` |

The **service role key is a secret**. It is only used in PHP on the server.
Never put it in JavaScript or commit it to git.

Edit `.env` in this folder (copy from `.env.example` if needed).

## 4. Run the site (XAMPP)

1. Apache + PHP must have the **curl** extension (enabled by default in XAMPP).
2. Open `http://localhost/mutedia-vault/` (or `index.html`).
3. Create an account, upload a file (or paste a link), then browse, search,
   filter and delete your vault items.

## How the backend is used

```
Browser  →  PHP (sessions, forms, password hashing)
                ↓  REST + Storage API
           Supabase
             • Postgres table `profiles`
             • Postgres table `media`
             • Storage buckets `avatars` + `media`
```

- **Register / login / profile** read and write `profiles`.
- **Dashboard add / delete / filter / search** read and write `media` filtered
  by the logged-in `user_id`.
- **File uploads** go to the `media` bucket; the row stores `storage_path`,
  `mime_type` and `file_size`.
- **Avatar upload** sends the file to `avatars`, then saves the public URL on
  the profile.

PHP talks to:

- `https://YOUR_REF.supabase.co/rest/v1/...` (PostgREST)
- `https://YOUR_REF.supabase.co/storage/v1/object/...` (Storage)

The helper is `lib/supabase.php`.

## If something fails

| Symptom | Fix |
| --- | --- |
| “Supabase is not configured” | Fill `.env` with the real URL and **service_role** key. |
| “Could not reach Supabase” | Check URL, internet, and that curl is enabled (`php.ini`: `extension=curl`). |
| Register works but avatar fails | Create a **public** `avatars` bucket. |
| Add file fails | Create a **public** `media` bucket. |
| File upload blocked by PHP | Raise `upload_max_filesize` / `post_max_size` in `php.ini` (app limit is 25 MB). |
| CSS missing | Use Apache, not opening files as `file://`. Path should be `http://localhost/mutedia-vault/`. |
