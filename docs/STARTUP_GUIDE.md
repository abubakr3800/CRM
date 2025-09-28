# 🚀 ShortCircuit CRM - Startup Guide

## 📋 Quick Start Checklist

### ✅ Prerequisites
- [ ] XAMPP installed and running
- [ ] Apache and MySQL services started
- [ ] Web browser ready
- [ ] CRM files extracted to htdocs

---

## 🎯 Step-by-Step Startup Process

### **STEP 1: Start XAMPP Services**

1. **Open XAMPP Control Panel**
   - Launch XAMPP from your desktop or start menu
   - You should see the control panel with Apache and MySQL

2. **Start Required Services**
   - Click **"Start"** next to **Apache**
   - Click **"Start"** next to **MySQL**
   - Both should show green "Running" status

3. **Verify Services**
   - Apache: Should show port 80 or 8080
   - MySQL: Should show port 3306

### **STEP 2: Access Your CRM**

1. **Open Web Browser**
   - Use Chrome, Firefox, Edge, or any modern browser

2. **Navigate to Your CRM**
   - Go to: `http://localhost/crm/`
   - Or: `http://localhost:8080/crm/` (if using port 8080)

3. **First Time Setup**
   - You should see the installation page
   - If not, go directly to: `http://localhost/crm/install.php`

### **STEP 3: Install the Database**

1. **Run Installation**
   - Click **"Install CRM System"** button
   - Wait for installation to complete
   - You should see "CRM System installed successfully!"

2. **Verify Installation**
   - Check that you can access: `http://localhost/crm/login.php`
   - You should see the login page

### **STEP 4: First Login**

1. **Default Credentials**
   - **Username:** `admin`
   - **Password:** `password`

2. **Login Process**
   - Enter credentials and click "Sign In"
   - You should be redirected to the dashboard

3. **Change Password (IMPORTANT!)**
   - Click on your username in the top-right corner
   - Select "Profile"
   - Change the default password immediately

### **STEP 5: Initial Configuration**

1. **Create Additional Users (Optional)**
   - Go to Admin Panel (if you're admin)
   - Add more users as needed

2. **Configure System Settings**
   - Check system settings in Admin Panel
   - Verify backup settings

3. **Test Basic Functions**
   - Create a test account
   - Add a contact
   - Create a project
   - Add a task

---

## 🔧 Troubleshooting Common Issues

### **Issue 1: "Database Connection Failed"**

**Symptoms:**
- Error message about database connection
- Cannot access login page

**Solutions:**
1. **Check MySQL is Running**
   - XAMPP Control Panel → MySQL should be "Running"
   - If not, click "Start"

2. **Check Database Configuration**
   - Open `config/database.php`
   - Verify these settings:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'shortcircuit_crm');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

3. **Check Database Exists**
   - Go to `http://localhost/phpmyadmin`
   - Look for `shortcircuit_crm` database
   - If missing, run installation again

### **Issue 2: "Page Not Found" or 404 Error**

**Symptoms:**
- Browser shows "Page Not Found"
- Cannot access CRM pages

**Solutions:**
1. **Check File Location**
   - Ensure files are in `C:\xampp\htdocs\crm\`
   - Verify all files are present

2. **Check Apache is Running**
   - XAMPP Control Panel → Apache should be "Running"
   - If not, click "Start"

3. **Check URL**
   - Use: `http://localhost/crm/`
   - Not: `http://localhost/crm/index.php`

### **Issue 3: "Permission Denied" Errors**

**Symptoms:**
- Cannot create backups
- Cannot write to logs
- File upload errors

**Solutions:**
1. **Check Directory Permissions**
   - Ensure `logs/`, `backups/`, `uploads/` folders exist
   - Make sure they're writable

2. **Create Missing Directories**
   ```bash
   mkdir logs
   mkdir backups
   mkdir uploads
   ```

### **Issue 4: "Login Failed"**

**Symptoms:**
- Cannot login with admin/password
- "Invalid username or password" error

**Solutions:**
1. **Check Database Installation**
   - Run installation again: `http://localhost/crm/install.php`
   - Verify admin user was created

2. **Check Database**
   - Go to phpMyAdmin
   - Check `users` table has admin user
   - Verify password hash

3. **Reset Password (if needed)**
   - In phpMyAdmin, update users table:
   ```sql
   UPDATE users SET password = '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlY7B77UdFm' WHERE username = 'admin';
   ```

---

## 🎯 Quick Access URLs

| Page | URL |
|------|-----|
| **Homepage/Dashboard** | `http://localhost/crm/` |
| **Login** | `http://localhost/crm/login.php` |
| **Installation** | `http://localhost/crm/install.php` |
| **Accounts** | `http://localhost/crm/accounts.php` |
| **Projects** | `http://localhost/crm/projects.php` |
| **Tasks** | `http://localhost/crm/tasks.php` |
| **Price Offers** | `http://localhost/crm/price_offers.php` |
| **Contacts** | `http://localhost/crm/contacts.php` |
| **Admin Panel** | `http://localhost/crm/admin.php` |
| **Profile** | `http://localhost/crm/profile.php` |

---

## 📱 First Time User Experience

### **What You'll See After Login:**

1. **Dashboard**
   - Welcome message with your name
   - Statistics cards (all showing 0 initially)
   - Recent activity (empty initially)
   - Notifications section

2. **Navigation Bar**
   - Home, Accounts, Projects, Tasks, Price Offers, Contacts
   - Your username dropdown with Profile and Logout options

3. **Empty System**
   - No accounts, projects, or tasks yet
   - Ready for you to start adding data

### **Recommended First Steps:**

1. **Create Your First Account**
   - Go to Accounts → Create Account
   - Fill in company information
   - Create an initial project

2. **Add Contacts**
   - Go to Contacts → Create Contact
   - Link contacts to your account

3. **Create Tasks**
   - Go to Tasks → Create Task
   - Assign tasks to users

4. **Test Price Offers**
   - Go to Price Offers → Create Price Offer
   - Add items and test calculations

---

## 🔒 Security Checklist

### **Immediate Actions:**
- [ ] Change default admin password
- [ ] Create additional users if needed
- [ ] Set up proper file permissions
- [ ] Configure backup schedule

### **Production Considerations:**
- [ ] Use HTTPS in production
- [ ] Set up proper database user (not root)
- [ ] Configure firewall rules
- [ ] Regular security updates

---

## 📞 Getting Help

### **If You Need Support:**

1. **Check Logs**
   - Look in `logs/` directory for error messages
   - Check browser console for JavaScript errors

2. **Verify Installation**
   - Ensure all files are present
   - Check database tables exist

3. **Common Solutions**
   - Restart XAMPP services
   - Clear browser cache
   - Check file permissions

### **System Status Check:**
- **Apache Running:** ✅/❌
- **MySQL Running:** ✅/❌
- **Database Exists:** ✅/❌
- **Files Present:** ✅/❌
- **Can Login:** ✅/❌

---

## 🎉 Success Indicators

### **You're Ready When:**
- [ ] Can access `http://localhost/crm/`
- [ ] Can login with admin/password
- [ ] See the dashboard with statistics
- [ ] Can navigate between modules
- [ ] Can create accounts, projects, tasks
- [ ] All tables show data correctly

### **Next Steps:**
- [ ] Add your real data
- [ ] Configure users and permissions
- [ ] Set up automated backups
- [ ] Customize system settings

---

**🎯 You're now ready to start using your CRM system!**

*For detailed system documentation, see SYSTEM_DOCUMENTATION.md*
*For installation issues, see INSTALLATION.md*
