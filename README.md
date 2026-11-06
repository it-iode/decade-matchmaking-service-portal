# 🌊 Ocean Decade Portal

A modern Web Portal built using **Laravel 12** (PHP), **React.js** (via Vite), and **TailwindCSS**. This project is part of the UNESCO Ocean Decade Programme to connect researchers, stakeholders, and initiatives in sustainable ocean science.

---

## 🛠 Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: React.js with Vite
- **Styling**: TailwindCSS
- **Routing**: Laravel (server-side) / React Router (SPA)
- **Build Tool**: Vite
- **Package Manager**: NPM

---
## ⚙️ Setup Instructions With docker

### Prerequisites

- Docker Installer

### Installation Steps

1. **Clone the repository**
git clone https://github.com/labidi/decade-matchmaking-service-portal.git
cd oceandecade_portal/portal

2. **Run Laravel Sail**

```bash
php artisan sail:install
```
OR 

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v $(pwd):/var/www/html \
    -w /var/www/html \
    laravelsail/php81-composer:latest \
    composer install --ignore-platform-reqs
```

```bash
./vendor/bin/sail up -d
```
3. **Run migrations**
```bash
./vendor/bin/sail artisan migrate
```

4. **Install Node.js dependencies**
```bash
./vendor/bin/sail npm install
```

5. **Build assets**
```bash
./vendor/bin/sail npm run dev
```

The application will be available at `http://portal_dev.local`
## ⚙️ Setup Instructions Without docker

### Prerequisites

- PHP 8.2 or higher
  - php dom extension 
  - php mysql extension
- Composer
- Node.js (16+)
  - NPM
- MySQL/PostgreSQL
- Git

### Installation Steps

1. **Clone the repository**
```bash
git clone https://github.com/labidi/decade-matchmaking-service-portal.git
cd oceandecade_portal/portal
```

2. **Install PHP dependencies**
```bash
composer install
```

3. **Install Node.js dependencies**
```bash
npm install
```

4. **Configure your database**
   - Edit `.env` file and update database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password

APP_KEY=
```

5. **Environment Configuration**
```bash
php artisan key:generate
```

6. **Run migrations**
```bash
php artisan migrate
```

7. **Build assets**
```bash
npm run dev
```

8. **Start the development server**
```bash
php artisan serve
```

The application will be available at `http://portal_dev.local`
