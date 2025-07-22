<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\GoogleCalendar\Event;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\JoinRequest;
use Google\Client;
use Google\Service\Calendar;

class GuestCalendarController extends Controller
{
    /**
     * Display a listing of the events for guest users
     */
    public function index()
    {
        // Get events without requiring authentication
        $events = $this->getEventsWithoutAuth();
        
        // Process events to ensure date properties are not null
        foreach ($events as $event) {
            if (!$event->startDateTime) {
                $event->startDateTime = Carbon::now();
            }
            if (!$event->endDateTime) {
                $event->endDateTime = Carbon::now()->addHour();
            }
        }
        
        return view('guest.index', compact('events'));
    }
    
    /**
     * Show event details for a specific event
     */
    public function show($eventId)
    {
        try {
            $event = $this->getEventWithoutAuth($eventId);
            
            if (!$event) {
                throw new \Exception('Event not found');
            }
            
            // Ensure date properties are not null
            if (!$event->startDateTime) {
                $event->startDateTime = Carbon::now();
            }
            if (!$event->endDateTime) {
                $event->endDateTime = Carbon::now()->addHour();
            }
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error showing event: ' . $e->getMessage());
            
            return redirect()->route('guest.index')
                ->with('error', 'Event not found or no longer available');
        }
        
        return view('guest.show', compact('event'));
    }
    
    /**
     * Handle the join request for an event
     */
    public function join(Request $request, $eventId)
    {
        // Validate the request
        $request->validate([
            'email' => 'required|email',
            'message' => 'nullable|string|max:500'
        ]);

        // Validate that the event exists before proceeding
        try {
            $event = $this->getEventWithoutAuth($eventId);
            
            if (!$event) {
                throw new \Exception('Event not found');
            }

            // Check if user already has a pending or approved request
            $existingRequest = JoinRequest::where('event_id', $eventId)
                ->where('email', $request->email)
                ->first();

            if ($existingRequest) {
                if ($existingRequest->isPending()) {
                    return redirect()->route('guest.show', $eventId)
                        ->with('info', 'Anda sudah memiliki permintaan bergabung yang sedang menunggu persetujuan untuk event ini.');
                } elseif ($existingRequest->isApproved()) {
                    return redirect()->route('guest.show', $eventId)
                        ->with('info', 'Anda sudah bergabung dengan event ini!');
                }
            }

            // Create join request
            JoinRequest::create([
                'event_id' => $eventId,
                'email' => $request->email,
                'message' => $request->message,
                'status' => 'pending'
            ]);
            
            return redirect()->route('guest.show', $eventId)
                ->with('success', 'Permintaan bergabung Anda telah dikirim! Admin akan menambahkan Anda ke event setelah persetujuan.');

        } catch (\Exception $e) {
            Log::error('Error in join method: ' . $e->getMessage());
            
            return redirect()->route('guest.index')
                ->with('error', 'Tidak dapat mengirim permintaan bergabung. Silakan coba lagi nanti.');
        }
    }
    
    /**
     * Get events without requiring authentication using service account or public access
     */
    private function getEventsWithoutAuth()
    {
        try {
            // First try to use service account if available
            if ($this->hasServiceAccount()) {
                return $this->getEventsViaServiceAccount();
            }
            
            // If no service account, try to use existing OAuth token if available
            if ($this->hasOAuthToken()) {
                return Event::get();
            }
            
            // If no authentication available, return empty collection
            Log::warning('No authentication method available for guest calendar access');
            return collect([]);
            
        } catch (\Exception $e) {
            Log::error('Error in getEventsWithoutAuth: ' . $e->getMessage());
            return collect([]);
        }
    }
    
