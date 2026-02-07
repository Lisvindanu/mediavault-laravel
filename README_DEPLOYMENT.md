# MediaVault Backend - Deployment Guide

## Stack Requirements

- **PHP:** 8.3+
- **Laravel:** 11.x
- **Database:** MySQL 8.0+ atau PostgreSQL 15+
- **Cache/Queue:** Redis 7.0+
- **Web Server:** Nginx 1.24+
- **Process Manager:** Supervisor

## VPS Setup Steps

### 1. Update System & Install Dependencies

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.3 and extensions
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-mysql php8.3-redis \
    php8.3-mbstring php8.3-xml php8.3-bcmath php8.3-curl php8.3-zip php8.3-gd

# Install MySQL
sudo apt install -y mysql-server

# Install Redis
sudo apt install -y redis-server

# Install Nginx
sudo apt install -y nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Supervisor
sudo apt install -y supervisor
```

### 2. Setup MySQL Database

```bash
sudo mysql

# Di dalam MySQL prompt:
CREATE DATABASE mediavault CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mediavault_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON mediavault.* TO 'mediavault_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Clone & Setup Application

```bash
# Clone repository
cd /var/www
sudo git clone <your-repo-url> mediavault
cd mediavault

# Set permissions
sudo chown -R www-data:www-data /var/www/mediavault
sudo chmod -R 775 /var/www/mediavault/storage
sudo chmod -R 775 /var/www/mediavault/bootstrap/cache

# Install dependencies
composer install --no-dev --optimize-autoloader

# Setup environment
cp .env.example .env
php artisan key:generate

# Edit .env dengan konfigurasi production
nano .env
```

### 4. Configure .env for Production

Update `/var/www/mediavault/.env`:

```env
APP_NAME=MediaVault
APP_ENV=production
APP_KEY=base64:GENERATED_KEY
APP_DEBUG=false
APP_URL=https://api.mediavault.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mediavault
DB_USERNAME=mediavault_user
DB_PASSWORD=YOUR_STRONG_PASSWORD

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 5. Run Migrations

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6. Configure Nginx

Create `/etc/nginx/sites-available/mediavault`:

```nginx
server {
    listen 80;
    server_name api.mediavault.yourdomain.com;
    root /var/www/mediavault/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site:

```bash
sudo ln -s /etc/nginx/sites-available/mediavault /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 7. Setup SSL with Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d api.mediavault.yourdomain.com
```

### 8. Setup Queue Worker with Supervisor

Create `/etc/supervisor/conf.d/mediavault-worker.conf`:

```ini
[program:mediavault-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/mediavault/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/mediavault/storage/logs/worker.log
stopwaitsecs=3600
```

Start supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start mediavault-worker:*
```

### 9. Setup Horizon (Optional - for better queue management)

If using Horizon:

```bash
php artisan horizon:install
php artisan vendor:publish --tag=horizon-assets
```

Create `/etc/supervisor/conf.d/mediavault-horizon.conf`:

```ini
[program:mediavault-horizon]
process_name=%(program_name)s
command=php /var/www/mediavault/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/mediavault/storage/logs/horizon.log
stopwaitsecs=3600
```

### 10. Setup Cron Jobs

Add to crontab (`sudo crontab -e`):

```bash
* * * * * cd /var/www/mediavault && php artisan schedule:run >> /dev/null 2>&1
```

### 11. Security Hardening

```bash
# Disable directory listing
sudo nano /etc/nginx/nginx.conf
# Add: autoindex off;

# Setup firewall
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable

# Secure MySQL
sudo mysql_secure_installation

# Secure Redis
sudo nano /etc/redis/redis.conf
# Uncomment and set: requirepass YOUR_REDIS_PASSWORD
sudo systemctl restart redis
```

### 12. Monitoring & Logs

```bash
# View Laravel logs
tail -f /var/www/mediavault/storage/logs/laravel.log

# View Nginx logs
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log

# View Queue worker logs
tail -f /var/www/mediavault/storage/logs/worker.log

# Check queue status
php artisan queue:work --once
```

## Deployment Updates

For future updates:

```bash
cd /var/www/mediavault
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo supervisorctl restart mediavault-worker:*
```

## API Endpoints

All endpoints are prefixed with `/api`:

### Authentication
- `POST /api/register` - Register new user
- `POST /api/login` - Login user
- `POST /api/logout` - Logout user (requires auth)

### Media
- `GET /api/media` - Get all media (requires auth)
- `POST /api/media/sync` - Sync media metadata (requires auth)
- `GET /api/media/{id}` - Get specific media (requires auth)
- `PUT /api/media/{id}` - Update media (requires auth)
- `DELETE /api/media/{id}` - Delete media (requires auth)

### Playlists
- `GET /api/playlists` - Get all playlists (requires auth)
- `POST /api/playlists` - Create playlist (requires auth)
- `GET /api/playlists/{id}` - Get specific playlist (requires auth)
- `PUT /api/playlists/{id}` - Update playlist (requires auth)
- `DELETE /api/playlists/{id}` - Delete playlist (requires auth)
- `POST /api/playlists/{id}/media` - Add media to playlist (requires auth)
- `DELETE /api/playlists/{id}/media/{mediaId}` - Remove media from playlist (requires auth)

### Analytics
- `GET /api/analytics/summary` - Get analytics summary (requires auth)

## Troubleshooting

### Issue: 500 Internal Server Error
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check Nginx error logs
tail -f /var/log/nginx/error.log

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Issue: Queue not processing
```bash
# Check supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart mediavault-worker:*

# Check worker logs
tail -f storage/logs/worker.log
```

### Issue: Database connection failed
```bash
# Test MySQL connection
mysql -u mediavault_user -p mediavault

# Check MySQL service
sudo systemctl status mysql

# Check .env database credentials
```

## Performance Optimization

```bash
# Enable OPcache
sudo nano /etc/php/8.3/fpm/php.ini
# Set:
# opcache.enable=1
# opcache.memory_consumption=256
# opcache.interned_strings_buffer=16
# opcache.max_accelerated_files=10000

# Restart PHP-FPM
sudo systemctl restart php8.3-fpm
```

## Backup Strategy

```bash
# Database backup
mysqldump -u mediavault_user -p mediavault > backup_$(date +%Y%m%d).sql

# Application backup
tar -czf mediavault_backup_$(date +%Y%m%d).tar.gz /var/www/mediavault
```

---

**Support:** For issues, check Laravel logs and refer to documentation.
