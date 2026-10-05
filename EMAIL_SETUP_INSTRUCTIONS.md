# 📧 Email Verification Setup Instructions

## ✅ Current Configuration Status

Your `.env` file has been updated with the following email settings:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=nabilah.hossain@g.bracu.ac.bd
MAIL_PASSWORD=your-app-password-here
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=nabilah.hossain@g.bracu.ac.bd
MAIL_FROM_NAME="QR Attendance System"
```

## 🔧 Required Steps to Complete Setup

### Step 1: Set up Gmail App Password

1. **Enable 2-Factor Authentication** on the Gmail account `nabilah.hossain@g.bracu.ac.bd`
   - Go to [Google Account Security](https://myaccount.google.com/security)
   - Enable 2-Step Verification if not already enabled

2. **Generate App Password**:
   - Go to [App Passwords](https://myaccount.google.com/apppasswords)
   - Select "Mail" as the app
   - Select "Other" as the device and name it "QR Attendance System"
   - Copy the generated 16-character password

3. **Update .env file**:
   - Replace `your-app-password-here` with the actual App Password
   - Example: `MAIL_PASSWORD=abcd efgh ijkl mnop`

### Step 2: Test Email Configuration

Run the test command to verify email sending works:

```bash
php artisan test:email-verification test@example.com
```

**Expected Output:**
```
Testing email verification for: test@example.com
✅ Email verification sent successfully to test@example.com
Check your email inbox for the verification message.
```

### Step 3: Test User Registration

1. **Start the application**:
   ```bash
   php artisan serve
   ```

2. **Register a new user**:
   - Go to http://localhost:8000/register
   - Fill in the registration form with a valid email
   - Submit the form

3. **Check email verification**:
   - You should be redirected to the email verification page
   - Check the email inbox for the verification message
   - Click the verification link to complete registration

## 🚨 Troubleshooting

### Common Issues and Solutions

#### 1. "Authentication failed" error
**Problem**: Gmail rejects the login credentials
**Solution**: 
- Ensure 2FA is enabled on the Gmail account
- Use App Password, not the regular password
- Double-check the App Password is correct

#### 2. "Connection refused" error
**Problem**: Cannot connect to Gmail SMTP server
**Solution**:
- Check internet connection
- Verify firewall settings allow outbound connections on port 587
- Try using port 465 with SSL encryption instead

#### 3. "Invalid recipient" error
**Problem**: Email address format is incorrect
**Solution**:
- Ensure the email follows proper format (user@domain.com)
- Check for typos in the email address

#### 4. Emails not being received
**Problem**: Emails are sent but not delivered
**Solution**:
- Check spam/junk folder
- Verify the recipient email address is correct
- Check if the sending domain has proper SPF/DKIM records

### Alternative Configuration (if Gmail doesn't work)

If Gmail SMTP doesn't work, you can try these alternatives:

#### Option 1: Use Mailtrap (for testing)
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=nabilah.hossain@g.bracu.ac.bd
MAIL_FROM_NAME="QR Attendance System"
```

#### Option 2: Use SendGrid
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=nabilah.hossain@g.bracu.ac.bd
MAIL_FROM_NAME="QR Attendance System"
```

## ✅ Verification Checklist

- [ ] Gmail account has 2FA enabled
- [ ] App Password generated and added to `.env`
- [ ] Test command runs successfully
- [ ] New user registration sends verification email
- [ ] Verification email is received and clickable
- [ ] User can complete email verification process

## 📞 Support

If you encounter any issues with the email setup, please check:

1. **Laravel Logs**: `storage/logs/laravel.log`
2. **Email Test Command**: `php artisan test:email-verification your-email@example.com`
3. **Configuration**: `php artisan config:show mail`

---

**Note**: The email verification system is now fully configured and ready to use. New users will receive verification emails from `nabilah.hossain@g.bracu.ac.bd` once the Gmail App Password is properly set up.
