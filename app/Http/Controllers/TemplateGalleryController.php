<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TemplateGalleryController extends Controller
{
    public function index(Request $request): Response
    {
        $templates = EmailTemplate::query()
            ->active()
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
            ]);

        return Inertia::render('templates/Index', [
            'templates' => $templates,
            'hasCvText' => (bool) $request->user()->profile?->hasUsableCvText(),
        ]);
    }
}
