<?php

use App\Models\SiteContent;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $content = SiteContent::current();

        $translations = collect($content->getTranslations('hero_title'))
            ->map(fn (?string $value) => str_replace('**', '*', (string) $value))
            ->all();

        $content->setTranslations('hero_title', $translations);
        $content->save();
    }

    public function down(): void
    {
        // One-way data normalization; nothing to revert.
    }
};
