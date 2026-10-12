# 📲 QR Attendance System

![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-10-FF2D20?logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?logo=bootstrap&logoColor=white)

A full-stack attendance platform for BRAC University. A lecturer opens a class session and shows a QR code; students scan it with their phone camera. Attendance only counts if the student is **enrolled**, the code is **still valid**, the student is **physically in the room**, and they're using **their own registered device**. These checks shut down the usual ways of cheating, like passing the code around or signing in for a friend.

## ✨ Features

**For lecturers**
- One click starts a session and generates a QR code. The lecturer's GPS position becomes the classroom location.
- Live session page shows who has checked in and when. Each record also stores the distance from the lecturer and the device used.
- Close sessions early and **export attendance as CSV**.
- Manage assigned courses and enrol students.

**For students**
- Scan the QR code in the browser with the phone camera (`html5-qrcode`). No app install needed.
- Dashboard showing enrolled courses and **attendance history**.
- Self-service course enrolment.

**For admins**
- Manage users, courses and sessions, and reassign courses between lecturers.
- **Reset device bindings** when a student changes phones.
- `php artisan bracu:sync-schedule` imports sections, class times and lecturers from the BRACU Connect schedule feed and matches lecturers by faculty initials.

## 🛡️ Anti-Proxy Checks

When a student scans, `POST /attendance/mark` runs these checks in order:

| # | Check | How it works |
|---|---|---|
| 1 | **Valid, unexpired code** | Each session gets a random token (`random_bytes`) that expires after `ATTENDANCE_QR_VALID_MINUTES` (default 10 minutes) or when the lecturer closes the session. |
| 2 | **Enrolment** | Only students enrolled in that course section are accepted. |
| 3 | **Device binding** | A long-lived encrypted cookie ties one student to one device and one device to one student. The first bind is an atomic conditional `UPDATE`, so two concurrent requests can't both claim the same device. |
| 4 | **Geofence** | The student's GPS reading must be within `ATTENDANCE_RADIUS_METERS` (default 100 m) of the lecturer's position. Readings with poor accuracy (`> ATTENDANCE_MAX_ACCURACY_METERS`) are rejected, so a vague location can't slip through. |
| 5 | **One mark per session** | A unique `(session_id, user_id)` index enforces this at the database level. |
| 6 | **Rate limiting** | `throttle:10,1` limits each user to 10 marking attempts per minute. |

Other safeguards: role-based route middleware (`role:admin|lecturer|student`), only `@bracu.ac.bd` emails can register, CSRF protection on every form, and lecturers can only see their own sessions.

## 🏗️ Architecture

```
Lecturer browser ──(GPS + course)──► LecturerController@generateQrCode ──► sessions table
       │                                                                     (token, expiry, lat/lng, radius)
       └── shows QR (qrcode.js) ◄────────────────────────────────────────────────┘
                                                                                 ▲
Student phone ──(scan + GPS + device cookie)──► AttendanceController@markAttendance
                                                  ├─ token valid & unexpired?
                                                  ├─ enrolled?
                                                  ├─ device bound to this student?
                                                  ├─ within radius & accurate enough?
                                                  └─ insert attendance (unique per session)
```

**Data model:** `users` (role, student ID, bound device) · `courses` (BRACU section, schedule, lecturer) · `course_student` · `sessions` · `attendances` (distance, device, timestamp)

## 🚀 Getting Started

**Requirements:** PHP 8.1+, Composer, Node 18+, MySQL 8

```bash
git clone https://github.com/nabilah-hossain-ruba/qr-attendance-system.git
cd qr-attendance-system

composer install
npm install && npm run build

cp .env.example .env          # set DB_* (and MAIL_* for password reset emails)
php artisan key:generate
php artisan migrate --seed    # creates demo accounts and courses
php artisan serve
```

Then open `http://localhost:8000`.

| Role | Email | Password |
|---|---|---|
| Admin | `admin@g.bracu.ac.bd` | `password` |
| Lecturer | `lecturer.demo@g.bracu.ac.bd` | `password` |
| Student | `student.demo@g.bracu.ac.bd` | `password` |

> Browsers only allow camera and location access over **HTTPS** (or `localhost`). To test from a phone, expose the dev server through an HTTPS tunnel such as `ngrok` or `cloudflared`.

To import the real BRACU schedule:

```bash
php artisan bracu:sync-schedule
```

See [`docs/SETUP_GUIDE.md`](docs/SETUP_GUIDE.md) and [`docs/EMAIL_SETUP.md`](docs/EMAIL_SETUP.md) for more detail.

## ⚙️ Configuration

| Variable | Default | Purpose |
|---|---|---|
| `ATTENDANCE_QR_VALID_MINUTES` | `10` | How long a QR code can be scanned |
| `ATTENDANCE_RADIUS_METERS` | `100` | Maximum distance from the lecturer |
| `ATTENDANCE_MAX_ACCURACY_METERS` | `150` | Reject GPS readings less accurate than this |
| `SEED_ADMIN_EMAIL` / `SEED_ADMIN_PASSWORD` | demo values | Admin account created by the seeder |

## 🗂️ Project Structure

```
app/
├── Console/Commands/SyncBracuSchedule.php   # bracu:sync-schedule
├── Http/Controllers/                        # Admin, Lecturer, Student, Attendance, Auth
├── Http/Middleware/CheckRole.php            # role-based access
├── Models/                                  # User, Course, Session, Attendance, Enrollment
├── Rules/BracuEmail.php                     # @bracu.ac.bd registration rule
└── Services/BracuScheduleSync.php           # schedule feed → courses + lecturers
config/attendance.php                        # QR validity, geofence, device-cookie settings
database/migrations/                         # schema incl. unique and device-binding constraints
resources/views/{admin,lecturer,student}/    # Blade UIs
legacy/                                      # pre-Laravel prototype (unused)
```

## 🧰 Tech Stack

Laravel 10 · PHP 8.1 · MySQL · Blade + Bootstrap 5 · Vite · html5-qrcode · qrcode.js · Laravel Sanctum · Guzzle (schedule sync)
