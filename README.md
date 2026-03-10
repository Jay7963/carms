# Co-Curricular Activities Registration and Management System (CARMS)

## Project Overview
CARMS is a web-based platform designed for Kirinyaga University to automate the management of co-curricular activities. It aims to replace manual, paper-based processes, significantly reducing registration times, improving data accuracy, and providing centralized tracking of student participation.

## Features
- **Student Portal**: Activity browsing and registration, personal schedule viewing, participation history tracking.
- **Activity Leader Hub**: Club membership management, event posting, attendance recording, member communication.
- **Administrative Dashboard**: University-wide oversight, club approval workflows, reporting and analytics, system user management.
- **Role-Based Access Control (RBAC)**: Enforces user permissions for students, activity leaders, and administrators.
- **Mobile-Responsive Design**: Ensures usability across all devices.
- **Reporting**: Generates participation reports for institutional accreditation and data export capabilities.

## Technical Specifications
- **Frontend**: HTML5, CSS3 (with Bootstrap 5), JavaScript (with jQuery)
- **Backend**: PHP 8.x
- **Database**: MySQL 8.x

## Setup Instructions

### 1. Prerequisites
Ensure you have the following installed on your system:
- Web Server (e.g., Apache, Nginx)
- PHP 8.x or higher
- MySQL 8.x or higher
- Composer (optional, for dependency management if introduced later)

### 2. Database Setup
1. Create a new MySQL database. You can use a tool like phpMyAdmin or the MySQL command line client.
   ```sql
   CREATE DATABASE carms_db;
   ```
2. Import the provided `database_schema.sql` file into your newly created database.
   ```bash
   mysql -u your_username -p carms_db < database_schema.sql
   ```
   (Replace `your_username` with your MySQL username and enter your password when prompted.)

### 3. Project Configuration
1. Copy the `.env.example` file to `.env` in the project root directory.
   ```bash
   cp .env.example .env
   ```
2. Open the `.env` file and update the database connection details and other application settings:
   ```
   DB_HOST=localhost
   DB_NAME=carms_db
   DB_USER=your_db_username
   DB_PASS=your_db_password

   APP_NAME="CARMS"
   APP_URL=http://localhost:8000 # Adjust if your local server runs on a different port or domain
   APP_ENV=development

   SECRET_KEY=YOUR_VERY_STRONG_SECRET_KEY_HERE # Generate a strong, unique key
   ```

### 4. Web Server Configuration
Configure your web server (Apache or Nginx) to point its document root to the `public/` directory of this project. This is crucial for security and proper routing.

**Example for Apache (`.htaccess` in `public/` directory - *to be created*):**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [QSA,L]
</IfModule>
```

### 5. Accessing the Application
After completing the setup, you should be able to access the application through your web browser at the configured `APP_URL`.

## Directory Structure
```
carms/
├── public/             # Publicly accessible files (HTML, CSS, JS, images)
│   ├── index.php       # Front controller for routing
│   ├── assets/         # Static assets
│   │   ├── css/        # CSS files (Bootstrap, custom styles)
│   │   ├── js/         # JavaScript files (jQuery, custom scripts)
│   │   └── img/        # Images
├── app/                # Application core files
│   ├── config/         # Configuration files (database, app settings)
│   ├── controllers/    # PHP classes for handling requests
│   ├── models/         # PHP classes for database interaction (data models)
│   ├── views/          # PHP files for rendering HTML (templates)
│   ├── services/       # Business logic and helper classes
│   └── lib/            # Third-party libraries or custom utility functions
├── vendor/             # Composer dependencies (if used)
├── database/           # Database related files
│   ├── migrations/     # Schema migration scripts
│   └── seeders/        # Data seeding scripts
├── .env                # Environment variables (template)
├── .htaccess           # Apache rewrite rules (for clean URLs)
├── README.md           # Project README
└── composer.json       # Composer configuration (if used)
```

## Contributing
[Instructions for contributing to the project, if applicable.]

## License
[License information, e.g., MIT, Apache 2.0, etc.]
