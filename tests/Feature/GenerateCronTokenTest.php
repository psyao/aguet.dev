<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * Points the app at an isolated temp directory containing a throwaway .env,
 * so cron:token --force never touches the real project .env. Returns the
 * temp env file's path; caller is responsible for cleanup.
 */
function useTempEnv(string $initialContents = "APP_NAME=Test\n"): string
{
    $dir = sys_get_temp_dir().'/cron-token-test-'.bin2hex(random_bytes(8));
    mkdir($dir);
    $envPath = $dir.'/.env';
    file_put_contents($envPath, $initialContents);

    // APP_ENV=testing makes Laravel lock environmentFile() to '.env.testing'
    // at boot (this project ships one). useEnvironmentPath() alone only moves
    // the directory, so without this reset the command would look for
    // "{$dir}/.env.testing" instead of the plain .env we just wrote.
    app()->useEnvironmentPath($dir);
    app()->loadEnvironmentFrom('.env');

    return $envPath;
}

function cleanupTempEnv(string $envPath): void
{
    @chmod($envPath, 0644);
    @unlink($envPath);
    @rmdir(dirname($envPath));
}

it('prints a 48-character hex token without --force (GCT1)', function () {
    $envPath = useTempEnv();
    $before = File::get($envPath);

    try {
        $exitCode = Artisan::call('cron:token');
        $output = trim(Artisan::output());

        expect($exitCode)->toBe(0)
            ->and($output)->toMatch('/^[a-f0-9]{48}$/')
            ->and(File::get($envPath))->toBe($before);
    } finally {
        cleanupTempEnv($envPath);
    }
});

it('appends CRON_TOKEN when --force is used and the key is absent (GCT2)', function () {
    $envPath = useTempEnv("APP_NAME=Test\n");

    try {
        $this->artisan('cron:token', ['--force' => true])->assertSuccessful();

        $contents = File::get($envPath);
        expect($contents)->toMatch('/^CRON_TOKEN=[a-f0-9]{48}$/m');
    } finally {
        cleanupTempEnv($envPath);
    }
});

it('replaces an existing CRON_TOKEN line when --force is used (GCT3)', function () {
    $envPath = useTempEnv("APP_NAME=Test\nCRON_TOKEN=oldvalue\n");

    try {
        $this->artisan('cron:token', ['--force' => true])->assertSuccessful();

        $contents = File::get($envPath);
        expect(substr_count($contents, 'CRON_TOKEN='))->toBe(1)
            ->and($contents)->not->toContain('CRON_TOKEN=oldvalue')
            ->and($contents)->toMatch('/^CRON_TOKEN=[a-f0-9]{48}$/m');
    } finally {
        cleanupTempEnv($envPath);
    }
});

it('refuses --force when the env file is unreadable (GCT4)', function () {
    if (function_exists('posix_getuid') && posix_getuid() === 0) {
        $this->markTestSkipped('Running as root — file permission checks are bypassed.');
    }

    $envPath = useTempEnv("APP_NAME=Test\n");
    $original = File::get($envPath);
    chmod($envPath, 0000);

    try {
        $this->artisan('cron:token', ['--force' => true])->assertFailed();
    } finally {
        chmod($envPath, 0644);
        expect(File::get($envPath))->toBe($original);
        cleanupTempEnv($envPath);
    }
});

it('refuses --force when the env file is readable but not writable (GCT5)', function () {
    if (function_exists('posix_getuid') && posix_getuid() === 0) {
        $this->markTestSkipped('Running as root — file permission checks are bypassed.');
    }

    $envPath = useTempEnv("APP_NAME=Test\n");
    $original = File::get($envPath);
    chmod($envPath, 0444);

    try {
        $this->artisan('cron:token', ['--force' => true])->assertFailed();
    } finally {
        chmod($envPath, 0644);
        expect(File::get($envPath))->toBe($original);
        cleanupTempEnv($envPath);
    }
});

it('dedupes multiple existing CRON_TOKEN lines down to one (GCT6)', function () {
    $envPath = useTempEnv("APP_NAME=Test\nCRON_TOKEN=old1\nSOMETHING=else\nCRON_TOKEN=old2\n");

    try {
        $this->artisan('cron:token', ['--force' => true])->assertSuccessful();

        $contents = File::get($envPath);
        expect(substr_count($contents, 'CRON_TOKEN='))->toBe(1)
            ->and($contents)->toContain('SOMETHING=else')
            ->and($contents)->toMatch('/^CRON_TOKEN=[a-f0-9]{48}$/m');
    } finally {
        cleanupTempEnv($envPath);
    }
});

/**
 * Proves the command checks file_get_contents()'s return value rather than
 * trusting the is_readable()/is_writable() preflight alone. GCT4/GCT5 only
 * cover the preflight failing; a directory named `.env` passes both of those
 * checks (directories are typically readable and writable) but makes
 * file_get_contents() itself fail and return false.
 *
 * Note: this does NOT exercise the file_put_contents()===false branch —
 * simulating a write failure that survives the is_writable() preflight
 * (disk-full, a race, a symlink loop) isn't practical to trigger portably in
 * a test. That branch is defensive code, verified by reading, not by a test.
 */
it('fails cleanly when the env "file" is actually a directory (GCT7)', function () {
    $dir = sys_get_temp_dir().'/cron-token-test-'.bin2hex(random_bytes(8));
    mkdir($dir);
    $envPathAsDir = $dir.'/.env';
    mkdir($envPathAsDir);

    app()->useEnvironmentPath($dir);
    app()->loadEnvironmentFrom('.env');

    try {
        $this->artisan('cron:token', ['--force' => true])->assertFailed();
    } finally {
        @rmdir($envPathAsDir);
        @rmdir($dir);
    }
});
