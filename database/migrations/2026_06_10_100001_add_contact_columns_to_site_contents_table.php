<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contact coordinates move from config/aguet.php into the editorial
     * singleton (plain columns — language-neutral, not translatable). The
     * backfill touches ONLY the new columns, so an already-edited production
     * row keeps its editorial content.
     */
    public function up(): void
    {
        Schema::table('site_contents', function (Blueprint $table) {
            $table->string('contact_email')->nullable();
            $table->string('contact_linkedin')->nullable();
            $table->string('contact_linkedin_label')->nullable();
            $table->string('contact_github')->nullable();
            $table->string('contact_github_label')->nullable();
        });

        DB::table('site_contents')->whereNull('contact_email')->update([
            'contact_email' => 'steve@aguet.dev',
            'contact_linkedin' => 'https://www.linkedin.com/in/steveaguet',
            'contact_linkedin_label' => '/in/steveaguet',
            'contact_github' => 'https://github.com/psyao',
            'contact_github_label' => '/psyao',
        ]);
    }

    public function down(): void
    {
        Schema::table('site_contents', function (Blueprint $table) {
            $table->dropColumn([
                'contact_email',
                'contact_linkedin',
                'contact_linkedin_label',
                'contact_github',
                'contact_github_label',
            ]);
        });
    }
};
