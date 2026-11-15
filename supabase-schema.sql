-- ============================================================
-- IMG MEDIA VAULT — Supabase schema
-- Paste the whole file into Supabase -> SQL Editor -> Run.
-- Safe to run more than once (idempotent).
-- ============================================================

create extension if not exists pgcrypto;

-- ------------------------------------------------------------
-- profiles
-- ------------------------------------------------------------
create table if not exists public.profiles (
    id uuid primary key default gen_random_uuid(),
    email text unique,
    username text not null unique,
    full_name text,
    display_name text,
    password_hash text not null,
    avatar_url text,
    terms_accepted_at timestamptz,
    created_at timestamptz not null default now(),
    constraint username_length check (char_length(username) >= 3)
);

-- columns added after the first version of this project
alter table public.profiles add column if not exists email text;
alter table public.profiles add column if not exists full_name text;
alter table public.profiles add column if not exists terms_accepted_at timestamptz;

create unique index if not exists profiles_email_key on public.profiles (email);

-- ------------------------------------------------------------
-- media
-- ------------------------------------------------------------
create table if not exists public.media (
    id uuid primary key default gen_random_uuid(),
    user_id uuid not null references public.profiles(id) on delete cascade,
    title text not null,
    category text not null check (category in ('video', 'audio', 'image', 'document')),
    url text not null,
    storage_path text,
    mime_type text,
    file_size bigint,
    created_at timestamptz not null default now()
);

alter table public.media add column if not exists storage_path text;
alter table public.media add column if not exists mime_type text;
alter table public.media add column if not exists file_size bigint;

create index if not exists media_user_id_idx on public.media (user_id);
create index if not exists media_user_category_idx on public.media (user_id, category);
create index if not exists media_created_idx on public.media (user_id, created_at desc);

-- ------------------------------------------------------------
-- Row level security
-- PHP talks to Supabase with the service_role key, which
-- bypasses RLS. These policies only lock the tables down if the
-- anon key is ever used from a browser.
-- ------------------------------------------------------------
alter table public.profiles enable row level security;
alter table public.media    enable row level security;

drop policy if exists "no anon access to profiles" on public.profiles;
create policy "no anon access to profiles"
    on public.profiles for all to anon
    using (false) with check (false);

drop policy if exists "no anon access to media" on public.media;
create policy "no anon access to media"
    on public.media for all to anon
    using (false) with check (false);

-- ------------------------------------------------------------
-- Storage buckets: avatars + media (both public read)
-- ------------------------------------------------------------
-- Create buckets if they don't already exist (idempotent)
do $$
begin
    insert into storage.buckets (id, name, public)
    values ('avatars', 'avatars', true),
           ('media', 'media', true)
    on conflict (id) do update set public = true;
end $$;

drop policy if exists "Public read avatars" on storage.objects;
create policy "Public read avatars"
    on storage.objects for select
    using (bucket_id = 'avatars');

drop policy if exists "Public read media" on storage.objects;
create policy "Public read media"
    on storage.objects for select
    using (bucket_id = 'media');

drop policy if exists "Writes avatars" on storage.objects;
create policy "Writes avatars"
    on storage.objects for insert to authenticated, service_role
    with check (bucket_id = 'avatars');

drop policy if exists "Updates avatars" on storage.objects;
create policy "Updates avatars"
    on storage.objects for update to authenticated, service_role
    using (bucket_id = 'avatars');

drop policy if exists "Deletes avatars" on storage.objects;
create policy "Deletes avatars"
    on storage.objects for delete to authenticated, service_role
    using (bucket_id = 'avatars');

drop policy if exists "Writes media" on storage.objects;
create policy "Writes media"
    on storage.objects for insert to authenticated, service_role
    with check (bucket_id = 'media');

drop policy if exists "Deletes media" on storage.objects;
create policy "Deletes media"
    on storage.objects for delete to authenticated, service_role
    using (bucket_id = 'media');