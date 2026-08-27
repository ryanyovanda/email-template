# Deploying ApplyMail with Portainer

The stack is two containers: the app (nginx + PHP-FPM behind supervisor) and MySQL 8.
Your existing reverse proxy terminates TLS and forwards to the app's published port.

Everything below has been run end to end against this image — build, first boot,
migrations, seeding, a real registration POST, and a redeploy.

---

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
| `CREDIT_PRICE_TEMPLATE_DESIGN` | `30` | Cost of designing one template |
| `CREDIT_PROMOTION_REWARD` | `50` | Paid to an author when their design is published |
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
