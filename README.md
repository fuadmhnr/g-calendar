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

## Workflow Aplikasi

### 🎯 **Overview Sistem**

Aplikasi ini memiliki dua tipe user dengan workflow yang berbeda:
- **Guest Users**: Dapat melihat events dan request bergabung tanpa login
- **Admin Users**: Mengelola events dan approve join requests dengan OAuth authentication

### 👤 **Guest User Workflow**

#### 1. **Melihat Events**
```
1. Kunjungi homepage (/) 
   → Otomatis redirect ke /events
2. Sistem load events via service account (read-only)
3. Guest melihat list events dengan detail:
   - Nama event & deskripsi
   - Tanggal & waktu mulai/selesai
   - Tombol "Lihat Detail"
```

#### 2. **Join Event Process**
```
1. Guest klik "Lihat Detail" pada event
2. Melihat detail lengkap event:
   - Informasi waktu
   - Peserta yang sudah terdaftar (jika ada)
   - Google Meet link (jika ada)
3. Isi form join request:
   - Email address (required)
   - Pesan untuk admin (optional)
4. Submit → Request tersimpan di database
5. Melihat konfirmasi: "Permintaan bergabung telah dikirim!"
```

#### 3. **Status Tracking**
```
- Jika submit request yang sama lagi:
  • Pending: "Anda sudah memiliki permintaan yang menunggu persetujuan"
  • Approved: "Anda sudah bergabung dengan event ini!"
```

### 👨‍💼 **Admin User Workflow**

#### 1. **Login Process**
```
1. Kunjungi homepage → Klik "Login Admin"
2. Redirect ke Google OAuth consent screen
3. Login dengan Google account
4. Grant calendar permissions
5. Redirect kembali ke /calendar (admin dashboard)
```

#### 2. **Manage Events**
```
Admin Dashboard (/calendar):
├── View all events
├── Create new event
├── Edit existing event  
├── Delete event
└── View join requests (dengan badge counter)
```

#### 3. **Join Request Management**
```
1. Klik "Permintaan Bergabung" (dengan notif badge jika ada pending)
2. Melihat requests digroup per event:
   - Email requester
   - Pesan dari user
   - Tanggal request
3. Actions per request:
   ├── APPROVE: 
   │   ├── Add attendee ke Google Calendar via OAuth
   │   ├── Send email invitation otomatis
   │   ├── Update status di database → "approved"
   │   └── Track admin yang approve
   └── REJECT:
       ├── Update status di database → "rejected"  
       └── Track admin yang reject
```

### 🔄 **Complete User Journey Example**

#### **Scenario: Marketing Event Join Process**

**Guest Side:**
```
1. Sarah mengunjungi website
   → Melihat "Marketing Workshop - 25 Juli 2025"
2. Klik "Lihat Detail" 
   → Melihat workshop 09:00-17:00, Google Meet included
3. Isi email: sarah@company.com
   Pesan: "Saya tertarik untuk belajar digital marketing"
4. Submit → "Permintaan bergabung telah dikirim!"
```

**Admin Side:**
```
1. Admin login → Dashboard shows "Permintaan Bergabung (1)"
2. Klik badge → Melihat request dari Sarah
3. Review: sarah@company.com + pesan tentang digital marketing
4. Klik "Setujui" → Konfirmasi dialog
5. Sistem:
   ├── Add Sarah sebagai attendee di Google Calendar
   ├── Google otomatis send invitation email ke Sarah
   ├── Update database: status = "approved", approved_by = admin@company.com
   └── Show: "Permintaan bergabung telah disetujui!"
```

**Result:**
```
✅ Sarah mendapat email dari Google Calendar
✅ Sarah bisa join Google Meet
✅ Sarah muncul di attendee list
✅ Admin track siapa yang approve & kapan
```

### 🔐 **Authentication Flow Detail**

#### **Guest Access (No Login Required)**
```
Guest Request → Service Account → Google Calendar API (Read-Only)
├── IF service account available: Direct API call
├── IF service account not available: Fallback to OAuth token  
└── IF no auth available: Empty collection (graceful degradation)
```

#### **Admin Access (OAuth Required)**
```
Admin Action → OAuth Token → Google Calendar API (Full Access)
├── Create/Edit/Delete events
├── Add/Remove attendees  
├── Send email invitations
└── Full calendar management
```

### 🚨 **Error Handling Scenarios**

#### **Common Issues & Solutions:**
```
1. Guest tidak bisa lihat events:
   → Check: service account credentials & calendar sharing
   
2. Admin tidak bisa approve requests:
   → Check: OAuth token valid & calendar permissions
   
3. Email invitations tidak terkirim:
   → Check: sendUpdates parameter & attendee email valid
   
4. Duplicate join requests:
   → Sistem otomatis detect & prevent dengan unique constraint
```

### 📊 **Database State Changes**

```
JOIN REQUEST LIFECYCLE:

Initial State:
join_requests table empty

Guest Submit:
├── INSERT: event_id, email, status='pending', message, created_at
└── CONSTRAINT: unique(event_id, email) prevents duplicates

Admin Approve:
├── UPDATE: status='approved', approved_at=now(), approved_by=admin_email
└── EXTERNAL: Add attendee to Google Calendar

Admin Reject:
├── UPDATE: status='rejected', approved_by=admin_email  
└── NO EXTERNAL ACTION
```

### 🎯 **Key Success Metrics**

- ✅ Guest dapat lihat events tanpa barrier
- ✅ Join requests tracked di database
- ✅ Admin approval mengirim real invitations
- ✅ Zero manual email sending required
- ✅ Automatic duplicate prevention
- ✅ Full audit trail untuk approval actions
