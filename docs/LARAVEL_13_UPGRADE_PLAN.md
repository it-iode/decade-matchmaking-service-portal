# Laravel 13 + PHP 8.5 Upgrade Plan — Ocean Decade Portal

> Status: planned (2026-11-09). Target: Laravel 13.x (latest, 13.40+) on PHP 8.5.12+.
> Verified against the official docs: [Upgrade Guide](https://laravel.com/docs/13.x/upgrade), [Release Notes / Support Policy](https://laravel.com/docs/13.x/releases), and Packagist for every dependency.

## Context

The portal runs Laravel 12.12.0 (locked) with `php: ^8.2` and a Sail PHP 8.4 runtime; production runs PHP 8.4.x. Laravel 12 entered security-fixes-only on 2026-08-13. Requirement: move to the current Laravel release with compatibility for **PHP 8.5.12 at minimum** (8.5.12, released 2026-10-21, fixes critical RCEs CVE-2026-109618/109619).

## Verification (completed)

- **Laravel 13** is the current major (released 2026-03-17; bug fixes until Q3 2027, security fixes until 2028-03-17). Requires **PHP ≥ 8.3**, supports **PHP 8.3–8.5** → covers 8.5.12. ✅
- Laravel 12 also runs on PHP 8.5 (12.40+), but is past bug-fix EOL → target Laravel 13, not a 12.x patch bump.
- **Dependency audit:** every package has a Laravel 13-compatible release. Only two forced major bumps:

| Package | Locked | L13-compatible | Action |
|---|---|---|---|
| laravel/framework | 12.12.0 | 13.40.0 | constraint → `^13.0` |
| laravel/tinker | 2.10.1 | 3.0.2 | constraint → `^3.0` (major, per official guide) |
| phpunit/phpunit | 11.5.x | 12.x | constraint → `^12.0` (major) |
| inertiajs/inertia-laravel | 2.0.2 | **2.0.28 supports L13** | keep `^2.0` — do NOT jump to v3 |
| spatie/laravel-permission | 6.18.0 | **6.25.0 supports L13** | keep `^6.18` — do NOT jump to v8 |
| spatie/laravel-one-time-passwords | 1.0.8 | 1.1.2 (needs php ^8.4) | keep `^1.0` |
| therobfonz/laravel-mandrill-driver | 6.1.0 | 6.2.0 | keep `^6.1` |
| diglactic/laravel-breadcrumbs | 10.0.0 | 10.1.0 | keep `^10.0` |
| barryvdh/laravel-dompdf | 3.1.1 | 3.1.2 | keep `^3.1` |
| sanctum / socialite / telescope / log-viewer / ziggy / breeze / pail / sail / larastan / collision / pint / mockery | — | all in-constraint | `composer update` |

- **Sail**: locked 1.42 has no 8.5 runtime; latest (1.69) ships `runtimes/8.5`. `docker-compose.yml` currently builds `runtimes/8.4`.

## Codebase scan findings

**Real breakers (must fix):**
1. `app/Infrastructure/Email/Services/TemplateResolver.php:31` — `Cache::remember()` stores an `EmailTemplate` object. Under Laravel 13's new `serializable_classes => false` cache default, the `catch (\Throwable)` fallback silently disables template caching.
2. `app/Domains/Notification/Console/SendWeeklyNewsletter.php:67` — caches `now()` (a Carbon object), read back as an object at lines 43-46/102 and in `NewsletterService.php:223`. Breaks under the new default.
3. ~104 docblock `@test` / `@dataProvider` annotations across ~15 test files — removed in PHPUnit 12.

**Deploy-sensitive:**
- No `CACHE_PREFIX` env pin; the framework fallback format changes in L13 (`<app>_cache_` → `<app>-cache-`) → database cache goes cold and the `newsletter:last_send_date` key is lost unless pinned.
- `config/session.php` has no `serialization` key; session content is scalars-only (verified), so adopting the skeleton's `json` value is safe but invalidates active sessions once.
- `QUEUE_CONNECTION=database` with 11 queued listeners + the `SendTransactionalEmail` job; workers are spawned by the scheduler (`queue:work` every minute, `otp-mail` every 5 s). The `jobs` table must be drained before deploying a major framework bump (internal class renames break in-flight payload unserialization).

**Confirmed NOT affected:** no `VerifyCsrfToken` references, no `upsert`, no DELETE+JOIN+ORDER/LIMIT, no `JobAttempted`/`QueueBusy` listeners, no `array_first`/`array_last` helpers, no custom `Str` factories, no pagination view references, no custom cache stores / queue drivers / dispatchers, no models instantiated in `boot()`, `Notification::extend()` closures are `$this`-free, and **zero PHP 8.5 deprecation hits** (no implicit nullables, dynamic properties, or legacy casts) — the app code is already PHP 8.5-clean.

## Decisions (confirmed)

1. **Sessions**: adopt Laravel 13 hardened defaults (`serialization => 'json'`, new cookie-name fallback). One-time forced re-login at deploy is accepted. No legacy `SESSION_COOKIE` pin.
2. **PHPUnit**: bump to `^12.0` and migrate all docblock annotations to attributes in this upgrade.
3. **Cache breakers**: refactor to scalars; keep `serializable_classes => false` (no allow-list).

## Implementation

Branch `upgrade/laravel-13-php85`, 4 commits — each phase independently verifiable and revertable. All composer/artisan runs via Sail.

### Phase 0 — Baseline (no changes)
- `sail artisan test` → record exact test/assertion counts. **This is the regression gate**: PHPUnit 12 silently *skips* `@test`-annotated methods instead of failing, so count-matching is the only reliable check.
- `sail artisan tinker --execute="echo config('cache.prefix');"` → record the exact current prefix.
- `git tag pre-l13-upgrade`.

### Phase 1 — Pre-upgrade hardening (still L12 / PHP 8.4 / PHPUnit 11) — commit 1
1. **TemplateResolver**: move `EmailTemplate::fromConfig()` *outside* the `Cache::remember` closure — cache the raw config **array**, construct the object after retrieval. Behavior identical; nothing but scalars/arrays in cache.
2. **SendWeeklyNewsletter**: store `now()->toIso8601String()`; on every read (command lines 43-46/102, `NewsletterService.php:223`) use a tolerant parse (`$v instanceof CarbonInterface ? $v : Carbon::parse($v)`) so the pre-deploy cached Carbon object (14-day TTL — it will exist at upgrade time) cannot fatal.
3. **Env pin**: set `CACHE_PREFIX` in `.env` + `.env.example` + production env to the exact Phase 0 value (keeps the DB cache warm and preserves `newsletter:last_send_date` across the prefix-format change).
4. **Test annotation migration** (~15 files): `/** @test */` → `#[Test]` (mechanical); `@dataProvider x` → `#[DataProvider('x')]` per-file (docblocks carry prose that must survive); add `use PHPUnit\Framework\Attributes\{Test,DataProvider};`; run `sail pint`. Attributes work on PHPUnit 10+, so this is fully verified on the current stack. Gate: `grep -rn "@test\|@dataProvider" tests/` → 0 hits; suite count == baseline.
5. **New serialization-regression test**: the existing `TemplateResolverTest` runs on the array store (never serializes) and cannot catch breaker 1. Add a Feature test forcing `cache.default => database` that round-trips template resolution and the newsletter timestamp. It permanently guards the "no objects in cache" invariant.

Gate: full suite green at baseline count on the untouched L12/8.4 stack. This commit is independently deployable and must never be rolled back.

### Phase 2 — Sail + PHP 8.5 runtime — commit 2
1. `sail composer update laravel/sail --with-all-dependencies` (inside the current 8.4 container; `^1.41` reaches 1.69 which ships `runtimes/8.5`).
2. `sail down`; edit `docker-compose.yml`: context → `./vendor/laravel/sail/runtimes/8.5`, image → `sail-8.5/app`.
3. `sail build --no-cache && sail up -d`; verify `sail php -v` ≥ 8.5.12 and `sail artisan --version` boots (brief L12-on-8.5 transit state — supported by both sides; don't linger).

### Phase 3 — Laravel 13 bump + config sync — commit 3
1. `composer.json`:
   ```json
   "php": "^8.5",
   "laravel/framework": "^13.0",
   "laravel/tinker": "^3.0",
   "phpunit/phpunit": "^12.0"
   ```
   Nothing else changes. `php: ^8.5` also makes any accidental host-side (PHP 8.3) `composer install` fail the platform check instead of producing a wrong lock.
2. `sail composer update --with-all-dependencies` inside the 8.5 container — no `--ignore-platform-reqs` needed anywhere. Watch `package:discover` output: this is the first Laravel 13 boot.
3. Config sync against the skeleton diff (<https://github.com/laravel/laravel/compare/12.x...13.x>):
   - `config/cache.php`: add `'serializable_classes' => false`.
   - `config/session.php`: add `'serialization' => 'json'`.
   - `.env.example`: keep `CACHE_PREFIX=` pinned.
   - Apply only additive skeleton keys elsewhere (no middleware/bootstrap customizations are affected).
4. Gate: `sail artisan about` / `route:list` succeed; `sail artisan test` green at **exactly** the baseline count (silent-skip check).

### Phase 4 — Verification (no commit)
- `sail artisan test` == baseline count; `sail pint --test`.
- Larastan: add a minimal `phpstan.neon` (level 2, paths: `app`) — none exists today — and run `sail php vendor/bin/phpstan analyse` to catch removed/renamed framework APIs.
- `composer dev` smoke: serve + queue:listen + pail + vite all stay up.
- Manual flows, each targeting a specific risk:
  - **OTP login** — one-time-passwords 1.1.2 + `otp-mail` queue + Mandrill driver (against Mailpit).
  - **Transactional email** — TemplateResolver path; inspect `cache` table rows: no `O:` object markers in app keys.
  - **Newsletter** — `newsletter:send-weekly --dry-run` → `--force` → `--dry-run` (full ISO-string round trip).
  - **PDF export** (dompdf), **Telescope** and **log-viewer** load, **tinker 3** opens, login/logout round-trip (json session serialization).

### Phase 5 — Deploy artifacts + runbook — commit 4
- `deploy-test/Dockerfile`: `php8.4-*` → `php8.5-*`, `update-alternatives --set php /usr/bin/php8.5`, update the header comment.
- **Ops prerequisite**: production must have PHP 8.5.12+ (ondrej PPA) installed before the deploy that ships the new lock. Keep `php8.4-*` installed side-by-side for one release cycle for rollback.
- **Deploy runbook (ordered)**:
  1. `php artisan down`.
  2. Disable the scheduler cron (it respawns queue workers every minute / 5 s); let running workers finish.
  3. Drain: `SELECT COUNT(*) FROM jobs;` must be 0 (`queue:work --stop-when-empty` if not). Triage `failed_jobs`: `queue:retry` what matters now, `queue:flush` the rest — stale payloads are unretryable post-upgrade.
  4. Deploy code, `composer install`, `php artisan migrate --force` (none expected), `config:cache` / `route:cache` / `view:cache`, `queue:restart`.
  5. Re-enable the scheduler cron; `php artisan up`.
  6. Post-checks: OTP login end-to-end, one queued job processed within a minute, Telescope recording, `newsletter:send-weekly --dry-run` shows a sane "Last sent". Avoid deploying Monday morning just before the 11:00 newsletter run.
- **Rollback**: revert the merge + `composer install` requires switching prod back to PHP 8.4 in the same step (the new lock demands ^8.5): `update-alternatives` back to 8.4 + fpm switch + `composer install` + `config:cache` + `queue:restart`. Re-drain queues first (same payload-compat issue in reverse). Phase 1's commit is L12-compatible — never roll it back. Local: `docker-compose.yml` back to `runtimes/8.4` + rebuild. No DB migrations → no data rollback. The json-session switch invalidates sessions once more on rollback (same blast radius as forward).

## Official upgrade-guide completeness cross-check

Every item from <https://laravel.com/docs/13.x/upgrade>, mapped:

| Guide item | Impact | Status |
|---|---|---|
| Update deps: framework ^13, tinker ^3, phpunit ^12 | High | Phase 3 ✅ (laravel/boost, pest, Laravel installer: not used — n/a) |
| Request forgery: `VerifyCsrfToken` → `PreventRequestForgery` + Sec-Fetch-Site origin check | High | No references in code ✅; `routes/api.php` bypasses web middleware (unaffected); any cross-origin POST to web routes verified in Phase 4 smoke |
| Cache `serializable_classes` | Medium | Phase 1 refactors both object-caching sites; Phase 3 adds the key = `false` ✅ |
| `upsert` empty `uniqueBy` throws (MySQL) | Medium | No `upsert` calls ✅ |
| Cache prefix / session cookie fallback format | Low | `CACHE_PREFIX` pinned (Phase 1); cookie-name change accepted (decision 1) ✅ |
| Collection serialization restores eager-loaded relations | Low | Queued listeners carry events with single models; behavior-improving change — verify-only ✅ |
| `Container::call` nullable defaults | Low | No `app()->call()` / callable-array patterns found ✅ |
| Domain route precedence | Low | No domain routes ✅ |
| `JobAttempted` / `QueueBusy` event changes | Low | No listeners for either ✅ |
| Manager `extend` callback binding | Low | `Notification::extend` closures are `$this`-free (AppServiceProvider:107,111) ✅ |
| MySQL DELETE + JOIN + ORDER/LIMIT | Low | No such queries ✅ |
| Pagination bootstrap view names | Low | Not referenced ✅ |
| Polymorphic pivot table names | Low | No custom pivot classes ✅ |
| Session `serialization` → json | Low | Decision 1: adopt json; session content scalars-only (verified) ✅ |
| `Str` factories reset between tests | Low | None used ✅ |
| symfony/polyfill-php85 `array_first`/`array_last` | Low | No conflicting helpers; polyfill inert on PHP 8.5 anyway ✅ |
| Contracts: Cache `touch`, Dispatcher `dispatchAfterResponse`, ResponseFactory `eventStream`, Queue size methods, MustVerifyEmail `markEmailAsUnverified`, HTTP client `throw` signatures | Very low | No custom implementations of any of these contracts; `User` does not implement `MustVerifyEmail` ✅ |
| Model booting nested instantiation | Very low | Only boot hook is `Opportunity::booted()` setting a ULID — creates no models ✅ |
| Password-reset subject change | Very low | No assertions/overrides reference it ✅ |
| Queued notifications + missing models | Very low | No queued Notification classes ✅ |
| `withScheduling` timing | Very low | Schedules live in `routes/console.php` via the facade — unaffected ✅ |
| `Js::from` unescaped unicode | Very low | Not used ✅ |
| Skeleton sync (laravel/laravel 12.x…13.x diff) | — | Phase 3 step 3 ✅ |

## Regression-safety summary

- Regression gate at every phase = exact test-count match against the Phase 0 baseline (defeats PHPUnit 12's silent annotation skips).
- The new serialization-regression Feature test guards the `serializable_classes => false` invariant permanently.
- Phase 1 is deployable on Laravel 12 — the upgrade can pause safely after it.
- Manual smoke list covers every third-party package with a behavior surface (OTP, Mandrill, dompdf, Telescope, log-viewer, Inertia, tinker).
- Queue drain + side-by-side PHP 8.4/8.5 on prod give a clean rollback path.