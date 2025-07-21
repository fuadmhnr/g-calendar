<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\GoogleCalendar\Event;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
            'email' => 'required|email'
        ]);

        // Validate that the event exists before proceeding
        try {
            $event = Event::find($eventId);
            
            if (!$event) {
                throw new \Exception('Event not found');
            }

            // Add the user as an attendee
            $event->addAttendee([
                'email' => $request->email,
            ]);
            
            $event->save();
            
            return redirect()->route('guest.show', $eventId)
                ->with('success', 'You have been added to the event! Check your email for details.');

        } catch (\Exception $e) {
            Log::error('Error in join method: ' . $e->getMessage());
            
            return redirect()->route('guest.index')
                ->with('error', 'Unable to add you to the event. Please try again later.');
        }
    }
    

    
    /**
     * Get events without requiring authentication
     */
    private function getEventsWithoutAuth()
    {
        // This is a simplified version that assumes the service account has access to the calendar
        // In a real application, you might need to use a service account with proper permissions
        try {
            return Event::get();
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
        // This is a simplified version that assumes the service account has access to the calendar
        // In a real application, you might need to use a service account with proper permissions
        try {
            return Event::find($eventId);
        } catch (\Exception $e) {
            Log::error('Error in getEventWithoutAuth: ' . $e->getMessage());
            return null;
        }
    }
    

}