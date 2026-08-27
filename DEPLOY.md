# Deploying ApplyMail

The stack is two containers: the app (nginx + PHP-FPM behind supervisor) and MySQL 8.
Your existing reverse proxy terminates TLS and forwards to the app's published port.

Everything below has been run end to end against this image — build, first boot,
migrations, seeding, a real registration POST, saving a hand-written template, and a
redeploy.

Two ways to deploy the same `compose.yaml`:

- **[Portainer](#deploying-with-portainer)** — pull a published image, set the variables
  in the stack UI.
- **[Plain `docker compose`](#deploying-with-docker-compose)** — on any host with Docker,
  using `deploy.env`. Jump to that section.

---

## Deploying with docker compose

```bash
git clone <this repo> && cd email-html-template
cp deploy.env.example deploy.env
```

Fill in everything under **Required** in `deploy.env`. For `APP_KEY`, build first and
then generate one:

```bash
docker compose --env-file deploy.env -f compose.yaml -f compose.build.yaml build
docker run --rm applymail:local php artisan key:generate --show
```

Then bring it up — building the image on this host:

```bash
docker compose --env-file deploy.env \
    -f compose.yaml -f compose.build.yaml up -d --build
```

...or pulling a published one, with `APP_IMAGE` pointing at your registry:

```bash
docker compose --env-file deploy.env up -d
```

**Always pass `--env-file deploy.env`.** Compose otherwise reads `./.env`, which in this
repo is Laravel's *development* environment file — you would deploy with its `APP_KEY`
and `APP_URL` and not be told. `deploy.env` is gitignored and excluded from the image.

Watch the first boot, which takes ~30s:

```bash
docker compose --env-file deploy.env logs -f app
```

It waits for MySQL, migrates, seeds the four starter templates and the admin account,
then caches config, routes and views, and logs `[entrypoint] Ready`. Everything after
that is [step 4](#4-point-your-reverse-proxy-at-it).

> Building on the host needs Composer, Node and a PHP CLI — budget ~2 GB of RAM and a
> few minutes. On a small VPS, build in CI and pull the result instead.

Day to day:

```bash
docker compose --env-file deploy.env pull && \
docker compose --env-file deploy.env up -d      # update to a new published image
docker compose --env-file deploy.env ps         # status
docker compose --env-file deploy.env down       # stop, keeping the volumes
```

---

## Deploying with Portainer

Steps 1 to 3 are Portainer-specific. Step 4 onwards applies to both paths.

## 1. Publish the image

`.github/workflows/image.yml` builds and pushes to GHCR after the `tests` workflow
passes on `main`. No secrets to add — it uses the built-in `GITHUB_TOKEN`.

The image lands at:

```
ghcr.io/<your-github-user>/<repo>:latest
```

**Make the package readable by Portainer.** New GHCR packages are private. Either:

- **Public** — GitHub → your profile → Packages → the package → Package settings →
  Change visibility → Public. Portainer then pulls with no credentials.
- **Private** — Portainer → Registries → Add registry → Custom, URL `ghcr.io`,
  username your GitHub handle, password a
  [classic PAT](https://github.com/settings/tokens) with the `read:packages` scope.

> Building on an ARM host (Ampere, Graviton, a Pi)? The workflow builds `linux/amd64`
> only. Add `linux/arm64` to the `platforms:` line — emulated builds take noticeably
> longer.

---

## 2. Generate an `APP_KEY`

Once, on any machine with Docker. Laravel refuses to boot without it, and changing it
later invalidates every session and every encrypted column:

```bash
docker run --rm ghcr.io/<you>/<repo>:latest php artisan key:generate --show
```

Copy the whole `base64:...` string.

---

## 3. Create the stack

Portainer → **Stacks** → **Add stack** → **Repository**, pointed at this repo with
compose path `compose.yaml`. (Or **Web editor** and paste the file.)

Then fill in **Environment variables**. The app fails fast with a named error if a
required one is missing, rather than booting into a 500 page.

### Required

| Variable | Notes |
| --- | --- |
| `APP_IMAGE` | `ghcr.io/<you>/<repo>:latest` |
| `APP_KEY` | From step 2, including the `base64:` prefix |
| `APP_URL` | Public HTTPS address, e.g. `https://apply.example.com`. Password-reset links are built from this |
| `DB_PASSWORD` | Application database user password |
| `DB_ROOT_PASSWORD` | MySQL root password |
| `MAIL_HOST` | SMTP host — see the note in step 6 |
| `MAIL_FROM_ADDRESS` | The From address on outbound mail |
| `ADMIN_EMAIL` | Admin account created on first boot |
| `ADMIN_PASSWORD` | Change it after the first login |

### Recommended

| Variable | Default | Notes |
| --- | --- | --- |
| `APP_PORT` | `8080` | Host port your proxy forwards to |
| `DEEPSEEK_API_KEY` | — | Without it, AI drafting returns a clear error and manual mode still works |
| `CLOUDINARY_CLOUD_NAME` / `CLOUDINARY_API_KEY` / `CLOUDINARY_API_SECRET` | — | **All three or none.** Missing any one silently falls back to storing uploads on the local volume, which email clients cannot load photos from |
| `MAIL_USERNAME` / `MAIL_PASSWORD` / `MAIL_PORT` / `MAIL_SCHEME` | `587` / `tls` | SMTP credentials |
| `CREDIT_MONTHLY_GRANT` | `300` | Credits every account is topped up to each month |
| `CREDIT_PRICE_APPLICATION_DRAFT` | `10` | Cost of one AI application draft |
| `CREDIT_PRICE_TEMPLATE_DESIGN` | `30` | Cost of designing one template **with AI**. Writing one by hand is always free and is not priced |
| `CREDIT_PROMOTION_REWARD` | `50` | Paid to an author when their design is published |
| `DEEPSEEK_TIMEOUT` | `120` | Seconds one generation may hold an FPM worker for |
| `PASSKEYS_USER_HANDLE_SECRET` | `APP_KEY` | Set it explicitly if you ever intend to rotate `APP_KEY` — otherwise rotating orphans every registered passkey |
| `SESSION_SECURE_COOKIE` | `true` | Only set `false` to smoke-test over plain `http://`; while it is `true` the browser will not send the session cookie back and every login silently fails |
| `LOG_LEVEL` | `warning` | `debug` while bringing it up |
| `RUN_QUEUE_WORKER` / `RUN_SCHEDULER` | `false` | Nothing is queued or scheduled today; flip if that changes |

Deploy. First boot takes ~30s: it waits for MySQL, migrates, seeds the four starter
templates and the admin account, then caches config, routes and views.

---

## 4. Point your reverse proxy at it

Forward to the app container on port `80` (or the host's `APP_PORT`). The two headers
that matter:

```
X-Forwarded-Proto  https
X-Forwarded-Host   apply.example.com
```

The app trusts forwarded headers from any source, which is safe because the container
is only reachable through your proxy on the stack's internal network. **Do not publish
`APP_PORT` to the public internet** — bind it to localhost instead if the proxy runs on
the same host:

```yaml
ports:
  - "127.0.0.1:${APP_PORT:-8080}:80"
```

Using Traefik? Uncomment the labels and the `proxy` network at the bottom of
`compose.yaml` and delete the `ports:` block.

**TLS is not optional.** Passkeys and 2FA use WebAuthn, which browsers refuse to run on
an insecure origin.

---

## 5. Verify

```bash
curl -I https://apply.example.com/up          # 200
curl -s https://apply.example.com/ | head     # landing page
```

Then log in as `ADMIN_EMAIL`, change the password, and check **Admin → Templates**
lists four templates.

Templates → **Design your own** should offer both routes: *Generate with AI* (priced in
credits) and *Make your own* (free). The second opens the HTML builder with a live
preview, three worked examples and the full guide — it needs no `DEEPSEEK_API_KEY`, so
it is the quickest way to confirm a fresh deployment renders and saves correctly.

To promote your own account instead of using the seeded one:

```bash
docker exec <app-container> php artisan user:make-admin you@example.com
```

---

## 6. About email

Fortify's `emailVerification()` feature is enabled in config, **but the `User` model does
not implement `MustVerifyEmail`**, so it is currently inert — new users register and reach
the dashboard without confirming their address. Verified by test on the running stack.

That means SMTP is **not** a launch blocker. It is still needed for **password resets**,
which fail without it.

If you want verification enforced, add the contract to `app/Models/User.php`:

```php
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements PasskeyUser, MustVerifyEmail
```

Configure SMTP first — otherwise every new signup stalls on the verify screen.

---

## Operations

**Update to a new release.** Push to `main`, wait for the workflow, then in Portainer:
Stack → **Update the stack** → tick **Re-pull image**. The entrypoint re-runs migrations
and rebuilds caches. It will not re-seed or overwrite templates an admin has edited —
re-seeding only happens into an empty table.

**Back up.** The database holds everything except uploaded files:

```bash
docker exec <mysql-container> mysqldump -u root -p"$DB_ROOT_PASSWORD" --single-transaction applymail > applymail-$(date +%F).sql
```

With Cloudinary configured, files live there. Without it, also back up the
`app-storage` volume.

**Logs.** Everything goes to stdout/stderr, so `docker logs` and Portainer's log view
work normally. No log files accumulate inside the container.

**One-off commands.**

```bash
docker exec <app-container> php artisan tinker
docker exec <app-container> php artisan app:provision --force-templates   # re-seed starter templates, discarding CMS edits to them
```

---

## Things worth knowing

**A single AI request can hold a worker for four minutes.** The DeepSeek call is given
120s and retried once. nginx, PHP and FPM are all configured to allow ~310s so a slow
call returns an answer instead of a 504. The pool runs 24 workers, so a burst of stuck
generations eats into capacity for ordinary page loads. If that ever bites, lower
`DEEPSEEK_TIMEOUT` or move generation onto the queue.

**Uploads are capped in three places.** Laravel validates at 8 MB (CV) and 4 MB (photo);
PHP accepts 16 MB and nginx 16 MB. The infrastructure limits sit deliberately above the
app's so an oversized file produces a proper validation message rather than a bare 413.

**Config is cached at boot, not at build.** Every setting comes from the environment
Portainer injects, so changing a variable requires restarting the container — editing it
in Portainer and redeploying is enough.

**Scaling to more than one app container** needs `CACHE_STORE` and `SESSION_DRIVER` moved
to Redis, and Cloudinary configured so uploads are not tied to one host's volume.
