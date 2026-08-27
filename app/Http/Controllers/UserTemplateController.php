<?php

namespace App\Http\Controllers;

use App\Http\Requests\Applicant\BuildTemplateRequest;
use App\Http\Requests\Applicant\GenerateTemplateRequest;
use App\Models\CreditTransaction;
use App\Models\EmailTemplate;
use App\Services\Ai\AiException;
use App\Services\Ai\TemplateGenerator;
use App\Services\Credits\CreditLedger;
use App\Services\Templates\AccentPalette;
use App\Services\Templates\TemplateGuide;
use App\Services\Templates\TemplateValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserTemplateController extends Controller
{
    public function create(Request $request, CreditLedger $credits): Response
    {
        $user = $request->user();

        return Inertia::render('templates/Generate', [
            'balance' => $credits->balance($user),
            'price' => $credits->priceOf(CreditTransaction::TEMPLATE_DESIGN),
            'reward' => (int) config('emailcv.credits.promotion_reward'),
            'terms' => $this->terms(),
            'examples' => [
                'A bold, colourful layout for a graphic designer applying to a creative studio. Big name, room for a short pitch and a few standout projects.',
                'A calm, formal template for a finance analyst applying to a bank. Serif type, no photo, plenty of white space.',
                'A friendly template for a teacher applying to an international school, with a warm colour and space for certifications.',
            ],
        ]);
    }

    public function store(GenerateTemplateRequest $request, TemplateGenerator $generator, CreditLedger $credits): RedirectResponse
    {
        $user = $request->user();
        $price = $credits->priceOf(CreditTransaction::TEMPLATE_DESIGN);

        if (! $credits->canAfford($user, CreditTransaction::TEMPLATE_DESIGN)) {
            return back()->withErrors([
                'brief' => sprintf(
                    'Designing a template costs %d credits and you have %d. Your allowance tops up at the start of next month, and the shared library is open to you meanwhile.',
                    $price,
                    $credits->balance($user),
                ),
            ]);
        }

        // A whole template is a large, slow generation; a burst of them is
        // expensive even inside the lifetime allowance.
        $key = 'generate-template:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 2)) {
            return back()->withErrors([
                'brief' => 'Give it a moment — you can design another template in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        RateLimiter::hit($key, 300);

        try {
            $generated = $generator->generate($user, $request->string('brief')->toString());
        } catch (AiException $e) {
            return back()->withErrors(['brief' => $e->getMessage()]);
        }

        $template = $generated->template;

        $template->forceFill([
            'created_by' => $user->id,
            'origin' => EmailTemplate::ORIGIN_GENERATED,
            'visibility' => 'private',
            'slug' => $this->slug($template->name),
            'terms_accepted_at' => now(),
        ])->save();

        $generated->generation->forceFill(['email_template_id' => $template->id])->save();

        // Charged only now that a usable template exists.
        $credits->spend($user, CreditTransaction::TEMPLATE_DESIGN, [
            'email_template_id' => $template->id,
            'ai_generation_id' => $generated->generation->id,
            'description' => $template->name,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "“{$template->name}” is ready. Review it before you send anything with it.",
        ]);

        return to_route('templates.index', ['highlight' => $template->id]);
    }

    /**
     * The hand-written half of "design your own": the author supplies the HTML
     * themselves, so there is no model call, no allowance and nothing to charge.
     */
    public function build(Request $request, TemplateGuide $guide, CreditLedger $credits): Response
    {
        $examples = $guide->examples();

        return Inertia::render('templates/Build', [
            'designPrice' => $credits->priceOf(CreditTransaction::TEMPLATE_DESIGN),
            'guide' => $guide->sections(),
            'tokens' => $guide->tokens(),
            'examples' => $examples,
            'starter' => $examples[0],
            'maxBytes' => TemplateValidator::MAX_HTML_BYTES,
            'terms' => $this->buildTerms(),
        ]);
    }

    /**
     * Live sanitising, token detection and preview, called as the author types.
     *
     * The preview is rendered from the sanitised markup rather than the raw
     * textarea, so what they are judging is what would actually be saved.
     */
    public function analyse(Request $request, TemplateValidator $validator): JsonResponse
    {
        $validated = $request->validate([
            'html' => ['required', 'string', 'max:'.(TemplateValidator::MAX_HTML_BYTES * 4)],
            'accent_color' => ['nullable', 'string', 'max:20'],
        ]);

        $check = $validator->check(
            $validated['html'],
            $this->accent($validated['accent_color'] ?? null),
            $request->user()->profile,
        );

        return response()->json([
            'preview' => $check->preview,
            'fields' => $check->fields,
            'problems' => $check->problems,
            'removed' => $check->removed,
            'warnings' => $check->warnings,
            'size' => strlen($validated['html']),
        ]);
    }

    public function storeHtml(BuildTemplateRequest $request, TemplateValidator $validator): RedirectResponse
    {
        $user = $request->user();
        $accent = $this->accent($request->string('accent_color')->toString());

        $check = $validator->check($request->string('html')->toString(), $accent, $user->profile);

        if (! $check->isUsable()) {
            return back()->withErrors(['html' => implode(' ', $check->problems)]);
        }

        $template = new EmailTemplate([
            'name' => Str::limit(trim($request->string('name')->toString()), 60, ''),
            'description' => Str::limit(trim($request->string('description')->toString()), 400, ''),
            'accent_color' => $accent,
            // The sanitised markup is what is stored: the raw paste never
            // reaches the database, so nothing has to be re-filtered on read.
            'html' => $check->html,
            'fields' => $check->fields,
            'is_active' => true,
            'sort_order' => 100,
        ]);

        $template->forceFill([
            'created_by' => $user->id,
            'origin' => EmailTemplate::ORIGIN_HAND_WRITTEN,
            'visibility' => 'private',
            'slug' => $this->slug($template->name),
            'terms_accepted_at' => now(),
        ])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "“{$template->name}” is saved and ready to use.",
        ]);

        return to_route('templates.index', ['highlight' => $template->id]);
    }

    public function destroy(Request $request, EmailTemplate $template): RedirectResponse
    {
        // Only your own, still-private designs. A promoted template belongs to
        // the platform and other people's drafts may already point at it.
        abort_unless($template->isOwnedBy($request->user()) && ! $template->isGlobal(), 403);

        $template->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Template deleted.']);

        return to_route('templates.index');
    }

    private function accent(?string $value): string
    {
        return (new AccentPalette)->for($value)['accent'];
    }

    private function slug(string $name): string
    {
        $base = Str::slug($name) ?: 'template';

        return $base.'-'.Str::lower(Str::random(6));
    }

    /**
     * @return array<int, string>
     */
    private function terms(): array
    {
        return [
            'The AI writes the layout from your description. Check every line before you send it to anyone — it can get things wrong.',
            'The template is yours and stays private to your account unless you are told otherwise.',
            'If an administrator judges your design good enough, they may publish it to the shared template library for all users. You grant permission for that by generating it here, and you earn '.config('emailcv.credits.promotion_reward').' credits when it happens.',
            'Published designs are shown without your name or any of your personal details. Your profile, CV and application text are never shared.',
            'Designs that are offensive, misleading, or that impersonate a real company may be removed, and repeated abuse can suspend your account.',
            'Each design costs '.config('emailcv.credits.prices.template_design').' credits whether or not you end up using it. Attempts that fail cost nothing.',
        ];
    }

    /**
     * The hand-written path swaps the warnings about model output for the one
     * risk it introduces instead: markup the author may not own.
     *
     * @return array<int, string>
     */
    private function buildTerms(): array
    {
        return [
            'Writing your own template is free and does not use any credits.',
            'Your HTML is filtered before it is saved — scripts, style blocks, forms and remote images are removed. Anything taken out is listed beside the preview before you save.',
            'Only save HTML you have the right to use. Do not paste a design you copied from another company, a paid template you have not licensed, or someone else\'s work.',
            'The template is yours and stays private to your account unless you are told otherwise.',
            'If an administrator judges your design good enough, they may publish it to the shared template library for all users. You grant permission for that by saving it here, and you earn '.config('emailcv.credits.promotion_reward').' credits when it happens.',
            'Published designs are shown without your name or any of your personal details. Your profile, CV and application text are never shared.',
            'Designs that are offensive, misleading, or that impersonate a real company may be removed, and repeated abuse can suspend your account.',
        ];
    }
}
