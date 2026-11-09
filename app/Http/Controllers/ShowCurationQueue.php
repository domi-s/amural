<?php

namespace App\Http\Controllers;

use App\Enums\LabelStatus;
use App\Models\ConceptLabel;
use App\Models\Locale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * GET /curate. Curators see the names that nobody has reviewed yet, oldest first.
 */
class ShowCurationQueue
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->isCurator(), 403);

        $waiting = ConceptLabel::query()->whereIn('status', [LabelStatus::Machine, LabelStatus::Draft]);

        // How many names wait in each language, for the filter.
        $counts = (clone $waiting)->selectRaw('locale_code, count(*) as total')->groupBy('locale_code')->pluck('total', 'locale_code');
        $locales = Locale::query()->whereIn('code', $counts->keys())->orderByDesc('is_launch')->orderBy('sort')->get();

        $filter = $locales->firstWhere('code', $request->query('locale'));

        $labels = $waiting
            ->with(['concept', 'locale'])
            ->when($filter, fn ($query) => $query->where('locale_code', $filter->code))
            ->oldest('updated_at')
            ->oldest('id')
            ->paginate(25)
            ->withQueryString();

        return view('curate', [
            'labels' => $labels,
            'locales' => $locales,
            'counts' => $counts,
            'filter' => $filter,
            'total' => $counts->sum(),
        ]);
    }
}