    /**
     * Get a specific event without requiring authentication
     */
    private function getEventWithoutAuth($eventId)
    {
        try {
            // First try to use service account if available
            if ($this->hasServiceAccount()) {
                return $this->getEventViaServiceAccount($eventId);
            }
            
            // If no service account, try to use existing OAuth token if available
            if ($this->hasOAuthToken()) {
                return Event::find($eventId);
            }
            
            // If no authentication available, return null
            Log::warning('No authentication method available for guest calendar access');
            return null;
            
        } catch (\Exception $e) {
            Log::error('Error in getEventWithoutAuth: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if service account credentials are available
     */
    private function hasServiceAccount()
    {
        return file_exists(storage_path('app/google-calendar/service-account-credentials.json'));
    }
    
    /**
     * Check if OAuth token is available
     */
    private function hasOAuthToken()
    {
        return file_exists(storage_path('app/google-calendar/oauth-token.json'));
    }
    
    /**
     * Get events using service account
     */
    private function getEventsViaServiceAccount()
    {
        $client = new Client();
        $client->setAuthConfig(storage_path('app/google-calendar/service-account-credentials.json'));
        $client->addScope(Calendar::CALENDAR_READONLY);
        
        $service = new Calendar($client);
        $calendarId = config('google-calendar.calendar_id');
        
        if (!$calendarId) {
            throw new \Exception('Calendar ID not configured');
        }
        
        $optParams = [
            'maxResults' => 50,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => Carbon::now()->toISOString(),
        ];
        
        $results = $service->events->listEvents($calendarId, $optParams);
        $events = $results->getItems();
        
        // Convert to Laravel collection with similar structure to spatie/laravel-google-calendar
        return collect($events)->map(function ($event) {
            $eventObj = new \stdClass();
            $eventObj->id = $event->getId();
            $eventObj->name = $event->getSummary();
            $eventObj->description = $event->getDescription();
            
            // Handle start time
            $start = $event->getStart();
            if ($start->getDateTime()) {
                $eventObj->startDateTime = Carbon::parse($start->getDateTime());
            } elseif ($start->getDate()) {
                $eventObj->startDateTime = Carbon::parse($start->getDate());
            } else {
                $eventObj->startDateTime = Carbon::now();
            }
            
            // Handle end time
            $end = $event->getEnd();
            if ($end->getDateTime()) {
                $eventObj->endDateTime = Carbon::parse($end->getDateTime());
            } elseif ($end->getDate()) {
                $eventObj->endDateTime = Carbon::parse($end->getDate());
            } else {
                $eventObj->endDateTime = Carbon::now()->addHour();
            }
            
            $eventObj->googleEvent = $event;
            
            return $eventObj;
        });
    }
    
    /**
     * Get a specific event using service account
     */
    private function getEventViaServiceAccount($eventId)
    {
        $client = new Client();
        $client->setAuthConfig(storage_path('app/google-calendar/service-account-credentials.json'));
        $client->addScope(Calendar::CALENDAR_READONLY);
        
        $service = new Calendar($client);
        $calendarId = config('google-calendar.calendar_id');
        
        if (!$calendarId) {
            throw new \Exception('Calendar ID not configured');
        }
        
        $event = $service->events->get($calendarId, $eventId);
        
        // Convert to similar structure as spatie/laravel-google-calendar
        $eventObj = new \stdClass();
        $eventObj->id = $event->getId();
        $eventObj->name = $event->getSummary();
        $eventObj->description = $event->getDescription();
        
        // Handle start time
        $start = $event->getStart();
        if ($start->getDateTime()) {
            $eventObj->startDateTime = Carbon::parse($start->getDateTime());
        } elseif ($start->getDate()) {
            $eventObj->startDateTime = Carbon::parse($start->getDate());
        } else {
            $eventObj->startDateTime = Carbon::now();
        }
        
        // Handle end time
        $end = $event->getEnd();
        if ($end->getDateTime()) {
            $eventObj->endDateTime = Carbon::parse($end->getDateTime());
        } elseif ($end->getDate()) {
            $eventObj->endDateTime = Carbon::parse($end->getDate());
        } else {
            $eventObj->endDateTime = Carbon::now()->addHour();
        }
        
        $eventObj->googleEvent = $event;
        
        return $eventObj;
    }
}