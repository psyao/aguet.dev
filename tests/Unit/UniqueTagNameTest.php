<?php

namespace Tests\Unit;

use App\Models\Tag;
use App\Rules\UniqueTagName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UniqueTagNameTest extends TestCase
{
    use RefreshDatabase;

    private function passes(string $name, ?int $ignoreId = null): bool
    {
        return Validator::make(
            ['name' => $name],
            ['name' => [new UniqueTagName($ignoreId)]],
        )->passes();
    }

    public function test_rejects_case_insensitive_duplicate(): void
    {
        Tag::create(['name' => 'Laravel']);

        $this->assertFalse($this->passes('laravel'));
        $this->assertFalse($this->passes('  LARAVEL  '));
    }

    public function test_accepts_new_name(): void
    {
        Tag::create(['name' => 'Laravel']);

        $this->assertTrue($this->passes('Filament'));
    }

    public function test_ignores_the_record_being_edited(): void
    {
        $tag = Tag::create(['name' => 'Laravel']);

        $this->assertTrue($this->passes('laravel', $tag->id));
    }
}
