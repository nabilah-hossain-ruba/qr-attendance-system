# BRACU QR Attendance System - Setup Guide

## 📋 System Overview

The BRACU QR Attendance System is a comprehensive web application built with Laravel 10 and PHP 8.1, designed specifically for Brac University's attendance tracking needs.

### ✅ Implemented Features

1. **User Authentication & Authorization**
   - BRACU email validation (@bracu.ac.bd)
   - Role-based access (Admin, Lecturer, Student)
   - Secure password hashing (bcrypt)

2. **Dashboard System**
   - Admin Dashboard: System statistics and management
   - Lecturer Dashboard: Course management and QR generation
   - Student Dashboard: Attendance history and QR scanning

3. **Course Management**
   - Course creation and editing
   - Student enrollment system
   - Lecturer assignment

4. **Attendance Tracking**
   - QR code generation with expiry
   - QR code scanning (web-based)
   - Duplicate attendance prevention
   - Session-wise tracking

5. **Reporting System**
   - CSV export functionality
   - Attendance statistics

## 🚀 Getting Started

### Prerequisites

- PHP 8.1 or higher
- Composer
- MySQL database
- Node.js and NPM
- XAMPP (for local development)

### Installation Steps

1. **Navigate to Project Directory**
   ```bash
   cd C:\QR_Attendance
   ```

2. **Install PHP Dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript Dependencies**
   ```bash
   npm install && npm run build
   ```

4. **Environment Configuration**
   - The `.env` file is already configured
   - Database: `qr_attendance`
   - Make sure MySQL is running in XAMPP

5. **Database Setup**
   ```bash
   php artisan migrate
   php artisan db:seed --class=DemoDataSeeder
   ```

6. **Start the Application**
   ```bash
   php artisan serve
   ```
   Access at: http://localhost:8000

## 👥 Demo User Accounts

### Admin Account
- **Email:** admin@g.bracu.ac.bd
- **Password:** password (set `SEED_ADMIN_PASSWORD` to change)
- **Role:** Admin (Full system access)

### Lecturer Account
- **Email:** lecturer.demo@g.bracu.ac.bd
- **Password:** password
- **Role:** Lecturer (Course management)

### Student Account
- **Email:** student.demo@g.bracu.ac.bd  
- **Password:** password
- **Role:** Student (Attendance marking)

## 📱 Using the System

### For Students
1. Login to student dashboard
2. View enrolled courses
3. Scan QR codes to mark attendance
4. View attendance history

### For Lecturers
1. Login to lecturer dashboard
2. Generate QR codes for sessions
3. View course attendance
4. Export attendance reports

### For Admins
1. Login to admin dashboard
2. View system statistics
3. Manage users and courses
4. Generate comprehensive reports

## 🔧 System Testing

Run the system tests:
```bash
php artisan system:test
php artisan dashboards:test
```

## 📊 Database Structure

- **users**: User accounts with roles
- **courses**: Course information
- **sessions**: Class sessions with QR codes
- **attendances**: Attendance records
- **course_student**: Student enrollments

## 🛠️ Technical Stack

- **Backend:** Laravel 10, PHP 8.1
- **Database:** MySQL
- **Frontend:** Blade Templates, Tailwind CSS
- **QR Codes:** SimpleSoftwareIO QR Code package
- **Reports:** CSV export functionality
- **Authentication:** Laravel Breeze

## ⚠️ Important Notes

1. **GD Extension**: The system requires PHP GD extension for QR code generation. If you encounter issues, ensure GD is enabled in your php.ini file.

2. **Email Configuration**: The system is configured to use BRACU email (your.email@g.bracu.ac.bd) for notifications and email verification. See the Email Setup section below for configuration details.

3. **QR Code Testing**: Use the test QR code "TEST_QR_CODE_123" in the manual entry field for testing.

## 🔍 Testing the System

### Quick Test Workflow

1. **Admin Test**
   - Login as admin
   - Check system statistics
   - Verify all counts are correct

2. **Lecturer Test**
   - Login as lecturer
   - Generate QR code for a course
   - View session details

3. **Student Test**
   - Login as student
   - Use QR scanner or manual entry
   - Verify attendance is marked

## 📈 System Status

✅ **Working Features:**
- User authentication with BRACU emails
- Role-based dashboards
- QR code generation and scanning
- Attendance tracking
- Course management
- CSV exports

⚠️ **Requires PHP GD Extension:**
- QR code image generation
- Excel reports

## 🆘 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Ensure MySQL is running in XAMPP
   - Check database name in `.env` file

2. **QR Code Generation Error**
   - Enable GD extension in PHP
   - Restart web server after PHP changes

3. **Route Not Found**
   - Clear route cache: `php artisan route:clear`
   - Clear config cache: `php artisan config:clear`

## 📧 Email Setup & Verification

### Email Configuration

The system now includes email verification for new user registrations. To configure email sending:

1. **Update .env file** with the following email settings:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your.email@g.bracu.ac.bd
   MAIL_PASSWORD=your-app-password-here
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=your.email@g.bracu.ac.bd
   MAIL_FROM_NAME="QR Attendance System"
   ```

2. **Gmail App Password Setup**:
   - Enable 2-Factor Authentication on the Gmail account
   - Generate an App Password for the application
   - Use the App Password in `MAIL_PASSWORD` (not your regular password)

3. **Test Email Verification**:
   ```bash
   php artisan test:email-verification test@example.com
   ```

### Email Verification Features

- ✅ Custom email verification notifications
- ✅ Professional email templates with BRACU branding
- ✅ Secure verification links with expiration
- ✅ Automatic redirect to verification page after registration
- ✅ Resend verification email functionality

## 📞 Support

For technical support or questions about the BRACU QR Attendance System, please contact the development team.

---

**Version:** 1.1  
**Last Updated:** {{ date('Y-m-d') }}  
**Laravel Version:** 10.x  
**PHP Version:** 8.1+
