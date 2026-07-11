<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Generates the shared secret for the Infomaniak HTTP cron trigger
 * (GET /cron/{token}). Plain run only prints the token; --force also
 * writes it into the environment file as CRON_TOKEN=, replacing any
 * existing line(s).
 *
 * --force is a local-dev/emergency convenience only. In staging and
 * production, .env is regenerated from Doppler on every deploy, so an
 * SSH-side --force write gets silently reverted by the next deploy — the
 * token must be rotated in Doppler instead. See
 * docs/howto-rotate-cron-token.md.
 */
class GenerateCronToken extends Command
{
    protected $signature = 'cron:token {--force : Write the token into the .env file, replacing any existing CRON_TOKEN - local/emergency use only, see docs/howto-rotate-cron-token.md for staging/production rotation}';

    protected $description = 'Generate a token for the Infomaniak HTTP cron trigger';

    public function handle(): int
    {
        $token = bin2hex(random_bytes(24));

        $this->line($token);

        if (! $this->option('force')) {
            return self::SUCCESS;
        }

        $path = app()->environmentFilePath();

        if (is_dir($path) || ! is_readable($path) || ! is_writable($path)) {
            $this->error("Cannot read/write the environment file at {$path}.");

            return self::FAILURE;
        }

        // @-suppressed: a directory named .env passes the is_readable()/
        // is_writable() preflight above but makes file_get_contents() emit a
        // warning that Laravel's error handler would otherwise escalate to
        // an ErrorException — the false-return check below is what actually
        // handles this case.
        $contents = @file_get_contents($path);

        if ($contents === false) {
            $this->error("Failed to read the environment file at {$path}.");

            return self::FAILURE;
        }

        $lines = array_values(array_filter(
            preg_split('/\R/', $contents),
            fn (string $line): bool => ! str_starts_with($line, 'CRON_TOKEN=')
        ));

        while ($lines !== [] && end($lines) === '') {
            array_pop($lines);
        }

        $lines[] = "CRON_TOKEN={$token}";

        $written = file_put_contents($path, implode(PHP_EOL, $lines).PHP_EOL);

        if ($written === false) {
            $this->error("Failed to write the environment file at {$path}.");

            return self::FAILURE;
        }

        $this->info('CRON_TOKEN written to .env.');

        return self::SUCCESS;
    }
}
