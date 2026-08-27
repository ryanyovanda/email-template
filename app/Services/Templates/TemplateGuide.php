<?php

namespace App\Services\Templates;

/**
 * Everything a user needs to hand-write a template: the rules the validator
 * enforces, the tokens they may use, and complete working examples to start
 * from.
 *
 * This lives server-side rather than in the Vue page because the rules are the
 * validator's rules — when TemplateValidator changes, the guidance beside the
 * editor has to change with it, and a copy in the front end would quietly rot.
 */
class TemplateGuide
{
    /**
     * The guideline shown next to the editor, in the order it should be read.
     *
     * @return array<int, array{title: string, body: string, items: array<int, string>}>
     */
    public function sections(): array
    {
        return [
            [
                'title' => 'How a template works',
                'body' => 'A template is one chunk of HTML with placeholders in it. When you write an application, the app swaps every placeholder for a real value and you paste the result into Gmail.',
                'items' => [
                    'Write `{{ full_name }}` and it becomes your name, taken from your profile.',
                    'Write `{{ intro_paragraph }}` — a name you invented — and it becomes a box on the application form for you to fill in.',
                    'Anything that is not a placeholder is fixed text that appears in every email you send with this template.',
                    'Save the template and it is yours, private to your account, free, and reusable for every application.',
                ],
            ],
            [
                'title' => 'Rules that must be met to save',
                'body' => 'These are checked as you type. Until all of them pass, the Save button stays disabled.',
                'items' => [
                    'Build the layout out of `<table>` elements. Email clients do not support flexbox, grid, floats or CSS positioning.',
                    'The email must greet the recruiter with `{{ recipient_name }}`, name the job with `{{ position }}` and `{{ company }}`, and sign off with `{{ full_name }}`.',
                    'Use `{{ accent }}` somewhere for colour instead of hard-coding your main brand hex, so the colour picker still works.',
                    'Invent at least three placeholders of your own, so the email is not entirely fixed text. Four to eight is a good number.',
                    'Keep the whole thing under '.number_format(TemplateValidator::MAX_HTML_BYTES).' characters.',
                    'Never point an image at a relative path — email clients need a full `https://` URL.',
                ],
            ],
            [
                'title' => 'What gets removed automatically',
                'body' => 'Your HTML is filtered before it is saved. This is not optional and it is not a judgement on your markup — it is the same filter every template on the platform passes through. Anything removed is listed above the preview so you are never surprised by it.',
                'items' => [
                    '`<style>` blocks and `<link rel="stylesheet">`, along with everything inside them. Put your CSS in `style=""` attributes on each element instead.',
                    '`<script>`, `<form>`, `<input>`, `<button>`, `<iframe>`, `<svg>` and HTML comments.',
                    '`background-image` and any other `url()` in a style attribute, because a remote image can silently track whoever opens the email.',
                    'Event handlers such as `onclick`, and any link that is not `http`, `https`, `mailto` or `tel`.',
                    'Tags email clients do not understand are unwrapped — the tag goes, the text inside it stays.',
                ],
            ],
            [
                'title' => 'Pasting a template from somewhere else',
                'body' => 'Templates exported from a newsletter builder usually lean on a `<style>` block and media queries, and both are removed here. Expect to do some work after pasting one.',
                'items' => [
                    'Move the rules you care about into inline `style=""` attributes on the elements themselves.',
                    'Replace media queries with a fluid width: `width:100%` together with `max-width:640px` behaves well on a phone without them.',
                    'Delete the `<html>`, `<head>` and `<body>` wrapper — start your HTML at the outermost `<table>`.',
                    'Only paste HTML you have the right to use. An administrator may publish your design to the shared library, and a design you copied from someone else is not yours to share.',
                ],
            ],
            [
                'title' => 'Making it survive Gmail',
                'body' => 'These are not blocking, but they are the difference between a design that looks right on your screen and one that looks right in a recruiter\'s inbox.',
                'items' => [
                    'Give every cell that holds text its own `background-color`, or dark mode may invert it into something unreadable.',
                    'Give every `<img>` an `alt`. Gmail blocks images by default, so the alt text is often what the recruiter actually reads.',
                    'Where you use a CSS gradient, set a plain `bgcolor` attribute on the same cell as a fallback.',
                    'Write symbols as HTML entities — `&middot;`, `&bull;`, `&nbsp;` — rather than pasting them in directly, which can arrive as mojibake.',
                    'Keep the outer width to 640px or less. Anything wider is scrolled sideways on a phone.',
                ],
            ],
            [
                'title' => 'Repeating lists',
                'body' => 'For anything that is a list — skills, projects, achievements — wrap the row in a block. The applicant types one item per line and the row repeats once per item.',
                'items' => [
                    'Open with `{{# skills }}`, close with `{{/ skills }}`, and put `{{ . }}` where the item text goes.',
                    'Inside a block, `{{ . }}` is the only thing that changes between items. Any other placeholder in there would print the same value on every row, so it is rejected.',
                    '`{{ . }}` is always plain text — never use it as a link, an image source or a colour.',
                    'Everything between the open and close tag repeats, so keep the whole `<tr>` inside the block, not just the `<td>`.',
                ],
            ],
            [
                'title' => 'Colour',
                'body' => 'Pick one accent colour and the app derives a small matching palette from it. Use the palette placeholders and you can restyle the whole template later by changing one colour.',
                'items' => [
                    '`{{ accent }}` is your main colour, and the one the rules require you to use.',
                    '`{{ accent_dark }}` is a darker shade for borders and hover states, `{{ accent_soft }}` a pale tint for panel backgrounds.',
                    '`{{ accent_alt }}` is a harmonious partner colour, made for the far end of a gradient.',
                    '`{{ accent_contrast }}` is black or white, whichever stays readable on top of `{{ accent }}`. Use it for text sitting on an accent-coloured cell.',
                    'Plain greys, white and black are fine to hard-code — they are not part of the accent.',
                ],
            ],
        ];
    }

