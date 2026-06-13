<?php

namespace App\Models;

use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A contact-form submission. Only the three visitor-supplied fields are
 * fillable; delivery bookkeeping (read_at / notified_at / notify_attempts) is
 * set explicitly by the inbox and the `contact:notify` sweep, never by
 * mass assignment.
 *
 * @method static ContactMessageFactory factory($count = null, $state = [])
 */
class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory;

    protected $fillable = ['subject', 'email', 'message'];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'notified_at' => 'datetime',
            'notify_attempts' => 'integer',
        ];
    }
}
