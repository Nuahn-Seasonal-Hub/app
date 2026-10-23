# Booking backend: Snow Removal and Mobile Mechanics

This document describes the live, database-backed booking flows for the two launched services. The other four
services stay on the Coming Soon gate (`fixtures/services.php`), unchanged.

Code map:

| Area | Files |
|---|---|
| Schema | `database/migrations.sql` (idempotent, runs on every deploy / container start) |
| Domain logic | `includes/marketplace/{config,core,jobs,quotes,payments,photos,ops,view}.php` |
| One POST router | `actions/marketplace.php` → handlers in `includes/marketplace/handlers.php` |
| Pages | `public/{post_task,offers,pay,track,approve,bookings,open_jobs,quote,mechanics,mechanic_request,updates,contractor_setup,contractor,provider_home,provider_dashboard,client_home,operations}.php` |
| Files | `public/media.php` (authorised photo/document streaming), `uploads/jobs/` (outside any public URL, deny-all `.htaccess`) |
| Payments | `includes/marketplace/payments.php`, `public/pay.php`, `public/assets/pay.js`, `public/stripe_webhook.php` |
| Cron | `scripts/auto_release.php` |
| Tests | `tests/stripe_provider_test.php` (`php tests/stripe_provider_test.php`), `tests/e2e/` (HTTP end-to-end run, section 11) |

Everything money-related is CAD, `DECIMAL(10,2)`.

## 1. Tables

Existing legacy tables (`users`, `jobs`, `applications`, ...) are MyISAM and untouched. All new tables are InnoDB with
real foreign keys **between new tables only** (a foreign key to a MyISAM `users` table is impossible; ownership of
`user_id` columns is enforced in PHP and by indices).

