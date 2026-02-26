# Database Setup Guide for Spotify Clone

## Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher (MariaDB also works)
- Web server (Apache/Nginx) - XAMPP/WAMP recommended for Windows
- Project files uploaded to web server directory (e.g., `htdocs`)

## Quick Setup (Recommended)
1. **Configure Database Credentials**:
   - Open `config/db.php`
   - Edit the `$database_config` array:
   ```php
   $database_config = [
       'host' => 'localhost',           // Usually 'localhost'
       'dbname' => 'spotify_clone',     // Database name
       'username' => 'root',            // Your MySQL username
       'password' => '',                // Your MySQL password
       'charset' => 'utf8'
   ];
   ```

2. **Run Auto-Installer**:
   - Open browser and navigate to: `http://localhost/your-project-folder/install.php`
   - Wait for success message
   - Database and sample data will be created automatically

3. **Verify Installation**:
   - Visit main site: `http://localhost/your-project-folder/index.php`
   - Admin panel: `http://localhost/your-project-folder/admin/login.php`
   - Default admin: username `admin`, password `admin123`

## Manual Setup (Alternative)
If auto-installer fails:

1. **Create Database**:
   - Open phpMyAdmin or MySQL command line
   - Create database: `CREATE DATABASE spotify_clone CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;`

2. **Import SQL File**:
   - Select the `spotify_clone` database
   - Import `database/spotify_clone-new.sql`

3. **Update Configuration**:
   - Edit `config/db.php` with your database credentials (see step 1 above)

## Configuration Notes
- All database settings are in `config/db.php` - this is the only file others need to edit
- Uses PDO for secure database connections
- Supports UTF-8 encoding for international characters

## Troubleshooting
- **Connection Failed**: Check MySQL is running and credentials are correct
- **Permission Denied**: Ensure database user has CREATE/DROP privileges
- **File Not Found**: Verify `database/spotify_clone-new.sql` exists and is readable
- **Auto-installer Issues**: Try manual setup or check PHP/MySQL versions

## Default Credentials
- **Admin Panel**: admin / admin123
- **User Account**: Register new account or use setup script

---
*This guide is for sharing the Spotify Clone project. For full documentation, see README.md*
