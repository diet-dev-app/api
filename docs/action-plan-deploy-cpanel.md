# Action Plan: Deploying Symfony Project to cPanel Hosting

## Overview
This document provides a step-by-step guide to deploy the Symfony-based API project to a web hosting server with cPanel. The plan assumes you have access to cPanel, FTP/SFTP, and a MySQL database.

---

## 1. Preparation

1. **Check Hosting Requirements:**
   - Ensure your hosting supports PHP 8.1+ and Composer.
   - Confirm SSH access (recommended) or FTP/SFTP access.
   - Confirm MySQL database availability.

2. **Export Environment Variables:**
   - Prepare your `.env` or `.env.prod` file with production values (DB credentials, JWT secret, etc.).

3. **Build the Project Locally:**
   - Run `composer install --no-dev --optimize-autoloader` locally.
   - Run `php bin/console cache:clear --env=prod`.
   - Run `php bin/console doctrine:migrations:migrate --no-interaction` (if DB is accessible remotely).

---

## 2. Upload Files to Server

1. **Connect to Server:**
   - Use FTP/SFTP or cPanel File Manager.

2. **Upload Files:**
   - Upload all project files except `/var`, `/vendor` (if you will run Composer on server), and local config files.
   - Place files in a subfolder (e.g., `api/`) if needed.

3. **Set Document Root:**
   - In cPanel, set the domain/subdomain document root to the `public/` folder inside your project (e.g., `/home/username/api/public`).

---

## 3. Server Configuration

1. **Composer Install (on server):**
   - If SSH is available, run `composer install --no-dev --optimize-autoloader` in the project root.
   - If not, upload the `/vendor` folder from your local machine.

2. **Environment Variables:**
   - Upload your `.env.prod` or set environment variables via cPanel > Advanced > Cron Jobs > Environment Variables.

3. **Permissions:**
   - Set correct permissions for `var/` and `public/` folders:
     - `chmod -R 755 var public`
     - `chown -R user:user var public` (if possible)

4. **Database Setup:**
   - Create a MySQL database and user in cPanel.
   - Update `.env` with DB credentials.
   - Run migrations:
     - If SSH: `php bin/console doctrine:migrations:migrate --no-interaction --env=prod`
     - If not, import SQL manually via phpMyAdmin.

---

## 4. Final Steps

1. **Clear and Warmup Cache:**
   - `php bin/console cache:clear --env=prod`
   - `php bin/console cache:warmup --env=prod`

2. **Test the Application:**
   - Access your domain/subdomain and verify the API is working.
   - Check logs in `var/log/` for errors.

3. **Security:**
   - Remove or protect `.env` files from public access.
   - Ensure `public/` is the only web-accessible directory.

---

## 5. Troubleshooting

- **500 Internal Server Error:**
  - Check file permissions and PHP version.
  - Review `var/log/prod.log` for details.
- **Database Connection Issues:**
  - Verify DB credentials and host in `.env`.
- **Missing Vendor Libraries:**
  - Ensure `composer install` ran successfully or upload `/vendor`.

---

## References
- Symfony Deployment: https://symfony.com/doc/current/deployment.html
- cPanel Documentation: https://docs.cpanel.net/

---

**Author:** GitHub Copilot
**Date:** 2026-02-15
