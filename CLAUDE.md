# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Xassaid Laravel Backend is a content management system for managing audio files, articles, and documents with a role-based admin interface. It's a REST API + Web application hybrid built with Laravel 11.9.

- **Framework**: Laravel 11.9 (PHP 8.2+)
- **Database**: SQLite (local) / MySQL (production)
- **API Prefix**: `/api/v1`
- **Authentication**: Laravel Sanctum (tokens) + Session-based (web)

## Common Commands

```bash
# Development
composer install && npm install
npm run dev                    # Vite dev server
php artisan serve              # Laravel dev server

# Database
php artisan migrate
php artisan migrate:fresh

# Testing
./vendor/bin/phpunit
./vendor/bin/phpunit tests/Feature/SomeTest.php

# Code formatting
./vendor/bin/pint

# Production deployment (via Makefile)
make all                       # Full rsync + deploy
make rsync                     # Sync to server
make deploy                    # Restart Docker containers
make logs                      # Stream container logs
make shell                     # SSH into container
make migrate                   # Run migrations on production
make cache                     # Rebuild config/route/view caches
```

## Architecture

### Core Domain Models
- **Audio** - Audio tracks with category relationship, uses slug-based URLs
- **AudioCategory** - Audio groupings with cover images
- **Article** - Blog articles with SEO fields (seo_title, seo_keywords, seo_description)
- **File** - Document library files for download
- **User** - Users with many-to-many Role relationship
- **Role** - `ROLE_ADMIN` and `ROLE_SUPER_ADMIN`

### Key Patterns

**Media Optimization** (`App\Support\MediaOptimizer`):
- `optimizeImage()` - Resize images (1200x1200, quality 82%)
- `optimizeAudio()` - Compress via FFmpeg to configured bitrate
- Files upload to external endpoint via `XASSAID_FILES_URI`

**Role-Based Access Control**:
- `EnsureUserIsAdmin` middleware - requires authenticated admin
- `EnsureUserIsSuperAdmin` middleware - requires super admin role
- User model has `hasRole()` method

**Slug Generation**:
All content models use slug-based URLs. The `generateSlug()` method is in the base Controller.

### Route Structure

**Web Routes** (session auth, admin middleware):
- `/` - Dashboard
- `/audios`, `/articles`, `/library`, `/users` - CRUD pages
- `/categories/audios` - Audio category management
- `/file-health` - Santé des fichiers (scan des médias manquants, doublons, audios courts) — super-admin

**API Routes** (`/api/v1`, public):
- `/homepage` - Frontend homepage data
- `/search/{term}` - Search audios and files
- `/article/{slug}`, `/articles/page/{page}` - Article endpoints
- `/file/{slug}`, `/files/page/{page}` - File endpoints
- `/audios/category/{category}`, `/audios/{type}` - Audio endpoints

## Configuration rule

Never call `env()` outside `config/*.php`: production runs with a cached config (`make cache`), where `env()` returns null. Read values through `config('services.xassaid.*')` / `config('app.force_https')` and add new variables to `config/services.php` first.

## Key Environment Variables

```
XASSAID_FILES_URI=          # External file upload endpoint
XASSAID_UPLOAD_KEY=         # API key for file upload service
XASSAID_AUDIO_BITRATE=96    # Audio compression bitrate (kbps)
SCOUT_DRIVER=algolia        # Search driver
FORCE_HTTPS=false           # Force https in generated URLs (set to true behind the TLS proxy in production)
```

## Docker

The project uses PHP 8.2 with Apache, includes FFmpeg for audio processing. Port 8000 maps to container port 80.

```bash
docker-compose up -d          # Start containers
docker-compose logs -f        # View logs
```

## Backoffice layout & YouTube section (2026-09)

- No CSS framework: the back-office uses its own stylesheet and vanilla JS in `public/backoffice/app.css` / `app.js` (no Bootstrap, jQuery, DataTables or select2). Components: `.card`, `.table.stack` (stacks into cards under 720px, cells need `data-label`), `.btn*`, `.field/.input/.select`, `.badge`, `.flash`, `.tabs`, `.toolbar`, `<x-modal>` (native `<dialog>`, opened with `data-open="#id"`; the trigger can carry `data-action` and `data-set-<field>` to prefill the form), `select[data-search]` (searchable combobox, `data-group` on options for a prefix), `form[data-upload]` (XHR upload with progress), partials `partials.flash` and `partials.pagination`.
- Lists are paginated server-side (`paginate()->withQueryString()`) with a `q` search parameter; render them with `$items->links('partials.pagination')`.
- Access: every page requires `ROLE_ADMIN`; the Communauté (videos, app users), Publication (YouTube) and Administration (users, file health) sections are restricted to the super admin (`User::isSuperAdmin()`, enforced by `EnsureUserIsSuperAdmin`) and hidden from the menu for other admins.
- Layout: `resources/views/base.blade.php` (sidebar grouped menu, `partials/sidebar-nav.blade.php`). Menu entries are declared in the `$menu` array of the layout; add new pages there with an "active" pattern for `request()->is()`.
- Dashboard: `OverviewController@dashboard` → `pages/overview.blade.php` (counters, YouTube publication card, audios by type, "à corriger", recent audios/articles).
- YouTube publication tracking: `/youtube` (`YoutubeAutomationController`, `App\Support\YoutubeAutomation`) relays the JSON API of the `../xassaid-automation` service configured with `YOUTUBE_AUTOMATION_URL` / `YOUTUBE_AUTOMATION_TOKEN` (`config/services.php`). The Laravel container reaches the host service via `host.docker.internal` (extra_hosts in docker-compose).
