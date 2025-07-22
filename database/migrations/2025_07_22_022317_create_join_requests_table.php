<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('join_requests', function (Blueprint $table) {
            $table->id();
            $table->string('event_id'); // Google Calendar Event ID
            $table->string('email');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('message')->nullable(); // Optional message from guest
            $table->timestamp('approved_at')->nullable();
            $table->string('approved_by')->nullable(); // Admin email who approved
            $table->timestamps();
            
            // Ensure one request per email per event
            $table->unique(['event_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('join_requests');
    }
};
