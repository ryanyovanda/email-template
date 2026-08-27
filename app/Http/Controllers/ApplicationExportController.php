<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Services\Templates\TemplateRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hands the finished HTML to the user so they can paste it into the Gmail
 * extension, either as a copy-ready payload or a downloadable file.
 */
class ApplicationExportController extends Controller
{
    public function download(Request $request, Application $application, TemplateRenderer $renderer): Response
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        $template = $application->template;

        abort_if($template === null, 404, 'This application no longer has a template.');

        $html = $renderer->render($template, $request->user()->profile, $application->field_values ?? [], $application);

        $filename = Str::slug($application->displayName()).'.html';

        $application->forceFill(['last_copied_at' => now()])->save();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function markCopied(Request $request, Application $application): Response
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        $application->forceFill(['last_copied_at' => now()])->save();

        return response()->noContent();
    }
}
