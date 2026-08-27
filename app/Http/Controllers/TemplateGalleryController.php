<?php

namespace App\Http\Controllers;

use App\Models\CreditTransaction;
use App\Models\EmailTemplate;
use App\Services\Credits\CreditLedger;
use App\Services\Templates\TemplatePreview;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TemplateGalleryController extends Controller
{
    public function index(Request $request, TemplatePreview $preview, CreditLedger $credits): Response
    {
        $user = $request->user();
        $profile = $user->profile;

        $templates = EmailTemplate::query()
            ->active()
            ->visibleTo($user)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (EmailTemplate $template): array => [
                'id' => $template->id,
                'name' => $template->name,
                'slug' => $template->slug,
                'description' => $template->description,
                'accent_color' => $template->accent_color,
                'thumbnail_url' => $template->thumbnail_url,
                'field_count' => count($template->userFields()),
                'ai_field_count' => count($template->aiFields()),
                'is_mine' => $template->isOwnedBy($user),
                'is_global' => $template->isGlobal(),
                'is_community' => $template->isUserMade() && $template->isGlobal(),
                // Rendered with the viewer's own details so the card shows how
                // the design looks for them, not for a stranger.
                'preview_html' => $preview->render($template, $profile),
            ]);

        return Inertia::render('templates/Index', [
            'templates' => $templates,
            'hasCvText' => (bool) $user->profile?->hasUsableCvText(),
            'credits' => $credits->balance($user),
            'designPrice' => $credits->priceOf(CreditTransaction::TEMPLATE_DESIGN),
            'highlight' => $request->integer('highlight') ?: null,
        ]);
    }
}
