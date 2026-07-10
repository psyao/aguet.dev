<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Infomaniak's pseudo-cron ("Planificateur de tâches") is URL/HTTP-based, not
 * a real crontab — this is its entrypoint. Deliberately calls contact:notify
 * directly, not schedule:run: schedule:run would execute every scheduled task
 * (not just this one) and swallows task exceptions internally, making its
 * exit code meaningless. See
 * docs/superpowers/specs/2026-07-08-infomaniak-cron-trigger-design.md for the
 * full reasoning behind every decision below.
 */
class CronController extends Controller
{
    private const LOCK_KEY = 'cron:contact-notify';

    private const LOCK_SECONDS = 240;

    private const LAST_SUCCESS_KEY = 'cron:contact-notify:last-success';

    public function __invoke(Request $request, string $token): Response
    {
        if ($request->isMethod('head')) {
            return $this->noStore(response('', 404));
        }

        $configured = config('aguet.cron.token');

        if (! is_string($configured) || ! filled($configured) || ! hash_equals($configured, $token)) {
            return $this->noStore(response('', 404));
        }

        $lock = null;
        $acquired = false;

        try {
            $lock = Cache::lock(self::LOCK_KEY, self::LOCK_SECONDS);
            $acquired = $lock->get();

            if (! $acquired) {
                return $this->noStore(response('BUSY', 200));
            }

            $exitCode = Artisan::call('contact:notify');

            if ($exitCode !== 0) {
                Log::error('cron:contact-notify — contact:notify exited non-zero.', [
                    'exit_code' => $exitCode,
                    'output' => Artisan::output(),
                ]);

                return $this->noStore(response('FAILED', 500));
            }

            if (! Cache::put(self::LAST_SUCCESS_KEY, now(), now()->addDays(2))) {
                Log::error('cron:contact-notify — failed to write the last-success cache key.');

                return $this->noStore(response('FAILED', 500));
            }

            return $this->noStore(response('OK', 200));
        } catch (Throwable $e) {
            Log::error('cron:contact-notify — uncaught exception.', ['exception' => $e]);

            return $this->noStore(response('FAILED', 500));
        } finally {
            // Only release if get() actually returned true — Laravel's locks are
            // owner-scoped, so releasing an unacquired lock is likely harmless,
            // but there's no reason to call release() on a lock this request
            // never held.
            if ($acquired) {
                try {
                    $lock->release();
                } catch (Throwable $e) {
                    Log::warning('cron:contact-notify — lock release failed.', ['exception' => $e]);
                }
            }
        }
    }

    private function noStore(Response $response): Response
    {
        return $response->header('Cache-Control', 'no-store');
    }
}
