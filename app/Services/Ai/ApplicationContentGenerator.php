<?php

namespace App\Services\Ai;

use App\Models\AiGeneration;
use App\Models\Application;
use App\Models\EmailTemplate;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Turns a CV plus a job post into values for whichever tokens the chosen
 * template exposes, then records the call for quota and abuse tracking.
 */
class ApplicationContentGenerator
{
    /** Enough job post to capture the requirements without burning tokens. */
    private const MAX_JOB_POST_CHARS = 8000;

    private const MAX_CV_CHARS = 12000;

    public function __construct(private DeepSeekClient $client) {}

    public function generate(User $user, Application $application, EmailTemplate $template, Profile $profile): GeneratedContent
    {
        $fields = $template->aiFields();

        if ($fields === []) {
            throw new AiException('This template has no AI-writable fields. Fill it in manually instead.');
        }

        $startedAt = microtime(true);

        try {
            $result = $this->client->json(
                $this->systemPrompt(),
                $this->userPrompt($application, $profile, $fields),
            );
        } catch (AiException $e) {
            $this->record($user, $application, 'failed', [], (int) ((microtime(true) - $startedAt) * 1000), $e->getMessage());

            throw $e;
        }

        return new GeneratedContent(
            $this->normalise($result['content'], $fields),
            $this->record($user, $application, 'success', $result['usage'], (int) ((microtime(true) - $startedAt) * 1000)),
        );
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
        You write job application emails. You are given a candidate's CV, a job posting, and a
        list of content fields that a template needs filled in.

        Rules:
        - Write only from facts present in the CV. Never invent employers, dates, degrees, tools
          or metrics. If the CV does not support a claim, leave it out.
        - Never commit the candidate to anything they have not stated: notice periods, start
          dates, salary expectations, relocation or working hours. Describe what is on the CV,
          such as where they are based, and stop there.
        - Never merge two separate numbers into one figure. "cut turnaround from 5 days to 1"
          is not the figure 51. If no single clean number exists for a field, return "".
        - The CV is attached to this email. A portfolio or LinkedIn is a link, not an attachment.
        - Match the language of the job posting. If the posting is in Indonesian, write in
          Indonesian; if it is in English, write in English.
        - Be specific and concrete. Name the actual companies, tools and results from the CV that
          connect to the posting's requirements.
        - Professional and warm, never florid. No "I am writing to express my keen interest",
          no "dear sir or madam", no bullet-point padding.
        - Respect the length hint on each field. These are email fragments, not a full letter.
        - Do not include HTML, markdown, emoji, or the field labels themselves in your values.

        Return a single JSON object whose keys are exactly the requested field tokens. Fields of
        type "list" take an array of short strings. Every other field takes a plain string.
        Include every requested token and no others.
        PROMPT;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    private function userPrompt(Application $application, Profile $profile, array $fields): string
    {
        $candidate = collect([
            'Name' => $profile->full_name,
            'Headline' => $profile->headline,
            'Location' => $profile->location,
            'Portfolio' => $profile->portfolio_url,
            'LinkedIn' => $profile->linkedin_url,
        ])->filter()->map(fn ($value, $key) => "{$key}: {$value}")->implode("\n");

        $target = collect([
            'Company' => $application->company,
            'Role' => $application->position,
            'Hiring contact' => $application->recipient_name,
        ])->filter()->map(fn ($value, $key) => "{$key}: {$value}")->implode("\n");

        $fieldSpec = collect($fields)->map(function (array $field): string {
            $line = "- {$field['token']} (type: {$field['type']}) — {$field['label']}";

            if (filled($field['help'] ?? null)) {
                $line .= ". {$field['help']}";
            }

            return $line.'. '.$this->lengthHint((string) $field['type'], (string) $field['token']);
        })->implode("\n");

        $cv = Str::limit((string) $profile->cv_text, self::MAX_CV_CHARS, '…');
        $jobPost = Str::limit((string) $application->job_post, self::MAX_JOB_POST_CHARS, '…');

        return <<<PROMPT
        ## Candidate
        {$candidate}

        ## Target role
        {$target}

        ## Job posting
        {$jobPost}

        ## Candidate CV
        {$cv}

        ## Fields to write
        {$fieldSpec}

        Return the JSON object now.
        PROMPT;
    }

    private function lengthHint(string $type, string $token): string
    {
        if ($type === 'list') {
            // Chips hold a couple of words; card lists hold a claim each.
            return Str::contains($token, ['skill', 'tool', 'tag', 'stack', 'chip'])
                ? 'Return 4–6 items, 1–3 words each.'
                : 'Return 3–5 items, each one specific sentence under 20 words.';
        }

        if ($type === 'textarea') {
            return Str::contains($token, ['intro', 'opening'])
                ? 'One or two sentences.'
                : 'Two to four sentences, one paragraph.';
        }

        // Stat tiles want one clean figure, not a sentence and not two numbers.
        if (Str::endsWith($token, ['_value', '_number', '_figure'])) {
            return 'A single figure copied straight from the CV, such as 4+ or 32. '
                .'Digits only, optionally with a + or %. Return "" if the CV has no such figure.';
        }

        if (Str::endsWith($token, ['_label', '_caption'])) {
            return 'Two to four words naming what the figure counts.';
        }

        return 'A short phrase, under 12 words.';
    }

    /**
     * Coerce the model's output into the shape each field expects and drop
     * anything the template did not ask for.
     *
     * @param  array<string, mixed>  $content
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    private function normalise(array $content, array $fields): array
    {
        $values = [];

        foreach ($fields as $field) {
            $token = (string) $field['token'];

            if (! array_key_exists($token, $content)) {
                continue;
            }

            $value = $content[$token];

            $values[$token] = ($field['type'] ?? 'text') === 'list'
                ? collect(is_array($value) ? $value : (preg_split('/\r\n|\n|,/', (string) $value) ?: []))
                    ->map(fn ($item): string => trim(strip_tags((string) $item)))
                    ->filter()
                    ->values()
                    ->all()
                : trim(strip_tags((string) (is_array($value) ? implode(' ', $value) : $value)));
        }

        return $values;
    }

    /**
     * @param  array<string, int>  $usage
     */
    private function record(User $user, Application $application, string $status, array $usage, int $durationMs, ?string $error = null): AiGeneration
    {
        return AiGeneration::create([
            'user_id' => $user->id,
            'application_id' => $application->id,
            'model' => $this->client->model(),
            'status' => $status,
            'error' => $error ? Str::limit($error, 500) : null,
            'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
            'completion_tokens' => $usage['completion_tokens'] ?? 0,
            'total_tokens' => $usage['total_tokens'] ?? 0,
            'duration_ms' => $durationMs,
            'ip_address' => request()->ip(),
        ]);
    }
}
