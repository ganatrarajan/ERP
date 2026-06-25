# EduvoraX School ERP - Backend & API

This repository contains the backend engine for the EduvoraX School ERP system and the mobile application API. It is built using Laravel 11 and Vue 3 (Inertia.js).

Follow these instructions to set up and run this project on a new device.

---

## 🛠️ System Requirements & Versions

To successfully build and run this application on a new device or environment, ensure you have the following installed:

### **1. PHP & Web Server**
- **XAMPP**: Version 8.2 (Includes PHP 8.2 and MySQL)
- **PHP**: `^8.2` (Strictly required for Laravel 11)
- **Extensions Needed**: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, cURL, GD, Zip. (All are usually included and enabled in XAMPP 8.2 by default).

### **2. Package Managers**
- **Composer**: `v2.x` (For PHP packages)
- **Node.js**: `v18.x` or `v20.x` (LTS recommended)
- **npm**: `v9.x` or higher (For compiling Vue/Tailwind assets)

### **3. Database**
- **MySQL**: `v8.0+` or **MariaDB**: `v10.4+` (Included in XAMPP)
- *Note: You can also use SQLite for local rapid development.*

---

## 🚀 How to Setup and Run on a New Device

Follow these step-by-step instructions to clone and run the app fresh on a new computer:

### Step 1: Clone or Copy the Repository
Place the `ERP` folder inside your XAMPP `htdocs` directory (e.g., `D:\xampp8.2\htdocs\ERP` or `C:\xampp\htdocs\ERP`).

### Step 2: Install PHP Dependencies
Open your terminal inside the `ERP` directory and run:
```bash
composer install
```

### Step 3: Environment Setup
1. Duplicate the `.env.example` file and rename it to `.env`.
2. Generate the application encryption key:
```bash
php artisan key:generate
```
3. Open the `.env` file and configure your database settings. For XAMPP, it typically looks like this:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_erp
DB_USERNAME=root
DB_PASSWORD=
```
*(Make sure to create an empty database named `school_erp` in phpMyAdmin first).*

### Step 4: Run Migrations and Seeders
Run the following command to create all tables and populate the system with default roles, permissions, modules, and a super admin user:
```bash
php artisan migrate:fresh --seed
```

### Step 5: Install Node Dependencies & Compile Frontend
To build the Vue 3 and TailwindCSS assets, run:
```bash
npm install
npm run build
```
*(For active frontend development, you can leave `npm run dev` running in a separate terminal window).*

### Step 6: Link Storage
To ensure uploaded files (like logos, attachments, report cards) are publicly accessible:
```bash
php artisan storage:link
```

### Step 7: Start the Local Development Server
Since you are connecting a Mobile App (Flutter), you must serve the backend on your **local IP address** so your phone or emulator can reach it.

Find your IP address (e.g., `192.168.0.173`) and run:
```bash
php artisan serve --host=192.168.0.173 --port=8000
```
*(Replace `192.168.0.173` with your actual machine's IPv4 address).*

---

## 📱 Mobile App Connection

If you are setting up the Flutter Mobile App alongside this backend:
1. Ensure the backend is running via the `artisan serve --host=YOUR_IP` command as shown in Step 7.
2. In the Flutter project, update the API Base URL to match exactly:
```dart
static const String baseUrl = "http://YOUR_IP:8000/api/mobile";
```
3. Make sure both your development computer and your testing mobile device/emulator are connected to the **same Wi-Fi network**.

## 🐛 Troubleshooting

**1. "Target class [Controller] does not exist" or Route Errors:**
Run the following commands to clear cached route and config files:
```bash
php artisan optimize:clear
```

**2. "SQLSTATE[HY000] [1049] Unknown database":**
Ensure XAMPP MySQL is running from the XAMPP Control Panel and you have actually created the database name defined in your `.env` file using phpMyAdmin.

**3. Mobile app cannot connect (Network Error):**
- Verify your Windows/Mac Firewall is not blocking port `8000`.
- Verify your phone/emulator is on the exact same Wi-Fi network as the server.
- Double-check your IP address hasn't changed (IPs can change when reconnecting to Wi-Fi).
