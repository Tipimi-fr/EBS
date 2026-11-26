# Error tracking with Sentry

Sentry is wired in the back (`sentry/sentry-symfony`, `config/packages/sentry.yaml`) and in the
browser (`@sentry/browser`, `assets/sentry.js`, imported by `assets/app.js`).

**It is fully inactive as long as the DSN variables are empty or absent** (local dev, CI, tests):
no event is sent and nothing is initialized. The DSNs are never committed (the repository is
public): they are provided through environment variables only.

## Environment variables

| Variable | Side | Purpose |
|---|---|---|
| `SENTRY_DSN` | back | DSN of the Sentry project `tipimi-ebs` (php-symfony). Empty = inactive. |
| `SENTRY_ENVIRONMENT` | back | Defaults to `APP_ENV`. Helm sets `nonprod` / `production`. |
| `SENTRY_RELEASE` | back + front | Optional. Helm sets it to the image tag (`nonprod-<sha>`, `prod-<sha>`). |
| `SENTRY_TRACES_SAMPLE_RATE` | back | Performance traces sample rate, `0` by default. |
| `SENTRY_JS_DSN` | front | DSN of the Sentry project `tipimi-ebs-js` (browser JavaScript). Empty = inactive. |
| `SENTRY_JS_ENVIRONMENT` | front | Defaults to `APP_ENV`. Helm sets `nonprod` / `production`. |

All of them are read at runtime (no rebuild of the assets needed): the front DSN is rendered by the
Twig layout in `<meta name="sentry-dsn">`.

On Kubernetes, `SENTRY_DSN` and `SENTRY_JS_DSN` are read (optionally) from the `external-secrets`
Secret; `SENTRY_ENVIRONMENT` / `SENTRY_JS_ENVIRONMENT` come from `php.sentry.environment` in the
Helm values.

## Privacy

`send_default_pii` is `false` on both sides, request bodies are not sent (`max_request_body_size:
none`), browser breadcrumbs, tracing and session replay are disabled. 4xx HTTP and security
exceptions (not found, access denied, bad request...) are ignored in the back.

## Trying it locally

    SENTRY_DSN=https://key@o0.ingest.de.sentry.io/0 make sf c="sentry:test"

Use a real DSN to see the event in Sentry, or a fake one to check that the client tries to send it.
