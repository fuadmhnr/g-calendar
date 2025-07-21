<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\GoogleCalendar\Event;
use Carbon\Carbon;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\Event as GoogleEvent;
use Google\Service\Calendar\Events;
use Google\Client;
use Google\Service\Calendar;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;

class GoogleCalendarController extends Controller
{
    protected $client;
    protected $service;

    public function __construct()
    {
        // Check if we need to authenticate
        if (!$this->isAuthenticated()) {
            // We'll handle this in the middleware
        } else {
            $this->setupGoogleClient();
        }
    }

    /**
     * Check if the user is authenticated with Google
     */
    private function isAuthenticated()
    {
        return file_exists(storage_path('app/google-calendar/oauth-token.json'));
    }

    /**
     * Setup the Google Client
     */
    private function setupGoogleClient()
    {
        $this->client = new Client();
        $this->client->setAuthConfig(storage_path('app/google-calendar/oauth-credentials.json'));
        $this->client->addScope(Calendar::CALENDAR);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        // Load previously authorized token from a file, if it exists.
        $tokenPath = storage_path('app/google-calendar/oauth-token.json');
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $this->client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($this->client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                file_put_contents(
                    storage_path('app/google-calendar/oauth-token.json'),
                    json_encode($this->client->getAccessToken())
                );
            }
        }