    /**
     * The placeholders available, grouped by where their value comes from.
     *
     * @return array<int, array{group: string, note: string, tokens: array<int, array{token: string, description: string}>}>
     */
    public function tokens(): array
    {
        return [
            [
                'group' => 'From your profile',
                'note' => 'Filled in automatically from your saved profile. These never appear on the application form.',
                'tokens' => [
                    ['token' => 'full_name', 'description' => 'Your full name'],
                    ['token' => 'headline', 'description' => 'Your one-line professional headline'],
                    ['token' => 'contact_email', 'description' => 'Your contact email address'],
                    ['token' => 'phone', 'description' => 'Your phone number, formatted for reading'],
                    ['token' => 'phone_link', 'description' => 'The same number for a tel: link'],
                    ['token' => 'location', 'description' => 'Where you are based'],
                    ['token' => 'portfolio_url', 'description' => 'Your portfolio address'],
                    ['token' => 'linkedin_url', 'description' => 'Your LinkedIn address'],
                    ['token' => 'photo_url', 'description' => 'Your photo — the only image you should use'],
                    ['token' => 'cv_url', 'description' => 'A link to your CV'],
                    ['token' => 'cv_filename', 'description' => 'The file name of your CV'],
                    ['token' => 'today', 'description' => "Today's date"],
                ],
            ],
            [
                'group' => 'From the application',
                'note' => 'Filled in per application, from the job you are applying to.',
                'tokens' => [
                    ['token' => 'recipient_name', 'description' => 'The recruiter you are writing to'],
                    ['token' => 'company', 'description' => 'The employer'],
                    ['token' => 'position', 'description' => 'The role being applied for'],
                ],
            ],
            [
                'group' => 'Colours',
                'note' => 'Derived from the accent colour you pick below.',
                'tokens' => [
                    ['token' => 'accent', 'description' => 'Your main colour — required at least once'],
                    ['token' => 'accent_dark', 'description' => 'A darker shade for borders and rules'],
                    ['token' => 'accent_soft', 'description' => 'A pale tint for panel backgrounds'],
                    ['token' => 'accent_alt', 'description' => 'A partner colour for gradients'],
                    ['token' => 'accent_contrast', 'description' => 'Readable text colour on top of the accent'],
                ],
            ],
            [
                'group' => 'Yours to invent',
                'note' => 'Any other name in snake_case becomes a box on the application form. The name decides the kind of box you get.',
                'tokens' => [
                    ['token' => 'intro_paragraph', 'description' => 'Ending in _paragraph, or containing intro, body, summary or closing, gives a large text area'],
                    ['token' => 'current_role', 'description' => 'Anything else gives a single-line box'],
                    ['token' => 'personal_site_url', 'description' => 'Ending in _url gives a link box'],
                    ['token' => '# achievements', 'description' => 'Wrapped as a block, gives a list — one item per line'],
                ],
            ],
        ];
    }

