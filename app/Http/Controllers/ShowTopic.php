<?php

namespace App\Http\Controllers;

use App\Models\Concept;
use App\Support\TopicLink;
use App\Support\TopicResolution;
use App\Support\TopicResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * GET /topic/{public_id}/{slug?}. Only routes the request: the URL rules live in
 * TopicResolver and the page data in BuildTopicPage.
 */
class ShowTopic
{
    public function __invoke(
        Request $request,
        Concept $concept,
        TopicResolver $resolver,
        ?string $slug = null,
    ): View|RedirectResponse {
        $resolution = $resolver->resolve($concept, $slug, $request->query());

        if ($resolution->action === TopicResolution::REDIRECT) {
            return redirect()->to((string) $resolution->url, 301);
        }

        if ($resolution->action === TopicResolution::REPLACED_BY_MANY) {
            return view('topics.replaced', [
                'concept' => $concept,
                'links' => $resolution->replacements
                    ->map(function (Concept $replacement) use ($resolver) {
                        $label = $replacement->label()->label;

                        return new TopicLink(
                            concept: $replacement,
                            name: $label->pref ?? __('Untitled topic'),
                            url: $resolver->canonicalUrl($replacement),
                            language: $label === null ? null : explode('-', $label->locale_code)[0],
                        );
                    })
                    ->all(),
            ]);
        }

        return view('topics.show', ['concept' => $concept]);
    }
}
