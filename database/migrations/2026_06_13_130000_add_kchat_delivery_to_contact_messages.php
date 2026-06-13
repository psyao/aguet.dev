<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the kChat delivery rail to contact_messages. The email rail
 * (notified_at / notify_attempts) is unchanged; these mirror it so kChat
 * retries and dedupes independently.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->timestamp('kchat_notified_at')->nullable()->after('notify_attempts');
            $table->unsignedInteger('kchat_notify_attempts')->default(0)->after('kchat_notified_at');

            // The sweep scans for rows still pending on the kChat rail.
            $table->index(['kchat_notified_at', 'kchat_notify_attempts']);
        });

        // Backfill guard: rows that already exist must never be retro-pinged
        // when the webhook is first enabled. Mark them already-delivered on the
        // kChat rail (reuse the email timestamp, or now() if never emailed).
        DB::table('contact_messages')->update([
            'kchat_notified_at' => DB::raw('COALESCE(notified_at, CURRENT_TIMESTAMP)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropIndex(['kchat_notified_at', 'kchat_notify_attempts']);
            $table->dropColumn(['kchat_notified_at', 'kchat_notify_attempts']);
        });
    }
};
