<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\SiteContent;
use App\Models\SkillGroup;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * The bilingual one-page site. The locale is already set by SetLocale,
     * so the translatable models return the right language automatically.
     */
    public function index(): View
    {
        return view('home', [
            'content' => SiteContent::current(),
            'projects' => Project::published()->with('tags')->get(),
            'skills' => SkillGroup::ordered()->with('tags')->get(),
        ]);
    }
}
