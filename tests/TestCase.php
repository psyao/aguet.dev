<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Livewire\Livewire;

abstract class TestCase extends BaseTestCase
{
    /**
     * Livewire remembers in a static that an admin page rendered a component, and a
     * test's request never terminates to forget it: the next front page would then
     * carry Livewire's assets, which production never serves.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Livewire::flushState();
    }
}
