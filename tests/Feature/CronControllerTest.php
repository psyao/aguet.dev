<?php

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

const CRON_TEST_TOKEN = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'; // 48 lowercase hex chars

beforeEach(function () {
    config(['aguet.cron.token' => CRON_TEST_TOKEN]);
});

function cronUrl(string $token): string
{
    return '/cron/'.$token;
}

/**
 * RefreshDatabase runs migrations through the real Artisan facade before the
 * test body executes, which caches the real Kernel instance on the facade's
 * static resolvedInstance. $this->mock() only replaces the container
 * binding, so without clearing that cache the controller's Artisan::call()
 * would keep hitting the real kernel instead of this mock.
 */
function mockConsoleKernel(Closure $expectations): void
{
    test()->mock(ConsoleKernel::class, $expectations);

    Artisan::clearResolvedInstance(ConsoleKernel::class);
}

it('404s on a malformed-shape token at the route level (CC1)', function () {
    mockConsoleKernel(function ($mock) {
        $mock->shouldReceive('call')->never();
    });

    $this->get('/cron/too-short')->assertNotFound();
});

it('404s with no-store on a wrong token (CC2)', function () {
    mockConsoleKernel(function ($mock) {
        $mock->shouldReceive('call')->never();
    });

    $wrongToken = str_repeat('b', 48);
    $response = $this->get(cronUrl($wrongToken));

    $response->assertNotFound();
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('404s with no-store and skips contact:notify on HEAD (CC3)', function () {
    mockConsoleKernel(function ($mock) {
        $mock->shouldReceive('call')->never();
    });

    $response = $this->call('HEAD', cronUrl(CRON_TEST_TOKEN));

    $response->assertNotFound();
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('runs contact:notify and returns OK on a valid GET (CC4)', function () {
    mockConsoleKernel(function ($mock) {
        $mock->shouldReceive('call')->once()->with('contact:notify')->andReturn(0);
    });

    $response = $this->get(cronUrl(CRON_TEST_TOKEN));

    $response->assertOk();
    expect($response->getContent())->toBe('OK')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and(Cache::get('cron:contact-notify:last-success'))->not->toBeNull();
});

it('returns BUSY without invoking contact:notify when the lock is held (CC5)', function () {
    mockConsoleKernel(function ($mock) {
        $mock->shouldReceive('call')->never();
    });

    $lock = Cache::lock('cron:contact-notify', 240);
    $lock->get();

    try {
        $response = $this->get(cronUrl(CRON_TEST_TOKEN));

        $response->assertOk();
        expect($response->getContent())->toBe('BUSY')
            ->and($response->headers->get('Cache-Control'))->toContain('no-store');
    } finally {
        $lock->release();
    }
});

/**
 * The controller calls Artisan::output() on this branch — the mock must stub
 * it too, or Mockery throws on the unstubbed call and the outer
 * catch(Throwable) masks it as a (still-correct-looking) FAILED response
 * without ever exercising the non-zero-exit branch itself. Stubbing output()
 * closes that false-positive.
 */
it('returns FAILED on a non-zero contact:notify exit (CC6)', function () {
    mockConsoleKernel(function ($mock) {
        $mock->shouldReceive('call')->once()->with('contact:notify')->andReturn(1);
        $mock->shouldReceive('output')->once()->andReturn('some command output');
    });

    $response = $this->get(cronUrl(CRON_TEST_TOKEN));

    $response->assertServerError();
    expect($response->getContent())->toBe('FAILED')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and(Cache::get('cron:contact-notify:last-success'))->toBeNull();
});

/**
 * Checks the lock directly (a fresh acquisition succeeds) rather than firing
 * a second HTTP request through a second Artisan mock — the Artisan facade
 * caches its resolved root after first use, so a second $this->mock() call in
 * the same test wouldn't reliably replace it without an explicit
 * Artisan::clearResolvedInstance() call. Probing the lock state directly
 * avoids that trap entirely.
 */
it('returns FAILED when contact:notify throws, and releases the lock (CC7)', function () {
    mockConsoleKernel(function ($mock) {
        $mock->shouldReceive('call')->once()->with('contact:notify')->andThrow(new RuntimeException('boom'));
    });

    $response = $this->get(cronUrl(CRON_TEST_TOKEN));

    $response->assertServerError();
    expect($response->getContent())->toBe('FAILED')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store');

    $freshLock = Cache::lock('cron:contact-notify', 240);
    expect($freshLock->get())->toBeTrue();
    $freshLock->release();
});

it('does not run the web middleware group (no session cookie) (CC8)', function () {
    mockConsoleKernel(function ($mock) {
        $mock->shouldReceive('call')->once()->with('contact:notify')->andReturn(0);
    });

    $response = $this->get(cronUrl(CRON_TEST_TOKEN));

    expect($response->headers->get('Set-Cookie'))->toBeNull();
});

/**
 * Confirms the outer catch covers lock-acquisition failures, not just
 * command failures. The ->with(...) constraint also proves the controller
 * requests the documented key/TTL, not just "some lock."
 */
it('returns FAILED when Cache::lock() throws (CC9)', function () {
    mockConsoleKernel(function ($mock) {
        $mock->shouldReceive('call')->never();
    });

    Cache::shouldReceive('lock')->once()->with('cron:contact-notify', 240)
        ->andThrow(new RuntimeException('cache down'));

    $response = $this->get(cronUrl(CRON_TEST_TOKEN));

    $response->assertServerError();
    expect($response->getContent())->toBe('FAILED')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('returns FAILED when the last-success write throws (CC10)', function () {
    mockConsoleKernel(function ($mock) {
        $mock->shouldReceive('call')->once()->with('contact:notify')->andReturn(0);
    });

    $fakeLock = Mockery::mock(Lock::class);
    $fakeLock->shouldReceive('get')->once()->andReturn(true);
    $fakeLock->shouldReceive('release')->once()->andReturn(true);

    Cache::shouldReceive('lock')->once()->with('cron:contact-notify', 240)->andReturn($fakeLock);
    Cache::shouldReceive('put')->once()->andThrow(new RuntimeException('cache down'));

    $response = $this->get(cronUrl(CRON_TEST_TOKEN));

    $response->assertServerError();
    expect($response->getContent())->toBe('FAILED')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('returns FAILED when the last-success write returns false (CC10b)', function () {
    mockConsoleKernel(function ($mock) {
        $mock->shouldReceive('call')->once()->with('contact:notify')->andReturn(0);
    });

    $fakeLock = Mockery::mock(Lock::class);
    $fakeLock->shouldReceive('get')->once()->andReturn(true);
    $fakeLock->shouldReceive('release')->once()->andReturn(true);

    Cache::shouldReceive('lock')->once()->with('cron:contact-notify', 240)->andReturn($fakeLock);
    Cache::shouldReceive('put')->once()->andReturn(false);

    $response = $this->get(cronUrl(CRON_TEST_TOKEN));

    $response->assertServerError();
    expect($response->getContent())->toBe('FAILED')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('still returns OK when lock release() throws after a successful run (CC11)', function () {
    mockConsoleKernel(function ($mock) {
        $mock->shouldReceive('call')->once()->with('contact:notify')->andReturn(0);
    });

    $fakeLock = Mockery::mock(Lock::class);
    $fakeLock->shouldReceive('get')->once()->andReturn(true);
    $fakeLock->shouldReceive('release')->once()->andThrow(new RuntimeException('release failed'));

    Cache::shouldReceive('lock')->once()->with('cron:contact-notify', 240)->andReturn($fakeLock);
    Cache::shouldReceive('put')->once()->andReturn(true);

    $response = $this->get(cronUrl(CRON_TEST_TOKEN));

    $response->assertOk();
    expect($response->getContent())->toBe('OK')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store');
});

/**
 * If the controller called release() unconditionally, this fake lock's
 * release() would be invoked despite get() returning false — Mockery's
 * default "0 times unless declared" expectation on a plain mock() (not
 * shouldReceive) makes an unexpected call fail loudly.
 */
it('never calls release() on a lock it never acquired (CC11b)', function () {
    mockConsoleKernel(function ($mock) {
        $mock->shouldReceive('call')->never();
    });

    $fakeLock = Mockery::mock(Lock::class);
    $fakeLock->shouldReceive('get')->once()->andReturn(false);
    $fakeLock->shouldNotReceive('release');

    Cache::shouldReceive('lock')->once()->with('cron:contact-notify', 240)->andReturn($fakeLock);

    $response = $this->get(cronUrl(CRON_TEST_TOKEN));

    $response->assertOk();
    expect($response->getContent())->toBe('BUSY');
});

it('404s with no-store when the configured token is null (CC12)', function () {
    config(['aguet.cron.token' => null]);
    mockConsoleKernel(function ($mock) {
        $mock->shouldReceive('call')->never();
    });

    $response = $this->get(cronUrl(CRON_TEST_TOKEN));

    $response->assertNotFound();
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('404s with no-store when the configured token is blank (CC13)', function () {
    config(['aguet.cron.token' => '']);
    mockConsoleKernel(function ($mock) {
        $mock->shouldReceive('call')->never();
    });

    $response = $this->get(cronUrl(CRON_TEST_TOKEN));

    $response->assertNotFound();
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

/**
 * Realistic: CRON_TOKEN=true in .env is cast to boolean true by Laravel's
 * env() helper, so the configured value can legitimately be non-string.
 */
it('404s with no-store when the configured token is non-string (CC14)', function () {
    config(['aguet.cron.token' => true]);
    mockConsoleKernel(function ($mock) {
        $mock->shouldReceive('call')->never();
    });

    $response = $this->get(cronUrl(CRON_TEST_TOKEN));

    $response->assertNotFound();
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});