| Table | Purpose |
|---|---|
| `contractor_profiles` | One row per provider: trades, vetting status (`draft, pending, in_review, changes_requested, approved, rejected`), four per-check states (identity, insurance, licence, certification: `missing/review/ok/rejected/na`), base address + lat/lng, service radius, online flag, headline. Only `approved` providers may offer or quote. |
| `contractor_documents` | Uploaded ID / insurance / licence / certificate files (private; only reviewers can open them via `media.php?d=`). |
| `tasks` | The job. `service` (`snow-removal`, `mobile-mechanics`), `mode` (`on_demand, seasonal, roadside, scheduled`), `status` (job state, section 2), `payment_status` (mirror of `payments.state`), address + `lat/lng`, `details` JSON (driveway size, add-ons, vehicle, extras), budget, boost, `agreed_amount`, tax, `contractor_id`, `parent_id` (seasonal plan → visit), timestamps, auto-release deadline. |
| `task_offers` | Offers and counter-bids from contractors (`accept` at the client's price, `counter`, `quote`). Status `sent/accepted/declined/withdrawn/expired`. One live offer per contractor per task. |
| `job_photos` | before / after / dispute / issue photos. Stored name is random; the original name is never used. |
| `payments` | One per task: provider (`sandbox`/`stripe`), `processor_ref` (a token only), state, subtotal, tax, total, commission %, commission amount, payout amount, payout status (`pending/paid`), payout reference. |
| `payment_events` | Append-only ledger of every payment action with an idempotency key. |
| `webhook_events` | Processed Stripe event ids (replay protection). |
| `quotes`, `quote_items` | Itemised mechanic quotes: parts / labour / fee lines, GST/PST, scope, includes, excludes, available windows, validity. A `revision` quote holds only the extra items found on site. |
| `disputes` | Client-opened dispute: reason, details, wanted outcome, status, resolution (`release/refund/split`), split share, staff note. Evidence photos are `job_photos` with kind `dispute`. |
| `reviews` | One review per task (`task_id` UNIQUE), after approval only. |
| `job_messages` | Per-task thread between the client and the contractors involved. |
| `job_notifications` | In-app notification feed (`public/updates.php`). **Email / SMS delivery is not built**, see section 8. |
| `snow_events` | A recorded snowfall (`event_key` UNIQUE, cm, area) used to create seasonal-plan visits. |
| `mk_audit_log` | Who did what: actor, role, action, entity, id, detail, IP, time. Shown in Operations. |

## 2. State machines

Job state (`tasks.status`) and payment state (`payments.state` / `tasks.payment_status`) are kept **separate**.

```
Snow (on demand):   open_for_bids → confirmed → on_the_way → arrived → in_progress → pending_approval → approved
Roadside:           searching → confirmed → on_the_way → arrived → in_progress → pending_approval → approved
                    searching → no_provider (nobody accepted inside MK_ROADSIDE_SEARCH_MIN)
Scheduled repair:   quote_pending → (quote sent, client approves) → confirmed → ... → approved
Revised quote:      in_progress → paused (client decides) → in_progress
Any open state:     → cancelled      pending_approval → disputed → approved | cancelled
```

Rules enforced on the server:

* Only a vetted (`approved`) contractor can send an offer, accept a roadside request or send a quote.
* A contractor cannot accept their own offer. Only the task's client accepts offers (`offer_accept`).
* `confirmed → on_the_way → arrived`, then **start requires at least one BEFORE photo**, and **submitting
  completion requires at least one BEFORE and one AFTER photo**. Both checks are in `includes/marketplace/jobs.php`, not in JS.
* Submitting completion starts the `MK_AUTO_RELEASE_HOURS` (48) clock. The client approves, opens a dispute, or the cron releases.
* Cancel rules: free while open or confirmed-but-not-on-the-way (the hold is released in full). After the contractor has left, the
  client reports a problem instead; the contractor can withdraw from a job (`job_withdraw`) and it returns to the board.
* A revised quote pauses the job. The extra work is only allowed after the client approves; approving raises the held amount.
* Seasonal plan: a parent task plus one child visit per `snow_events` row at or above `MK_SEASONAL_TRIGGER_CM` (5 cm). Each visit is held and approved on its own.

Payment state: `unpaid → authorized (escrow hold at acceptance) → captured → released`; or `authorized → refunded`;
`partially_refunded` for a dispute split; `disputed` while a dispute is open; `failed` when the card is declined.
Payout record at approval: `payout = subtotal − commission` where `commission = subtotal × MK_COMMISSION_PCT (12%)`,
computed on the pre-tax amount. Tax (`MK_GST_PCT`, `MK_PST_PCT`) is added on top for the client. The constants are in
`includes/marketplace/config.php`.

## 3. Endpoints

All state changes are `POST actions/marketplace.php` with `do=<action>` and a `_csrf` token (checked before anything else).
Order of checks: signed in → CSRF → bookings installed → action exists → role. Ownership checks are inside each handler.

| Role | `do` values |
|---|---|
| client | `snow_post`, `mech_request`, `offer_accept`, `task_boost`, `task_cancel`, `job_approve`, `dispute_open`, `review_add`, `quote_decide`, `revision_decide` |
| provider | `offer_send`, `offer_withdraw`, `roadside_accept`, `job_step` (`trip/arrived/start`), `photo_upload`, `job_submit`, `job_withdraw`, `quote_send`, `quote_revise`, `profile_save`, `doc_upload`, `profile_submit`, `online_toggle` |
| client + provider | `message_send`, `notif_read` |
| staff (manager / superadmin) | `vetting_decide`, `dispute_resolve`, `snow_event`, `payout_paid`, `nudge` |

Other endpoints: `public/stripe_webhook.php` (signature-verified, bypasses the launch gate), `public/media.php?p=` / `?d=` (photo / document
streaming after an access check), `scripts/auto_release.php` (CLI only).

Guests can open the estimate forms and fill them in; sign-in is asked only when pressing **Post task**, and the draft is restored afterwards.

## 4. Photos

`includes/marketplace/photos.php`: JPEG / PNG / WebP only, 10 MB each, at most 6 per kind. The file type is checked from the bytes
(not the filename or browser MIME), the image is **re-encoded with GD** (drops EXIF, GPS and any embedded payload, fixes orientation)
and saved under `uploads/jobs/<random>.jpg`. `uploads/jobs/.htaccess` denies direct access; files are served through `media.php` after an ownership check.

## 5. Payments

* `PaymentProvider` interface with `SandboxProvider` (default) and `StripeProvider`.
* **Sandbox**: no keys, no network, fake `sbx_` tokens, a "Test payments" label on every payment surface. A test card selector can force a declined card.
* **Stripe**: PaymentIntents with `capture_method=manual`. Card details are typed only into Stripe's hosted Payment Element (Stripe.js) on `pay.php`;
  raw card data never reaches our servers, logs or database; we store the PaymentIntent id. Capture on approval, cancel on cancellation, refund / partial refund on dispute resolution.
* **Webhook**: `public/stripe_webhook.php`, verifies the `Stripe-Signature` HMAC with a 5 minute tolerance, de-duplicates by event id.
  Events to enable: `payment_intent.amount_capturable_updated`, `payment_intent.payment_failed`, `payment_intent.canceled`, `charge.dispute.created`.
* **Supplying keys (never committed)**: environment variables `STRIPE_SECRET_KEY`, `STRIPE_PUBLISHABLE_KEY`, `STRIPE_WEBHOOK_SECRET`
  (and optionally `NUAHN_PAYMENTS=sandbox|stripe`), **or** copy `config/payments.example.php` to `config/payments.local.php` (gitignored).
  On Hostinger, keep `config/payments.local.php` out of any `rsync --delete`, or use environment variables.
  Webhook URL: `https://<host>/public/stripe_webhook.php`.
* **Decision**: when a client's accepted price rises after a revised quote, `StripeProvider::adjust` calls `/increment_authorization`, which Stripe only allows on
  accounts with extended or incremental authorisation enabled. Without it the increase fails, the revision is not approved, and the client is told to pay the extra separately or decline the extra work. Confirm with Stripe before launch, or decide on a different top-up approach.
* **Pending**: Stripe test keys are needed to exercise the real card path. Paying contractors out through Stripe Connect is not built; with Stripe the
  payout record stays `pending` until Operations marks it paid (`payout_paid`). In the sandbox, approval marks the payout released straight away.

## 6. Cron

```
*/10 * * * * /usr/bin/php /home/<account>/<app>/scripts/auto_release.php >> /home/<account>/auto_release.log 2>&1
```

Releases jobs the client left unanswered for 48 hours (capture + payout record), expires roadside searches nobody accepted, expires old quotes and clears
stale checkouts. It refuses to run outside the CLI and takes a file lock so two runs cannot overlap. Set `NUAHN_DB_NAME` if the database name differs from `config/db.php`.

## 7. Operations (`public/operations.php`, manager / superadmin)

Overview counts, open-jobs list with a "nudge contractors" action, **vetting queue** (per-check states, approve / ask for changes / reject with a reason, document links), **disputes queue**
(client evidence, resolve by refund / release / split), payouts to mark paid, snow events (recording one creates the plan visits), and a searchable audit log.
Every staff decision is written to `mk_audit_log`.

## 8. Pending / stubbed (decisions for the backend owner)

| Item | State |
|---|---|
| Email / SMS notifications | In-app feed only (`job_notifications`). Add a sender in `mk_notify()` (`includes/marketplace/core.php`). |
| Geocoding / map | Address text + manual lat/lng; the map picker is labelled "Sample map". Needs a geocoding key (Google / Mapbox) to turn addresses into pins. Distance matching uses the haversine formula on stored coordinates. |
| Stripe keys / Connect payouts | See section 5. |
| Live contractor GPS | Not built. The ETA shown is the contractor's own estimate. |
| Audio / video attachments | Photos only. |
| Weather feed | Snow events are recorded by staff (`operations.php?view=snow`); no automatic weather API. |
| Per-role legacy login/register forms and a few old POST forms | They predate the CSRF layer; fixture-only pages (`provider_route`, `activity`, `messages`, `change_appointment`, `snow_event`, `chat`) are untouched. |
| Province tax table | `MK_GST_PCT`=5, `MK_PST_PCT`=0 (Alberta). Change the constants or replace with a lookup. |

## 9. Demo seed data

`migrations.sql` seeds demo contractors (`*@demo.nuahn.test`, mixed vetting states), their profiles, reviews and a few open jobs so the board and offers screens are not empty.
**Because migrations run on every deploy, the seed also lands on the production database.** Remove the "demo seed" block at the end of `migrations.sql` before launch if that is not wanted.
Every demo account has the sample password `password123`; delete them before launch.

## 10. Deploy notes

* No new Hostinger step is required for the code: migrations run on deploy.
* Make `uploads/jobs/` writable by PHP (it is gitignored; the `.htaccess` inside is tracked).
* Add the cron entry above.
* Provide Stripe keys as environment variables, or place `config/payments.local.php` on the server and exclude it from `rsync --delete`.
* PHP needs the `gd`, `fileinfo`, `curl` (for Stripe) and `pdo_mysql` extensions.

## 11. Tests

* `php tests/stripe_provider_test.php`: Stripe provider against a mock transport plus a locally computed webhook HMAC (17 checks, no network, no keys).
* `tests/e2e/flows.py`: end-to-end over real HTTP against a local server and a scratch MariaDB database (needs Python 3 with `requests` and `Pillow`).
  Start the app with `NUAHN_DB_NAME=<scratch db> php -S 127.0.0.1:8091 -t .`, adjust `BASE` in `tests/e2e/lib.py` and the database credentials in `flows.py`, then run `python3 flows.py`.
  It covers the snow happy path, a dispute with a split, the mechanic roadside flow with a revised quote, a scheduled itemised quote with GST, cancellation, a declined card,
  seasonal plans and snow events, vetting, the cron script, and permission checks (another client's task, a contractor accepting their own offer, completion without photos,
  missing or bad CSRF token, an unvetted contractor offering, a bad webhook signature). It uses demo accounts, so run it only against a scratch database.
