<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class JoinRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'email', 
        'status',
        'message',
        'approved_at',
        'approved_by'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    /**
     * Check if request is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if request is approved
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Mark as approved
     */
    public function approve($adminEmail = null)
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => Carbon::now(),
            'approved_by' => $adminEmail
        ]);
    }

    /**
     * Mark as rejected
     */
    public function reject($adminEmail = null)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $adminEmail
        ]);
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
