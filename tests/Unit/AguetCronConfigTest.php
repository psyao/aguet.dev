<?php

function withCronTokenEnv(?string $value, callable $callback): mixed
{
    $key = 'CRON_TOKEN';
    $hadGetenv = getenv($key);
    $hadEnv = $_ENV[$key] ?? null;
    $hadServer = $_SERVER[$key] ?? null;

    if ($value === null) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    } else {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    try {
        return $callback();
    } finally {
        if ($hadGetenv === false) {
            putenv($key);
        } else {
            putenv("{$key}={$hadGetenv}");
        }

        if ($hadEnv === null) {
            unset($_ENV[$key]);
        } else {
            $_ENV[$key] = $hadEnv;
        }

        if ($hadServer === null) {
            unset($_SERVER[$key]);
        } else {
            $_SERVER[$key] = $hadServer;
        }
    }
}

it('wires cron.token to the CRON_TOKEN env var', function () {
    $token = withCronTokenEnv('abc123', fn () => (require config_path('aguet.php'))['cron']['token']);

    expect($token)->toBe('abc123');
});

it('resolves cron.token to null when CRON_TOKEN is unset', function () {
    $token = withCronTokenEnv(null, fn () => (require config_path('aguet.php'))['cron']['token']);

    expect($token)->toBeNull();
});
