# Google Calendar Integration with Laravel

This project demonstrates how to integrate Google Calendar with Laravel using the spatie/laravel-google-calendar package with OAuth authentication.

## Features

- OAuth authentication with Google
- List calendar events
- Create new events
- Edit existing events
- Delete events

## Requirements

- PHP 8.2+
- Laravel 12.0+
- Google Cloud Platform account with Calendar API enabled

## Installation

1. Clone the repository

```bash
git clone <repository-url>
cd g-calendar
```

2. Install dependencies

```bash
composer install
```

3. Copy the environment file and generate application key

```bash
cp .env.example .env
php artisan key:generate
```

4. Configure the database

```bash
touch database/database.sqlite
```

5. Run migrations

```bash
php artisan migrate
```

## Google Calendar API Setup

1. Go to the [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project
3. Enable the Google Calendar API
4. Create OAuth 2.0 credentials (Web application type)
5. Add authorized redirect URIs: `http://localhost:8000/google/callback`
6. Download the credentials JSON file
7. Create the directory structure and place the credentials file:

```bash
mkdir -p storage/app/google-calendar
cp path/to/downloaded/credentials.json storage/app/google-calendar/oauth-credentials.json
```

8. Update your `.env` file with the following:

```
GOOGLE_CALENDAR_AUTH_PROFILE=oauth
GOOGLE_CALENDAR_ID=your-email@gmail.com
```

## Usage

1. Start the Laravel development server

```bash
php artisan serve
```

2. Visit `http://localhost:8000` in your browser
3. Click on "View Calendar" to authenticate with Google
4. After authentication, you'll be redirected to the calendar page
5. You can now view, create, edit, and delete events

## How It Works

- The application uses OAuth 2.0 to authenticate with Google
- When you first access the calendar, you'll be redirected to Google's authentication page
- After granting permission, you'll be redirected back to the application
- The application stores the OAuth token in `storage/app/google-calendar/oauth-token.json`
- The token is used to make API calls to Google Calendar

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
