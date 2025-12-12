# Database Setup Instructions

## For All Team Members

### Step 1: Install MySQL
- **Mac:** Install MAMP from https://www.mamp.info
- **Windows:** Install XAMPP from https://www.apachefriends.org

### Step 2: Start MySQL Server
- **MAMP:** Click "Start Servers"
- **XAMPP:** Start MySQL module

### Step 3: Create Database
1. Open phpMyAdmin (http://localhost:8888/phpMyAdmin/ for MAMP)
2. Click "New" → Create database: `team14_makeitall_database`
3. Set collation: `utf8mb4_unicode_ci`

### Step 4: Import Schema
1. Click on `team14_makeitall_database` database
2. Click "Import" tab
3. Choose file: `database/schema.sql`
4. Click "Go"

You should see 12 tables created.

### Step 5: Configure Database Connection
1. Copy `config/database.example.php` to `config/database.php`
2. Update credentials if needed (default: root/root)
3. **Never commit `config/database.php` to GitHub!**

### Step 6: Test Connection
Open `http://localhost:8888/test-db.php` in your browser.
You should see: ✅ Database connected!

### Troubleshooting
- **Port conflict:** Change MAMP port to 8889 in preferences
- **Can't connect:** Check MySQL is running in MAMP/XAMPP
- **Wrong password:** Update in `config/database.php`
- **Requested Not Found:** 
-- 1. Open **MAMP**
-- 2. **Preferences** → **Web Server** tab
-- 3. Click **"Choose..."** next to Document Root
-- 4. Navigate to your GitHub project folder
-- 5. Click **"Select"**
-- 6. Click **"OK"**
-- 7. **Restart servers** (Stop → Start)

Questions? Ask Simi in team channel!