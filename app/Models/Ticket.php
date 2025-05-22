<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'ticket_status_id',
        'name',
        'description',
        'user_id',
        'due_date',
        'uuid',
        'epic_id',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function ($ticket) {
            if (empty($ticket->uuid)) {
                $project = Project::find($ticket->project_id);
                $prefix = $project ? $project->ticket_prefix : 'TKT';
                $randomString = Str::upper(Str::random(6));

                $ticket->uuid = "{$prefix}-{$randomString}";
            }
        });

        static::updating(function ($ticket) {
            if ($ticket->isDirty('ticket_status_id')) {
                TicketHistory::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => auth()->id(),
                    'ticket_status_id' => $ticket->ticket_status_id,
                ]);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'ticket_status_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TicketHistory::class)->orderBy('created_at', 'desc');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->orderBy('created_at', 'asc');
    }

    public function epic(): BelongsTo
    {
        return $this->belongsTo(Epic::class);
    }
}
