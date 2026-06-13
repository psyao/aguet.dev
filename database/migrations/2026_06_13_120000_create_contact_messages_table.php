<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Inbound contact-form submissions. The row is the source of truth: the
     * Livewire component writes it before any mail leaves, so a lead is never
     * lost to a slow/dead SMTP. The `contact:notify` scheduled sweep then
     * delivers the owner notification out of band (notified_at / notify_attempts
     * track delivery). No `ip` column — abuse is handled by an in-memory rate
     * limiter only (GDPR/nFADP clean).
     */
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('subject', 150);
            $table->string('email');
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->unsignedInteger('notify_attempts')->default(0);
            $table->timestamps();

            // The sweep scans for undelivered rows on every run.
            $table->index(['notified_at', 'notify_attempts']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