        $this->service = new Calendar($this->client);
    }

    /**
     * Redirect to Google OAuth page
     */
    public function redirectToGoogle()
    {
        $client = new Client();
        $client->setAuthConfig(storage_path('app/google-calendar/oauth-credentials.json'));
        $client->addScope(Calendar::CALENDAR);
        $client->setRedirectUri(route('google.callback'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        $authUrl = $client->createAuthUrl();
        return redirect($authUrl);
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request)
    {
        if (!$request->has('code')) {
            return redirect('/')->with('error', 'Authentication failed');
        }

        $client = new Client();
        $client->setAuthConfig(storage_path('app/google-calendar/oauth-credentials.json'));
        $client->addScope(Calendar::CALENDAR);
        $client->addScope('https://www.googleapis.com/auth/userinfo.email');
        $client->setRedirectUri(route('google.callback'));
        $client->setAccessType('offline');

        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($request->code);
        $client->setAccessToken($accessToken);

        // Check to see if there was an error.
        if (array_key_exists('error', $accessToken)) {
            return redirect('/')->with('error', 'Error fetching access token: ' . $accessToken['error']);
        }

        // Save the token to a file.
        if (!file_exists(dirname(storage_path('app/google-calendar/oauth-token.json')))) {
            mkdir(dirname(storage_path('app/google-calendar/oauth-token.json')), 0700, true);
        }
        file_put_contents(
            storage_path('app/google-calendar/oauth-token.json'),
            json_encode($client->getAccessToken())
        );
        
        // Check if this is a guest user trying to join an event
        if (session()->has('join_event_id')) {
            return redirect()->route('guest.add-attendee');
        }

        return redirect('/calendar')->with('success', 'Authentication successful');
    }

    /**
     * List all events
     */
    public function index()
    {
        if (!$this->isAuthenticated()) {
            return redirect()->route('google.redirect');
        }

        try {
            $events = Event::get();
            
            // Process events to ensure date properties are not null
            foreach ($events as $event) {
                if (!$event->startDateTime) {
                    $event->startDateTime = Carbon::now();
                }
                if (!$event->endDateTime) {
                    $event->endDateTime = Carbon::now()->addHour();
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error fetching events: ' . $e->getMessage());
            $events = collect([]);
        }
        
        return view('calendar.index', compact('events'));
    }

    /**
     * Show the form for creating a new event
     */
    public function create()
    {
        if (!$this->isAuthenticated()) {
            return redirect()->route('google.redirect');
        }

        return view('calendar.create');
    }

    /**
     * Store a newly created event
     */
    public function store(Request $request)
    {
        if (!$this->isAuthenticated()) {
            return redirect()->route('google.redirect');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'start_time' => 'required',
            'end_date' => 'required|date|after_or_equal:start_date',
            'end_time' => 'required',
            'attendees' => 'nullable|string',
            'add_meet_link' => 'nullable|boolean',
        ]);

        $startDateTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $request->start_date . ' ' . $request->start_time
        );

        $endDateTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $request->end_date . ' ' . $request->end_time
        );

        $event = new Event;
        $event->name = $request->name;
        $event->description = $request->description;
        $event->startDateTime = $startDateTime;
        $event->endDateTime = $endDateTime;
        
        // Add attendees if provided
        if ($request->filled('attendees')) {
            $attendeeEmails = array_map('trim', explode(',', $request->attendees));
            foreach ($attendeeEmails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $event->addAttendee([
                        'email' => $email,
                    ]);
                }
            }
        }
        
        // Add Google Meet link if requested
        if ($request->has('add_meet_link')) {
            $event->addMeetLink();
        }
        
        $event->save();

        return redirect()->route('calendar.index')
            ->with('success', 'Event created successfully');
    }

    /**
     * Show the form for editing the specified event
     */
    public function edit($eventId)
    {
        if (!$this->isAuthenticated()) {
            return redirect()->route('google.redirect');
        }

        try {
            $event = Event::find($eventId);

            if (!$event) {
                return redirect()->route('calendar.index')
                    ->with('error', 'Event not found');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error finding event: ' . $e->getMessage());
            return redirect()->route('calendar.index')
                ->with('error', 'Event not found or could not be retrieved.');
        }
        
        // Ensure date properties are not null
        if (!$event->startDateTime) {
            $event->startDateTime = Carbon::now();
        }
        if (!$event->endDateTime) {
            $event->endDateTime = Carbon::now()->addHour();
        }

        return view('calendar.edit', compact('event'));
    }

    /**
     * Update the specified event
     */
    public function update(Request $request, $eventId)
    {
        if (!$this->isAuthenticated()) {
            return redirect()->route('google.redirect');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'start_time' => 'required',
            'end_date' => 'required|date|after_or_equal:start_date',
            'end_time' => 'required',
            'attendees' => 'nullable|string',
            'add_meet_link' => 'nullable|boolean',
        ]);

        try {
            $event = Event::find($eventId);

            if (!$event) {
                return redirect()->route('calendar.index')
                    ->with('error', 'Event not found');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error finding event: ' . $e->getMessage());
            return redirect()->route('calendar.index')
                ->with('error', 'Event not found or could not be retrieved.');
        }

        $startDateTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $request->start_date . ' ' . $request->start_time
        );

        $endDateTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $request->end_date . ' ' . $request->end_time
        );

        // Update basic event details
        $event->name = $request->name;
        $event->description = $request->description;
        $event->startDateTime = $startDateTime;
        $event->endDateTime = $endDateTime;
        
        // Add attendees if provided
        if ($request->filled('attendees')) {
            // Clear existing attendees by setting to empty array
            // Note: The Google Calendar API doesn't provide a direct way to clear attendees
            // So we're updating with the new list only
            $attendeeEmails = array_map('trim', explode(',', $request->attendees));
            foreach ($attendeeEmails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $event->addAttendee([
                        'email' => $email,
                    ]);
                }
            }
        }
        
        // Add Google Meet link if requested
        if ($request->has('add_meet_link')) {
            $event->addMeetLink();
        }
        
        $event->save();

        return redirect()->route('calendar.index')
            ->with('success', 'Event updated successfully');
    }

    /**
     * Remove the specified event
     */
    public function destroy($eventId)
    {
        if (!$this->isAuthenticated()) {
            return redirect()->route('google.redirect');
        }

        try {
            $event = Event::find($eventId);

            if (!$event) {
                return redirect()->route('calendar.index')
                    ->with('error', 'Event not found');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error finding event: ' . $e->getMessage());
            return redirect()->route('calendar.index')
                ->with('error', 'Event not found or could not be retrieved.');
        }

        $event->delete();

        return redirect()->route('calendar.index')
            ->with('success', 'Event deleted successfully');
    }
}