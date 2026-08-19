# ApplyMail

A SaaS that turns a CV and a job posting into a designed HTML application email, previewed exactly
as Gmail will render it, then copied into the user's own Gmail through the
[Insert and Send HTML with Gmail](https://chromewebstore.google.com/detail/insert-and-send-html-with/bcflbfdlpegakpncdgmejelcolhmfkjh)
Chrome extension.

Built on Laravel 13, Inertia v3, Vue 3 and Tailwind 4.

## Why the extension, and not sending from the server

An application sent from our server arrives with our domain in the headers, often lands in spam,
and sends replies somewhere the applicant does not read. Copying the HTML into the user's own Gmail
keeps the message on their real address, puts the thread in their sent folder, and routes replies
straight back to them. The app's job is to produce the HTML and show it exactly as it will look.

## The user flow

1. **Register** — standard Fortify auth (email/password, 2FA and passkeys already in the starter kit).
2. **Profile** (`/profile/setup`) — name, headline, contact, photo, and a CV upload. Text is extracted
   from PDF and DOCX server-side and shown in an editable box; a scanned CV yields nothing, so the
   user is prompted to paste the text instead. That text is the only thing the AI reads.
3. **Choose a template** (`/templates`) — each layout is admin-authored HTML.
4. **Fill it in** (`/applications/{id}/edit`) — two modes on the same screen:
   - **Manual**: type into fields derived from the template's tokens.
   - **AI**: paste the job posting and DeepSeek writes each field from the CV. Everything it writes
     stays editable, and it also extracts the company, role and hiring contact from the posting.
5. **Preview** — a Gmail reading-pane mock (subject, sender row, attachment chip, desktop/mobile
   toggle) with the email in a sandboxed iframe so the app's own theme cannot leak in.
6. **Copy HTML** or **Download .html**, paste into the extension, attach the CV, send.

## The admin CMS (`/admin`)

- **Overview** — signups, applications, AI runs, token spend, and a list of accounts past the daily
  generation threshold.
- **Users** — search and filter (heavy AI use, suspended, admins), suspend with a reason, reinstate,
  and set a per-user monthly AI allowance (0 blocks AI while leaving the account usable). A suspended
  user is signed out on their next request and shown the reason at login.
- **Templates** — paste email HTML; tokens are detected as you type and the user's form is built from
  them. Includes a Gmail compatibility linter and a live sample render.
- **AI usage** — every DeepSeek call with tokens, duration, IP and error text.

## Template syntax

Templates are plain HTML with two constructs:

| Syntax | Meaning |
| --- | --- |
| `{{ token }}` | A single value |
| `{{# token }}…{{/ token }}` | A block repeated once per list item, with `{{ . }}` as the item |

Tokens resolve in three ways:

- **Profile** — `full_name`, `headline`, `contact_email`, `phone`, `phone_link` (a `wa.me` link built
  from an Indonesian mobile number), `location`, `portfolio_url`, `linkedin_url`, `photo_url`,
  `cv_url`, `cv_filename`, `today`. Never shown as form inputs.
- **Application** — `recipient_name`, `company`, `position`. Typed by the user; the AI fills blanks
  but never overwrites what was typed.
- **Template** — `accent`, the template's accent colour.

Everything else becomes a form field. Type is inferred from the name (`*_paragraph` → textarea,
`*_url` → URL, block tokens → list) and the admin can override the label, type, helper text and
whether the AI writes it. Editing the HTML re-syncs the schema: new tokens appear with sensible
defaults, removed tokens drop out, and admin customisations survive.

Every user-supplied value is escaped before it reaches the HTML, and URL-typed values are rejected
unless their scheme is `http`, `https`, `mailto` or `tel` — so a pasted `javascript:` payload cannot
reach an `href` or `src`.

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build      # or: composer dev
```

Seeding creates the admin account from `ADMIN_EMAIL`/`ADMIN_PASSWORD` and three starter templates
(Grayscale Accent, Clean Letter, Split Profile). Promote an existing account instead with:

```bash
php artisan user:make-admin you@example.com
php artisan user:make-admin you@example.com --demote
```

### Environment

| Variable | Purpose |
| --- | --- |
| `DEEPSEEK_API_KEY` | AI drafting. Without it the AI panel returns a clear error and manual mode still works. |
| `DEEPSEEK_MODEL` | Defaults to `deepseek-chat`. |
| `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY`, `CLOUDINARY_API_SECRET` | Photo and CV storage. All three are required; otherwise uploads fall back to the local `public` disk, which email clients cannot load images from. |
| `AI_DAILY_LIMIT`, `AI_MONTHLY_LIMIT` | Free-tier allowance. The stricter window wins. |
| `AI_RATE_LIMIT_PER_MINUTE` | Per-user burst guard on the generate endpoint. |
| `AI_ABUSE_THRESHOLD_PER_DAY` | Where the admin abuse watch starts flagging accounts. |

### Cloudinary notes

PDFs upload as `resource_type: image` (so they get thumbnail transforms); `.doc`/`.docx` go up as
`raw`. Newer Cloudinary accounts block PDF delivery by default — enable
**Settings → Security → Allow delivery of PDF and ZIP files**, or serve the file through a signed
Laravel route instead.

## Quotas and abuse handling

- Generations are counted per calendar day and per calendar month; only successful calls count, so a
  DeepSeek outage never eats a user's allowance.
- A per-minute rate limiter guards against runaway clients.
- Every call is written to `ai_generations` with tokens, duration and IP, whether it succeeded or not.
- Admins can cap an individual account or suspend it outright.

## Checks

```bash
php artisan test        # 112 tests
./vendor/bin/phpstan analyse
./vendor/bin/pint
npm run lint && npm run types:check && npm run build
```
