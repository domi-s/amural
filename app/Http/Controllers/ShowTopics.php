<?php

namespace App\Http\Controllers;

use App\Support\BuildTopicsIndex;
use Illuminate\Contracts\View\View;

/**
 * GET /topics: browse the top-level topics.
 */
class ShowTopics
{
    public function __invoke(BuildTopicsIndex $build): View
    {
        return view('topics.index', ['index' => $build->execute()]);
    }
}
