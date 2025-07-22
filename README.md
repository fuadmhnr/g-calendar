# Google Calendar Integration dengan Guest Access

Aplikasi ini mendemonstrasikan integrasi Google Calendar dengan Laravel menggunakan package spatie/laravel-google-calendar dengan fitur guest access dan OAuth authentication untuk admin.

## Fitur

- **Guest Access**: Users dapat melihat events tanpa login
- **Join Request System**: Guest dapat request bergabung ke event
- **Admin OAuth**: Admin login dengan Google OAuth untuk manage events dan approve join requests
- List, create, edit, dan delete calendar events (admin only)

## Persyaratan

- PHP 8.2+
- Laravel 12.0+
- Google Cloud Platform account dengan Calendar API enabled

## Instalasi

1. Clone repository

```bash
git clone <repository-url>
cd g-calendar
```

2. Install dependencies

```bash
composer install
```

3. Copy environment file dan generate application key

```bash
cp .env.example .env
php artisan key:generate
```

4. Configure database

```bash
touch database/database.sqlite
```

5. Run migrations

```bash
php artisan migrate
```

## Setup Google Calendar API

### Opsi 1: Service Account (Recommended untuk Guest Access)

**Langkah untuk membuat Service Account:**

1. Buka [Google Cloud Console](https://console.cloud.google.com/)
2. Buat project baru atau pilih existing project
3. Enable Google Calendar API
4. Buka "Credentials" → "Create Credentials" → "Service Account"
5. Isi nama service account dan create
6. Di halaman service account, klik "Keys" → "Add Key" → "Create New Key" → "JSON"
7. Download file JSON credentials
8. Rename file menjadi `service-account-credentials.json`
9. Copy ke `storage/app/google-calendar/service-account-credentials.json`

**Berikan akses calendar ke service account:**

1. Buka Google Calendar di browser
2. Di sidebar kiri, klik calendar yang ingin dishare
3. Klik "Settings and sharing"
4. Di "Share with specific people", klik "Add people"
5. Masukkan email service account (format: `nama@project-id.iam.gserviceaccount.com`)
6. Set permission ke "Make changes to events" 
7. Klik "Send"

### Opsi 2: OAuth Credentials (Untuk Admin Access)

1. Di Google Cloud Console, buat OAuth 2.0 credentials (Web application type)
2. Tambahkan authorized redirect URIs: `http://localhost:8000/google/callback`
3. Download credentials JSON file
4. Copy ke `storage/app/google-calendar/oauth-credentials.json`

## Konfigurasi Environment

Update file `.env`:

```env
GOOGLE_CALENDAR_AUTH_PROFILE=service_account
GOOGLE_CALENDAR_ID=your-calendar-id@gmail.com
```

**Cara mendapatkan Calendar ID:**
1. Buka Google Calendar
2. Klik settings calendar yang ingin digunakan
3. Scroll ke "Calendar ID" dan copy

## Struktur Akses

### Guest Users (Tanpa Login)
- 🔍 Lihat semua events di `/events`
- 📝 Request bergabung ke event dengan email
- ✅ Otomatis redirect ke events dari homepage

### Admin Users (Dengan OAuth Login)
- 🔐 Login via `/google/redirect`
- 📅 Manage events di `/calendar`
- ✅ Approve/reject join requests di `/calendar/join-requests`
- ➕ Create, edit, delete events

## Cara Penggunaan

1. **Start server**

```bash
php artisan serve
```

2. **Guest access**: Langsung kunjungi `http://localhost:8000` - akan redirect ke events
3. **Admin login**: Klik "Login Admin" untuk authentication
4. **Join events**: Guest click event → isi email → admin approve di dashboard

## Troubleshooting

### Guest tidak bisa lihat events

**Penyebab**: Service account belum disetup atau tidak punya akses ke calendar

**Solusi**:
1. Pastikan file `storage/app/google-calendar/service-account-credentials.json` ada
2. Pastikan service account sudah diberi akses ke Google Calendar
3. Pastikan `GOOGLE_CALENDAR_ID` di `.env` benar

**Alternatif fallback**: Jika service account tidak tersedia, sistem akan coba gunakan OAuth token (jika admin sudah pernah login)

### Error "Calendar ID not configured"

Update `GOOGLE_CALENDAR_ID` di `.env` file dengan calendar ID yang benar.

### Permission denied untuk service account

Service account harus diberi explicit access ke Google Calendar melalui sharing settings.

## Arsitektur Sistem

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Guest Users   │    │   Admin Users    │    │ Google Calendar │
│                 │    │                  │    │                 │
│ View Events     │◄───┤ OAuth Login      │◄───┤ OAuth API       │
│ Join Requests   │    │ Manage Events    │    │                 │
│                 │    │ Approve Requests │    │                 │
└─────────────────┘    └──────────────────┘    └─────────────────┘
          │                        │                        ▲
          │                        │                        │
          ▼                        ▼                        │
┌─────────────────────────────────────────────────────────┘
│              Laravel Application
│
│  ┌─────────────────┐  ┌──────────────────┐
│  │ Service Account │  │ OAuth Credentials│
│  │ (Guest Access)  │  │ (Admin Access)   │
│  └─────────────────┘  └──────────────────┘
└─────────────────────────────────────────────────────────
```

## Keamanan

- Guest access adalah **read-only** via service account
- Admin access memerlukan **OAuth authentication**
- Join requests tersimpan di database dan memerlukan **approval admin**
- Service account hanya punya permission sesuai yang diberikan di Google Calendar

## Flow Lengkap

1. **Guest** lihat events (via service account atau fallback OAuth)
2. **Guest** kirim join request (tersimpan di database)
3. **Admin** login dan lihat pending requests
4. **Admin** approve request (menambahkan attendee ke Google Calendar via OAuth)
5. **Guest** mendapat email invitation dari Google Calendar
