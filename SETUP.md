# Laravel Project Setup Guide

## Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js & NPM
- SQLite (or MySQL/PostgreSQL if preferred)

## Initial Setup (First Time Only)

### 1. Clone the Repository
```bash
git clone <repository-url>
cd chamberapis
```

### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 3. Environment Configuration
```bash
# Copy the example environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Database Setup
```bash
# Create SQLite database (if not exists)
touch database/database.sqlite

# Run migrations
php artisan migrate

# (Optional) Seed database
php artisan db:seed
```

### 5. Storage Setup
```bash
# Create symbolic link for storage
php artisan storage:link

# Set proper permissions
chmod -R 775 storage bootstrap/cache
```

### 6. Clear Caches (if needed)
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## Running the Application

### Development Server

#### Option 1: Using Laravel's built-in server
```bash
php artisan serve
```
Access at: http://localhost:8000

#### Option 2: Using Composer dev script (includes queue, logs, and Vite)
```bash
composer run dev
```
This runs:
- Laravel server (port 8000)
- Queue worker
- Log viewer (Pail)
- Vite dev server

#### Option 3: Separate processes (recommended for development)

Terminal 1 - Backend:
```bash
php artisan serve
```

Terminal 2 - Frontend Assets:
```bash
npm run dev
```

Terminal 3 - Queue Worker (if needed):
```bash
php artisan queue:work
```

## Git Workflow

The following files/folders are gitignored and won't be committed:
- `.env` (environment configuration)
- `/vendor` (Composer dependencies)
- `/node_modules` (NPM dependencies)
- `package-lock.json` (NPM lock file)
- `/database/database.sqlite` (SQLite database)
- `/public/build` (compiled assets)
- `/public/storage` (storage symlink)
- `/storage/*.key` (encryption keys)
- Cache and log files

### When Pushing Code to Git
You can safely push code changes without worrying about local files:
```bash
git add .
git commit -m "Your commit message"
git push origin <branch-name>
```

### When Pulling Code from Git
After pulling new changes, run:
```bash
# Update dependencies
composer install
npm install

# Run new migrations (if any)
php artisan migrate

# Clear caches
php artisan config:clear
php artisan cache:clear
```

## Troubleshooting

### Permission Issues
```bash
chmod -R 775 storage bootstrap/cache
```

### Config Cached
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Database Connection Issues
- Check `.env` file has correct database credentials
- For SQLite, ensure `database/database.sqlite` exists
- Run `php artisan migrate:status` to check migration status

### Telescope Issues
If Laravel Telescope gives errors:
```bash
php artisan telescope:install
php artisan migrate
```

## Available Artisan Commands

```bash
# View all routes
php artisan route:list

# Check application status
php artisan about

# Run tests
php artisan test

# Code style fixing (Laravel Pint)
./vendor/bin/pint

# Interactive shell (Tinker)
php artisan tinker
```

## Project Information

- **Laravel Version**: 12.x
- **PHP Version**: 8.2+
- **Database**: SQLite (default) / MySQL / PostgreSQL
- **Frontend**: Vite + Tailwind CSS 4.0
- **Additional Packages**:
  - Laravel Sanctum (API authentication)
  - Laravel Telescope (Debugging)
  - Laravel Socialite (OAuth)
  - Laravel Tinker (REPL)
  - Laravel Pail (Log viewer)
  - Laravel Sail (Docker)

## Notes

- The project is configured to use SQLite by default for easy local development
- Telescope is installed but not auto-discovered (manual registration needed)
- Queue and cache drivers are set to 'database' by default
- Session driver is set to 'database'