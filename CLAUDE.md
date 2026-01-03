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
- `/analytics` - Analytics dashboard

**API Routes** (`/api/v1`, public):
- `/homepage` - Frontend homepage data
- `/search/{term}` - Search audios and files
- `/article/{slug}`, `/articles/page/{page}` - Article endpoints
- `/file/{slug}`, `/files/page/{page}` - File endpoints
- `/audios/category/{category}`, `/audios/{type}` - Audio endpoints

## Key Environment Variables

```
XASSAID_FILES_URI=          # External file upload endpoint
XASSAID_UPLOAD_KEY=         # API key for file upload service
XASSAID_AUDIO_BITRATE=96    # Audio compression bitrate (kbps)
SCOUT_DRIVER=algolia        # Search driver
FORCE_HTTPS=false           # Force HTTPS in production
```

## Docker

The project uses PHP 8.2 with Apache, includes FFmpeg for audio processing. Port 8000 maps to container port 80.

```bash
docker-compose up -d          # Start containers
docker-compose logs -f        # View logs
```
