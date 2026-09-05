# ModuleUploader (FreeScout module)

Adds an **Upload Module** page to FreeScout's admin area (`/modules/upload`)
so an administrator can install a custom or third-party module by uploading
its `.zip` file, instead of copying the folder onto the server by hand.

FreeScout's core "Manage > Modules" page only lets you install modules from
the official freescout.net directory (via license key). There is no built-in
way to upload your own module through the UI — this module fills that gap.
It does not modify any core files.

## What it does

1. Adds `GET /modules/upload` (the form) and `POST /modules/upload` (the
   handler), both restricted to the `admin` role via the same
   `middleware => ['auth', 'roles'], 'roles' => ['admin']` pattern the core
   Modules routes use.
2. On submit, it:
   - Validates the upload is a `.zip` file (size capped at 50&nbsp;MB —
     see `MAX_ZIP_KB` in `ModuleUploaderController`).
   - Inspects every entry in the archive *before* extracting anything, to
     reject zip-slip attempts (absolute paths, `..` segments) and to make
     sure the archive contains exactly **one** top-level folder.
   - Requires a `module.json` at the root of that folder with a valid
     `alias`.
   - Refuses to overwrite an existing folder or an already-installed module
     with the same alias.
   - Extracts to a temp directory, then moves the validated module folder
     into FreeScout's `Modules/` directory.
3. Redirects back to the core **Manage > Modules** page, where the new
   module shows up under "Installed Modules" — **not activated**. You still
   click "Activate" there, same as any other module. This module never
   auto-activates code it just received over HTTP.

## Installing this module

1. Copy this `ModuleUploader` folder into your FreeScout installation's
   `Modules/` directory, so you end up with `Modules/ModuleUploader/`.
2. Log in to FreeScout as an admin and go to **Manage > Modules**.
   `ModuleUploader` will appear under "Installed Modules" (its
   `module.json` ships with `"active": 1`, but FreeScout still tracks
   activation per-install in the database, so click **Activate** if it
   isn't already active).
3. Visit `/modules/upload` (or use the "Upload Module" link that now appears
   in the Modules sidebar, next to "Installed Modules" and "Modules
   Directory") to upload further modules as zip files.

If the sidebar link doesn't show up right after activating, try
**Deactivate** then **Activate** again on the Modules page (or run
`php artisan freescout:clear-cache` on the server) — that's FreeScout's
usual fix for stale module caches.

The module's icon on the Manage > Modules page comes from
`Public/img/icon.png`, referenced via the `img` field in `module.json`. If
it doesn't appear (falls back to the generic puzzle-piece placeholder),
your FreeScout install may need its module assets published/symlinked —
try `php artisan module:publish ModuleUploader` on the server, or check
how `public/modules/` is set up for your other installed modules.

## How the sidebar link is added

Core's `resources/views/modules/sidebar_menu.blade.php` has no filter/hook
to add a link to, so `ModuleUploaderServiceProvider` prepends its own
`Resources/views/overrides` folder to Laravel's view search path
(`View::getFinder()->prependLocation(...)`). That makes our copy of
`modules/sidebar_menu.blade.php` — with the extra "Upload Module" `<li>` —
resolve ahead of core's, without touching any core file.

**Trade-off:** it's a copy, not a patch, so if a future FreeScout release
changes that sidebar (e.g. adds a new section), this override needs to be
updated to match or it'll silently keep showing the old version. Worth a
quick diff against core's `sidebar_menu.blade.php` after upgrading
FreeScout.

## Security note

Installing a module is equivalent to giving someone write access to your
FreeScout server's PHP code — a module can run arbitrary code once
activated. This tool doesn't change that trust model, it just moves the
"copy files onto the server" step into the browser. Treat `/modules/upload`
as sensitive as SSH/SFTP access, and only give the `admin` role to people
you'd hand server access to. Only upload modules from sources you trust.

## Disclaimer

This is an independent, community-built module. It is **not affiliated
with, endorsed by, or supported by FreeScout or freescout.net** — don't
file bugs about it with their support. It's meant for installing modules
**you wrote yourself or otherwise have the right to distribute**; it's a
generic zip-upload utility and doesn't bypass licensing on anyone's
commercial modules.
