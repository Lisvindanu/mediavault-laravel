# MediaVault Backend API

Laravel 11 backend API untuk MediaVault - Offline-First Media Queue & Library Manager.

## 📋 Overview

MediaVault backend adalah REST API yang berfungsi untuk:
- ✅ Sinkronisasi metadata media antar device
- ✅ Manajemen user authentication dengan Sanctum
- ✅ Penyimpanan dan query playlist
- ✅ Analytics dan reporting
- ✅ Backup metadata pengguna

**PENTING:** Backend ini HANYA menyimpan metadata. File media TIDAK di-upload ke server.

## 🛠 Tech Stack

- **Framework:** Laravel 11.x
- **PHP:** 8.3+
- **Database:** MySQL 8.0+ / PostgreSQL 15+
- **Cache/Queue:** Redis 7.0+
- **Authentication:** Laravel Sanctum
- **Monitoring:** Laravel Telescope (dev)
- **Queue Manager:** Laravel Horizon

## 🚀 Quick Start (Development)

### Prerequisites

- PHP 8.3+
- Composer
- MySQL 8.0+ atau PostgreSQL 15+
- Redis 7.0+

### Installation

```bash
# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database di .env
# Update DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Run migrations
php artisan migrate

# Start development server
php artisan serve
```

Server akan berjalan di `http://localhost:8000`

## 📡 API Endpoints

Dokumentasi lengkap: [API_DOCUMENTATION.md](API_DOCUMENTATION.md)

## 🚢 Deployment

Panduan deployment ke VPS: [README_DEPLOYMENT.md](README_DEPLOYMENT.md)

## 📊 Database Schema

- **users** - User accounts
- **media** - Metadata media (UUID)
- **playlists** - User playlists (UUID)
- **playlist_media** - Junction table
- **watch_history** - Riwayat menonton
- **sync_logs** - Log sinkronisasi
- **analytics** - Analytics harian

## 📝 License

Proprietary - MediaVault Project