    /**
     * Complete, working templates to start from.
     *
     * Every one of these passes the validator as-is, so a user can load one,
     * press Save, and have something usable before they change a thing.
     *
     * @return array<int, array{key: string, name: string, description: string, accent_color: string, html: string}>
     */
    public function examples(): array
    {
        return [
            [
                'key' => 'letter',
                'name' => 'Simple Letter',
                'description' => 'A plain, formal note with no images. The safest thing to send and the easiest to edit.',
                'accent_color' => '#2F5D8C',
                'html' => $this->letter(),
            ],
            [
                'key' => 'banner',
                'name' => 'Accent Banner',
                'description' => 'A coloured header with your name in it, then a short pitch and a list of highlights.',
                'accent_color' => '#E86A33',
                'html' => $this->banner(),
            ],
            [
                'key' => 'profile',
                'name' => 'Profile Card',
                'description' => 'Your photo and contact details beside the letter, with a skills list underneath.',
                'accent_color' => '#1F7A63',
                'html' => $this->profile(),
            ],
        ];
    }

    private function letter(): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="background-color:#f4f4f5;padding:24px 0;font-family:Georgia,'Times New Roman',serif;">
  <tr>
    <td align="center">
      <table width="600" cellpadding="0" cellspacing="0" border="0" role="presentation" style="width:100%;max-width:600px;background-color:#ffffff;border-top:4px solid {{ accent }};">
        <tr>
          <td style="background-color:#ffffff;padding:32px 36px 8px;">
            <h1 style="margin:0;font-size:22px;line-height:1.3;color:#111111;">{{ full_name }}</h1>
            <p style="margin:6px 0 0;font-size:13px;color:#666666;">{{ headline }}</p>
          </td>
        </tr>
        <tr>
          <td style="background-color:#ffffff;padding:16px 36px 0;">
            <p style="margin:0 0 16px;font-size:15px;line-height:1.75;color:#333333;">Dear {{ recipient_name }},</p>
            <p style="margin:0 0 16px;font-size:15px;line-height:1.75;color:#333333;">{{ intro_paragraph }}</p>
            <p style="margin:0 0 16px;font-size:15px;line-height:1.75;color:#333333;">{{ why_me_paragraph }}</p>
            <p style="margin:0 0 16px;font-size:15px;line-height:1.75;color:#333333;">I am applying for the <strong style="color:{{ accent }};">{{ position }}</strong> role at {{ company }}, and I am available from {{ availability }}.</p>
            <p style="margin:0 0 24px;font-size:15px;line-height:1.75;color:#333333;">{{ closing_paragraph }}</p>
          </td>
        </tr>
        <tr>
          <td style="background-color:#ffffff;padding:0 36px 32px;">
            <p style="margin:0;font-size:15px;line-height:1.6;color:#333333;">Kind regards,</p>
            <p style="margin:4px 0 0;font-size:15px;font-weight:bold;color:#111111;">{{ full_name }}</p>
            <p style="margin:10px 0 0;font-size:13px;color:#777777;">{{ contact_email }} &middot; {{ phone }} &middot; {{ location }}</p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
HTML;
    }

    private function banner(): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="background-color:#f5f5f5;padding:24px 0;font-family:Helvetica,Arial,sans-serif;">
  <tr>
    <td align="center">
      <table width="620" cellpadding="0" cellspacing="0" border="0" role="presentation" style="width:100%;max-width:620px;background-color:#ffffff;border-radius:10px;">
        <tr>
          <td bgcolor="{{ accent }}" style="background-color:{{ accent }};background-image:linear-gradient(135deg,{{ accent }},{{ accent_alt }});padding:28px 32px;border-radius:10px 10px 0 0;">
            <h1 style="margin:0;font-size:24px;line-height:1.25;color:{{ accent_contrast }};">{{ full_name }}</h1>
            <p style="margin:6px 0 0;font-size:13px;color:{{ accent_contrast }};">{{ current_role }}</p>
          </td>
        </tr>
        <tr>
          <td style="background-color:#ffffff;padding:28px 32px 8px;">
            <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#2b2b2b;">Hi {{ recipient_name }},</p>
            <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#2b2b2b;">I would like to be considered for the <strong>{{ position }}</strong> role at {{ company }}.</p>
            <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#2b2b2b;">{{ pitch_paragraph }}</p>
          </td>
        </tr>
        <tr>
          <td style="background-color:#ffffff;padding:0 32px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="background-color:{{ accent_soft }};border-radius:8px;">
              <tr>
                <td style="background-color:{{ accent_soft }};padding:18px 20px 6px;">
                  <p style="margin:0;font-size:11px;letter-spacing:1px;text-transform:uppercase;color:{{ accent_dark }};">What I bring</p>
                </td>
              </tr>
              {{# highlights }}
              <tr>
                <td style="background-color:{{ accent_soft }};padding:4px 20px;font-size:14px;line-height:1.6;color:#2b2b2b;">&bull;&nbsp; {{ . }}</td>
              </tr>
              {{/ highlights }}
              <tr>
                <td style="background-color:{{ accent_soft }};padding:0 20px 18px;font-size:1px;line-height:1px;">&nbsp;</td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="background-color:#ffffff;padding:22px 32px 30px;">
            <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#2b2b2b;">{{ closing_paragraph }}</p>
            <p style="margin:0;font-size:15px;font-weight:bold;color:#111111;">{{ full_name }}</p>
            <p style="margin:6px 0 0;font-size:13px;color:#777777;">{{ contact_email }} &middot; {{ phone }}</p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
HTML;
    }

    private function profile(): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="background-color:#eef1f0;padding:24px 0;font-family:Helvetica,Arial,sans-serif;">
  <tr>
    <td align="center">
      <table width="640" cellpadding="0" cellspacing="0" border="0" role="presentation" style="width:100%;max-width:640px;background-color:#ffffff;border-radius:12px;">
        <tr>
          <td style="background-color:#ffffff;padding:28px 30px 20px;border-bottom:2px solid {{ accent_soft }};">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
              <tr>
                <td width="76" valign="top" style="background-color:#ffffff;padding-right:18px;">
                  <img src="{{ photo_url }}" width="72" height="72" alt="Photo of {{ full_name }}" style="display:block;width:72px;height:72px;border-radius:36px;border:2px solid {{ accent }};" />
                </td>
                <td valign="top" style="background-color:#ffffff;">
                  <h1 style="margin:0;font-size:21px;line-height:1.25;color:#14211d;">{{ full_name }}</h1>
                  <p style="margin:5px 0 0;font-size:13px;color:{{ accent_dark }};">{{ headline }}</p>
                  <p style="margin:8px 0 0;font-size:12px;color:#7a8482;">{{ location }} &middot; {{ contact_email }} &middot; {{ phone }}</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="background-color:#ffffff;padding:26px 30px 6px;">
            <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#2b332f;">Dear {{ recipient_name }},</p>
            <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#2b332f;">{{ intro_paragraph }}</p>
            <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#2b332f;">With {{ years_value }} years in the field, I believe I would do well as {{ position }} at {{ company }}.</p>
          </td>
        </tr>
        <tr>
          <td style="background-color:#ffffff;padding:0 30px;">
            <p style="margin:0 0 10px;font-size:11px;letter-spacing:1px;text-transform:uppercase;color:{{ accent_dark }};">Selected skills</p>
            <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
              {{# skills }}
              <tr>
                <td style="background-color:#ffffff;padding:5px 0;font-size:14px;line-height:1.5;color:#2b332f;border-bottom:1px solid #eef1f0;">{{ . }}</td>
              </tr>
              {{/ skills }}
            </table>
          </td>
        </tr>
        <tr>
          <td style="background-color:#ffffff;padding:24px 30px 30px;">
            <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#2b332f;">{{ closing_paragraph }}</p>
            <table cellpadding="0" cellspacing="0" border="0" role="presentation">
              <tr>
                <td bgcolor="{{ accent }}" style="background-color:{{ accent }};border-radius:6px;">
                  <a href="{{ portfolio_url }}" style="display:inline-block;padding:11px 22px;font-size:14px;font-weight:bold;color:{{ accent_contrast }};text-decoration:none;">See my work</a>
                </td>
              </tr>
            </table>
            <p style="margin:20px 0 0;font-size:15px;font-weight:bold;color:#14211d;">{{ full_name }}</p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
HTML;
    }
}
