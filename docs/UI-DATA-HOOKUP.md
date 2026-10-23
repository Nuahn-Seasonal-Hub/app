# UI ↔ data hook-up notes (client design v2)

> **Update:** Snow Removal and Mobile Mechanics are now live, database-backed flows. Sections about tracking, the provider dashboard, operations, the mechanic screens and the contractor model describe the original sample-data shapes; the live implementation is in [`BOOKING-BACKEND.md`](BOOKING-BACKEND.md). The other four services still use the sample data described here.

**Who owns what:** Christo Innocent owns the UI. **Sir Paul builds the functionality.**
Every screen below is already built and clickable. Where there is no backend yet, the screen reads
**sample data from one fixture file per feature** in `/fixtures/*.php`, loaded with `nu_fixture('<name>')`
(`includes/ui.php`). These screens show a yellow dashed **"Sample data"** tag so nobody mistakes them for live data.

**Hooking a feature up:** replace the `nu_fixture('x')` call in the page with your query or API call, and return the same array shape.
Then delete the fixture file and the sample tag. No page markup has to change.
**No database schema was changed for these features.** The tables they need are listed per feature, for you to design.

Existing, real features are untouched and still hit the DB: auth/login/register, jobs (post, browse, map, save, apply), applications,
my jobs, profile, and all admin/manager pages (dashboard, users, staff, subscriptions/plans, notifications, analytics).

---

## 1. Services catalogue: `fixtures/services.php`
Used by: landing "Our services" cards, the Services dropdown in the top nav, and `public/book.php` (the form fields are driven by it).
Shape per service: `name, short, icon, photo, alt, tagline, blurb, price, unit, per_extra/extra_field/included | multiply_field, fields[], premium_freq[], season`.
`fields[]` items are `{name, label, type: number|select|chips|text, min,max,default | options[] | placeholder}`. book.php renders them generically.
**Needs:** `GET /api/services` (or a `services` + `service_fields` table). Prices could later come from a pricing endpoint per address.

## 2. Plans (Basic vs Premium): `fixtures/plans.php`
Used by: the landing "Basic or Premium" section, `public/plans.php` and the service pages. v3 wording follows doc2 p9: Premium = monthly provider reminders + saved preferences; **no** priority routing, discounts, unlimited work or automatic charges.
**Needs:** `GET /api/plans`. The existing `subscription_plans` table and admin "Subscriptions" pages may be able to back this.
Also needed: Premium recurrence rules per service.

## 3. Booking create: `public/book.php` (POST)
The form posts these field names, which you can keep as-is:
`service, address, <service field names>` (chips arrive as arrays, e.g. `bin_types[]`), `plan` (basic|premium), `frequency`, `date` (Y-m-d), `window`, `estimate`.
Today the POST only renders a confirmation preview. **Nothing is saved.**
**Needs:** `POST /api/bookings` → `{id, status:'requested'}`. Basic flow: the provider accepts the slot, or proposes another time in chat (see 7).
Suggested tables: `bookings`, `booking_items`/`booking_answers`, `recurrences`.

## 4. My Bookings: `fixtures/bookings.php`
Used by: `public/bookings.php` (Upcoming/Past tabs), the "Your next booking" card on the landing page and client home, and the calendar's "Upcoming services".
Shape: `id, service, when, date, window, status, team, plan, freq, address, price, queue?`.
Status values (v3, one vocabulary everywhere, doc2 p8/p21): `requested | searching | no_provider | quote_pending | awaiting_quote | confirmed | assigned | change_requested | reschedule_pending | on_the_way | arrived | in_progress | paused | delayed | weather_review | completed | disputed | closed | cancelled` (labels in `nu_booking_status()`).
Payment is a **separate** field: `unpaid | authorized | paid | failed | partially_refunded | refunded` (`nu_payment_chip()`). Past tab = completed, disputed, closed, cancelled.
**Needs:** `GET /api/bookings?client_id=&status=upcoming|past`.

## 5. Live tracking: `fixtures/tracking.php`
Used by: `public/track.php?id=` (status pill, ETA card, Leaflet map, team card, status timeline, "Arrived in your driveway" alert).
Shape: `booking, service, status, eta, eta_min, team{name,members,vehicle,rating}, home[lat,lng], route[[lat,lng]…], timeline[{key,label,time,done,current,note}]`.
The vehicle marker currently animates along `route` (about 22s). When it reaches the end, the page switches to the "Arrived" state.
**Needs:** `GET /api/bookings/{id}/tracking`, plus push updates (SSE or WebSocket) of the provider's GPS and status events. On each ping, call `marker.setLatLng()`.
Fire the driveway-arrival alert from a geofence or from the provider's "Arrived" button (see 9).

## 6. Completion evidence: `fixtures/proof_of_work.php` (escrow removed in v3)
Used by: track.php → "Completion report" (before/after photos, checklist, provider notes, invoice + payment chip) and activity.php.
The old "Confirm work & release payment" escrow button was **removed** because client doc2 p21 makes payment a separate state
and says completed ≠ accepted. Preview with `track.php?state=completed`; while on the way the report shows placeholders only.
**Needs:** `POST /api/bookings/{id}/photos` (see 9; completion is blocked until required uploads succeed), `GET /api/bookings/{id}/completion`
→ `{before{photo,time}, after{photo,time}, checklist[{label,done}], notes, duration, invoice{number,amount,tax,currency,payment}}`.

## 7. Messages + chat: `fixtures/messages.php`
Used by: `public/messages.php` (thread list with unread badges), `public/chat.php?thread=` (bubbles, reschedule proposal card, quick replies),
and the **unread dot** on "Messages" in the top nav and tab bar (`nu_unread_count()` in ui.php).
Sending and the quick replies only append to the page locally.
**Needs:** `GET /api/threads`, `GET/POST /api/threads/{id}/messages`, `POST /api/bookings/{id}/reschedule {window}` (accept/decline proposal).
Suggested tables: `threads (booking_id, client_id, provider_id)`, `messages`, `message_reads`.

## 8. Calendar, weather, notifications and snow queue: `fixtures/weather.php`, `fixtures/notifications.php`
Used by: `public/calendar.php` (7-day forecast strip, notifications, upcoming services), the landing "Local weather" card, and the client home "Today" card.
Notification `type`: `due | snow | queue | info`. A `queue` item renders the position bar plus **Let others go first** and **Pick a time window**. Both POST back to calendar.php and only show a preview.
**Needs:** a forecast feed keyed by the client's address (Environment Canada or Open-Meteo, cached),
a day-before snow alert job (cron) that creates notifications, `GET /api/notifications`,
`POST /api/queue/{booking}/defer` and `POST /api/bookings/{id}/window`.

## 9. Provider route / snow-day queue: `fixtures/queue.php`
Used by: `public/provider_route.php` (ordered stops, summary, status buttons **On my way / Arrived / Start / Complete**, photo picker)
and the "Today's route" card on provider home.
Buttons only change the UI locally. The photo picker previews files but uploads nothing.
**Needs:** `GET /api/providers/{id}/route?date=` (ordered stops, re-ordered when clients defer),
`POST /api/stops/{id}/status {on_my_way|arrived|start|complete}`, which drives the client's tracking timeline (5),
and `POST /api/bookings/{id}/photos` (multipart), which feeds the Before/After photos (6).

---

### Screen → data map

| Screen | File | Data today | Endpoint(s) to supply |
|---|---|---|---|
| Landing (services, weather, next booking, plans) | `public/index.php` | fixtures: services, weather, bookings, plans | 1, 2, 4, 8 |
| Book a service (dynamic per service) | `public/book.php` | fixtures: services | 1, 3 |
| Track your service | `public/track.php` | fixtures: tracking, proof_of_work, services | 5, 6 |
| My bookings | `public/bookings.php` | fixtures: bookings | 4 |
| Messages list | `public/messages.php` | fixtures: messages | 7 |
| Chat thread | `public/chat.php` | fixtures: messages | 7 |
| Calendar | `public/calendar.php` | fixtures: weather, notifications, bookings | 4, 8 |
| Provider route | `public/provider_route.php` | fixtures: queue | 9 |
| Client home cards | `public/client_home.php` | DB counts (real) + fixtures | 4, 8 |
| Provider home route card | `public/provider_home.php` | DB counts (real) + fixtures | 9 |
| Jobs, applications, my jobs, admin… | (existing) | **real DB**, unchanged | already wired |

Access: book.php is public (guests are asked to sign in on confirm). track, bookings and calendar are client-only;
messages and chat are client + provider; provider_route is provider-only. Wrong roles are redirected to their own home.


---

# v3 screens (client guides doc1 + doc2, all six services)

All screens are **UI only**: sample data from `fixtures/`, a "Sample data" tag, and forms that only show the resulting UI state.
Every page header comment lists its endpoints; they are collected here. Preview states use query strings so each state can be reviewed.
Assessment of both PDFs and the full functionality list: `notes/client-pdfs/ASSESSMENT.md` (kept outside the repo).

## 10. Service pages: `public/service.php?s=<slug>` (doc2 p2-7)
Slugs: `garbage-bin-cleaning, mobile-mechanics, snow-removal, fall-cleanup, lawn-mowing, car-wash` (old slugs aliased in `nu_service_slug()`).
Data: `fixtures/services.php` (form fields, side card) + `fixtures/visits.php` (current visit per service:
`id, status, date, window, team, members, eta, updated, timeline[], activity[], premium{enrolled, reminder}`).
The New booking form and the current visit are separate records (doc2 p1). The Premium panel shows only for enrolled customers; `&as=basic` previews the Basic view.
Car wash posts `water_source: customer|provider` and `power_source: customer|provider` independently (doc2 p7, p11).
**Needs:** `GET /api/services/{slug}`, `GET /api/bookings?service=&active=1`, `GET /api/bookings/{id}/events`, then POST → book.php review.

## 11. Booking review: `public/book.php` (doc1 p2, p8)
Posted from a service page (or `?from=reminder`). Shows details, window, price + tax, plan terms, payment method and a terms checkbox.
Confirm stays disabled until terms are ticked and a payment method is chosen. Confirm renders a "Requested · Unpaid" preview.
**Needs:** `POST /api/bookings/quote` (price, tax, capacity), `POST /api/bookings` (idempotency key; hosted/tokenised authorisation) → `{id, status:'requested', payment:'unpaid'|'authorized'}`.

## 12. Plans and care plans: `public/plans.php`, `public/care_plans.php` (doc2 p9-10)
`fixtures/plans.php` (Basic / Premium copy), `fixtures/care_plans.php`
(`plans[{service, name, provider, cadence, time, mode, fee, visit, status, next}]`, `reminders[{id, service, title, provider, text, sent, delivery, state}]`).
Reminder actions: Confirm a visit (→ book.php review) / Change date (→ change_appointment) / Skip this month (this cycle only). Edit / Pause / Cancel plan.
**Needs:** `GET /api/plans?service=`, `GET /api/plans/enrolled`, `GET /api/reminders?status=due`, `POST /api/reminders/{id}/confirm|change|skip`,
`PATCH /api/plans/{id} {cadence, preferred_time, visit_mode, paused_from}`. A reminder never creates a visit or a charge.

## 13. Change appointment: `public/change_appointment.php?id=` (doc2 p12-13)
Shows the current booking, a week of days, windows per day (`available|limited|full`), old → new comparison, apply to this visit / future plan visits, and a notification preview.
Results: `confirmed` (free slot before cutoff), `pending` (dispatch review), `declined` (old slot kept, alternatives offered).
`?state=on_route` → "Request change" (dispatch review only); `?reminder=1` arrives from a Premium reminder.
**Needs:** `GET /api/bookings/{id}/availability?week=` → `{days[{date, label, windows[{key,label,state}]}]}`,
`POST /api/bookings/{id}/reschedule {date, window, apply_to: this|future}` → `{state: confirmed|pending_dispatch|declined, alternatives[]}`.
Commit atomically: reserve new → update booking/route → release old → cancel outdated reminders (doc2 p22).

## 14. Snow event: `public/snow_event.php` (doc2 p4, p13)
Five steps: forecast → qualifying event → route published → come later → confirmed / declined. Shows plan terms (trigger, included areas, repeat passes).
"Busy? Come later": keep current / later today / another available day (date + window picker).
Previews: `?state=forecast|published|pending|confirmed|declined`, `?weather=unavailable`, `?as=basic` (Basic customers can request a one-off slot).
**Needs:** `GET /api/weather/events/current?address_id=` → `{event_id, state, forecast, updated, next_update}`, `GET /api/bookings/{id}`,
`POST /api/bookings/{id}/defer {option: later_today|another_day, date?, window?}` → `{state: pending|confirmed|declined, alternatives[]}`. Never one job per alert, only one per event.

## 15. Live tracking: `public/track.php?id=` (doc1 p3, doc2 p14)
`fixtures/tracking.php`: `booking, service, status, address, window, eta, eta_min, eta_range, location_updated, stale_since, team{name,members,vehicle,rating,note}, home[lat,lng], route[[lat,lng]], timeline[{key,label,time,done}], messages[{from,name,time,text}], weather{temp,text,note}, checklist[], invoice{number,amount,tax,currency,payment}`.
Six steps: Confirmed, Assigned, On the way, Arrived, In progress, Completed. The vehicle marker animation is a **preview only**: doc2 p22 requires real GPS/events.
`?state=stale` → last-update label and a less precise ETA. `?state=completed` → photos + checklist + invoice + payment chip. Messages append locally.
**Needs:** `GET /api/bookings/{id}/tracking` (SSE/WebSocket; GPS only during the accepted trip), `GET/POST /api/threads/{booking_id}/messages`, `GET /api/bookings/{id}/completion`.

## 16. Activity and notifications: `public/activity.php` (doc2 p15, p22)
`fixtures/events.php`: `booking{id, service, title, team, status, payment, date, duration, utilities, package, timeline[[time, title, detail, actor, icon]], checklist[], notes, invoice, tax}`,
`notifications[[event_type, title, detail, time, channels, delivery: pending|delivered|read|failed]]` (tuples today; use objects in the API, plus an `event_id` so retries never double-send).
Customer actions: View invoice, Rate service (stars + comment → thanks state), Report an issue (reason → case "CASE-311 · Disputed" stays visible), Book again.
`?tab=notifications` lists notifications with delivery chips and a filter.
**Needs:** `GET /api/bookings/{id}/events`, `GET /api/bookings/{id}/completion`, `GET /api/invoices/{id}`, `POST /api/bookings/{id}/rating {stars, comment}`,
`POST /api/cases {booking_id, reason, details, photos[]}`, `GET /api/notifications?status=`. Events and messages are separate streams.

## 17. Mobile mechanics: `public/mechanics.php` → `public/mechanic_request.php` → `public/quote.php` (doc1 p14-17, doc2 p16-18)
`fixtures/mechanic.php`: `location{label,value}`, `roadside{<slug>: {name, icon, tone, fields[]}}` (6), `maintenance{<slug>: …}` (8), `brake_symptoms[]`,
`quote{id, version, booking, service, vehicle, scope, items[[label, kind: Parts|Labour|Fee, qty, amount]], tax_rate, tax_label, includes[], excludes[], expires, mechanic, windows[], extra{…revised version}}`.
- Menu: illustrative SVG map, manual address (works without device permission), "Use my location", tiles → each opens its own form (`?svc=`).
- Request: brake form (year/make/model/trim, front/rear/Not sure, symptoms, photo/video, address, parking notes). The address is validated client-side.
  States: `?step=3` = quote requested (no promised slot); roadside `?state=searching` / `?state=no_provider` (retry/support, no ETA).
- Quote: itemised, includes/excludes, expiry, Approve / Ask / Decline, window choice, payment card (hosted). `?state=approved|declined|expired|revised|revised_ok`
  (extra work: approved items stay distinct from newly proposed ones).
**Needs:** `GET /api/services/mobile-mechanics/categories?lat=&lng=`, geocoding, `POST /api/mechanic-requests` → `{id, state: quote_pending|searching|no_provider}`,
`GET /api/mechanic-requests/{id}` (poll/SSE), `GET /api/quotes/{id}?version=`, `POST /api/quotes/{id}/approve {version, window}`, `/decline {reason}`, `/questions {text}`,
`POST /api/payments/intents` (idempotency key).

## 18. Provider dashboard: `public/provider_dashboard.php?job=` (doc1 p4, doc2 p19; provider role)
`fixtures/provider_day.php`: `team, date, time, stats[[icon, count, label, hint]], jobs{<id>: {id, service, slug, window, where, status, scope, access, plan, quote, utilities, property, progress, at, checklist[]}}, reminders[[customer, service, due, state: sent|replied|skipped]], week[]`.
Job detail shows the same facts the customer approved. Steps: On the way / Arrived / Start work / Report delay. The checklist and the after-photo upload enable Submit, which then shows "Pending sync" (simulated).
`?job=1042|1043|1044`.
**Needs:** `GET /api/teams/{id}/day?date=`, `POST /api/jobs/{id}/events {arrived|start|pause|delay|complete}`, `POST /api/jobs/{id}/media` (offline queue),
`POST /api/jobs/{id}/extra-work`, `GET /api/teams/{id}/reminders`.

## 19. Operations console: `public/operations.php?queue=` (doc1 p5, doc2 p20; manager / admin / superadmin)
`fixtures/ops.php`: `areas[], kpis[[icon, value, label, tone]], queues{dispatch|reschedule|snow|equipment|reminders|issues: {label, rows[[booking, service, window, status, team, action]]}}, teams[[name, available|busy]], weather{title,text}, payments[[state, count]]`.
Filters (service/area/status/search) and the assign panel (match on qualification, coverage, equipment, travel time, parts, capacity) only work locally.
**Needs:** `GET /api/ops/summary?area=&date=`, `GET /api/ops/queue?queue=&service=&area=&status=&q=`, `GET /api/teams/availability`, `POST /api/bookings/{id}/assign {team_id}`, weather review endpoints.

## 20. Service settings: `public/service_settings.php?s=` / `?new=1` (doc1 p10, doc2 p8, p20)
Per service: form fields, pricing mode (fixed / quote), base, unit, tax, equipment, Basic/Premium terms, weather rules, checklist, required photos, change cutoff. Save only shows a preview.
**Needs:** `GET/PUT /api/admin/services/{slug}` with versioning and an audit log; a new service needs no new booking database.

## 21. States reference: `public/status.php` (public; doc1 p13, doc2 p21-23)
Lifecycle, exceptions and payment chips, the doc2 p21 rule table (each row links to its preview URL), the doc2 p23 scenarios and the doc1 acceptance checks.
**The backend must emit exactly these keys** (see section 4).

## 22. Updated screens
- `public/bookings.php`: Upcoming / Past / Care plans tabs. Links depend on state (track / change / quote / activity), with a payment chip on every row.
- `public/client_home.php`: Premium reminder bar (Confirm / Change date / Skip) and snow event link.
- `public/index.php`: step 4 copy "Before/after photos, checklist and invoice", plan copy per doc2 p9, "Compare plans by service".

### v3 screen → data map

| Screen | File | Fixtures | Section |
|---|---|---|---|
| Service pages | `public/service.php` | services, visits | 10 |
| Booking review | `public/book.php` | services | 11 |
| Plans / care plans | `public/plans.php`, `public/care_plans.php` | plans, care_plans | 12 |
| Change appointment | `public/change_appointment.php` | bookings, services, visits | 13 |
| Snow event | `public/snow_event.php` | visits | 14 |
| Live tracking | `public/track.php` | tracking, proof_of_work | 15 |
| Activity / notifications | `public/activity.php` | events | 16 |
| Mechanic menu / request / quote | `public/mechanics.php`, `public/mechanic_request.php`, `public/quote.php` | mechanic | 17 |
| Provider dashboard | `public/provider_dashboard.php` | provider_day | 18 |
| Operations | `public/operations.php` | ops | 19 |
| Service settings | `public/service_settings.php` | services | 20 |
| States reference | `public/status.php` | (none) | 21 |

---

# Phase 1: launch gate, coming-soon splash and locked services

Public launch is end of November 2026 with two services live (Winter Snow Removal and Mobile Mechanics). The other four are "Coming soon". All of this is UI only; the sections below say what Sir Paul needs to connect.

## 23. Launch gate: `includes/gate.php`

- **What it does.** On the public hosts (`nuahnseasonalhub.com`, `www.nuahnseasonalhub.com`) every page request except the splash and its assets shows `public/coming_soon.php`. The home URL serves the splash; any other URL answers `302 /`. The full app stays reachable on the Hostinger temporary address (`lightslategrey-guanaco-876409.hostingersite.com`) and on localhost. Every non-public host also gets `X-Robots-Tag: noindex, nofollow`, and the splash adds `<meta name="robots" content="noindex">` only when it is not on the public domain.
- **Preview bypass.** Open any URL on the public domain with `?preview=<key>`. The gate sets the `nu_preview` cookie (HttpOnly, Secure, SameSite=Lax, 30 days) and from then on that browser sees the full app. A plain-http preview request is redirected to https first so the cookie is never sent in the clear. A wrong key shows the splash. The cookie holds an HMAC of the key, never the key itself.
- **Where the key lives.** It is never committed. It comes from the `NUAHN_PREVIEW_KEY` environment variable, or from the untracked, gitignored `config/preview.local.php` (copy `config/preview.example.php`). On Hostinger, `deploy.sh` writes `config/preview.local.php` into the build from the file `/workspace/notes/hostinger/preview_key` (mode 600, at least 24 characters), so `rsync --delete` can never remove it. The deploy checks list `/config/preview.local.php` as an expected 403. Rotate the key by replacing that file and redeploying; old cookies stop working.
- **How it is loaded.** Every entry script (`public/*.php`, `public/admin/index.php`, `public/jobs/index.php`, `actions/*.php`, root `index.php`) does an explicit `require_once` of the gate before any other output, plus `config/init.php` and `includes/header.php` as a second line of defence. The gate is idempotent (`NU_GATE_DONE`) and does nothing under CLI. Docker also sets `auto_prepend_file = /var/www/html/includes/gate.php` in `docker/php-prod.ini`. Hostinger does not use `auto_prepend_file` on purpose: a wrong absolute path would be fatal on every request, so the explicit includes are the mechanism there. `deploy.sh` fails the build if any entry script is missing the gate. **If you add a new entry script, include the gate at the top.**
- **Opening the site at launch.** Set `NUAHN_GATE_HOSTS` to `-` (real environment variable; if Hostinger cannot set one, change the default host list in `includes/gate.php`) and the gate lets everyone through. To gate other hosts, set it to a comma-separated list of hostnames. With the variable unset, the default is the two public hostnames.
- **Cache note.** The gate sends `Cache-Control: no-store` on the splash and redirects and `private, no-cache` on gated responses so a CDN does not serve the splash to previewers or the app to visitors. Keep Hostinger's CDN or page cache from caching HTML for the public domain until launch.

## 24. Service status flags: `fixtures/services.php`

- Every service has `'status' => 'live' | 'coming_soon'` and a `'launch'` label. Today `snow-removal` and `mobile-mechanics` are `live`; `garbage-bin-cleaning`, `lawn-mowing`, `fall-cleanup` and `mobile-car-wash-at-home` are `coming_soon`.
- Helpers in `includes/ui.php`: `nu_service_live($slug)`, `nu_services_by_status('live' | 'coming_soon')` and `nu_soon_chip($label)`. Use these instead of hard-coding slugs when you add anything that lists services.
- **To launch a service:** flip its `status` to `live` (later, the database column that replaces the fixture). The home cards, services sheet, mobile Book tab, footer links, plans table, client home tiles and the service and booking pages all follow the flag with no other change.
- **Locked behaviour:** a locked service shows the "Coming soon" badge, is not bookable, and links to `public/service.php?s=<slug>`. That page shows the hero, "What to expect" and a "Notify me when it launches" form instead of the booking form. A direct hit on `public/book.php?service=<locked slug>` (GET or POST) shows the same state. The server must also refuse locked services when real booking creation exists (reject any create call whose service is not `live`).
- Sample bookings and care plans only use live services so a locked service never looks bookable. The activity, track and status pages and `fixtures/events.php`, `visits.php`, `provider_day.php` and `ops.php` still contain bin, lawn, fall and car wash sample data; it is hidden behind locked services but not yet removed.

## 25. Notify-me forms (splash + locked service pages)

- Both forms are UI only: they validate the email in the browser, show "Thanks, we'll be in touch" and **store nothing**. A no-JS POST shows the same thanks state without saving. The locked-service form carries a "Preview only, nothing is stored" tag.
- To connect: send `{ email, postal_code (optional), service (slug, or "all" from the splash) }` to a new endpoint, store it with a consent timestamp, and return the same thanks state. The splash forms are `notify-hero` and `notify-cta` in `public/coming_soon.php`; the locked-service form is built by `nu_soon_panel()` in `includes/ui.php`.
- The splash is self-contained (inline CSS, no app.css) so it stays fast. Its service cards read `status` and `launch` from `fixtures/services.php`, so flipping a service live updates the splash too.

---

# Phase 2: marketplace UI (independent contractors, bidding, escrow states, mobile mechanics)

Everything below is UI only with sample data. Every fixture screen shows a visible "Sample data" tag (`nu_sample_tag()`); remove the tag when a screen reads real data. All amounts, commission %, price ranges, boost tiers and times are invented for the preview and are not business rules. Only the two live services (Winter Snow Removal, Mobile Mechanics) are built; locked services stay "Coming soon".

## 26. Contractor model: `fixtures/contractors.php`, `public/contractor.php?id=`

- Replaces any "team" idea. A contractor is an independent person: `id` (c-1001), `name`, `photo` (optional; initials avatar when empty), `services[]`, `rating`, `reviews`, `jobs`, `response` (median reply time), `since`, `radius_km`, `bio`, `badges{verified, insured, id_checked, certified}` and `area`.
- Helpers in `includes/ui.php`: `nu_contractor($id)`, `nu_avatar()`, `nu_badges($contractor)`, `nu_rating()`. Use them everywhere a contractor is shown so the badges stay consistent.
- Endpoint: `GET /api/contractors/{id}` (profile, badges, stats, services, reviews). The profile page is public (no sign-in needed to read). "Message" needs sign-in (see section 36 for `?next=`).
- Badges must come from the vetting queue results (section 33), never from a self-declared field. Photos: store resized versions and serve AVIF/WebP via `nu_photo()` as for other images.
- "Member since", "Jobs done", "Replies in" are computed values (count of approved jobs, median first-reply time).

## 27. Post a task and the map: `public/post_task.php` (`?plan=seasonal`, `?svc=`)

- Steps: property (address + map), driveway size (Standard 1-2 cars, Large 3-4 cars, Commercial or custom sqft), walkways and steps, optional ice melt, schedule (on demand, or seasonal plan with a snowfall trigger of 5 cm), target budget with suggested range and "Boost your bid".
- **Guest exploration.** A visitor can enter an address, pick scope and see the estimated range with no account. The sign-in prompt appears only when pressing "Post task". Today the estimate is computed client-side from `fixtures/marketplace.php`; replace with `GET /api/pricing/snow-estimate?size=&sqft=&walk=&steps=&ice=&plan=` -> `{lo, hi, mid}` and `GET /api/pricing/pickup?budget=&mid=` -> expected pickup tier (drives the "Boost" text). Keep both endpoints public and rate limited.
- **Post.** `POST /api/tasks` (client only; return 401 for a guest, open sign-in, then retry with the draft kept in `sessionStorage`) -> `{task_id}`, then go to `offers.php?task=`.
- **Map hook.** `public/assets/map-picker.js` is self-contained (CSS/SVG map, draggable pin, "Use my location", address field) and loads no external scripts. To use Google Maps or Mapbox set `window.NuahnMapProvider = { mount(el, {lat, lng, onMove}) -> {setPosition(lat, lng)}, geocode(text) -> Promise<{lat, lng, label}>, reverse(lat, lng) -> Promise<string> }` before `map-picker.js` loads (the signature is also documented at the top of that file); the picker then calls it instead of the built-in sample map. Load the provider SDK lazily (on first interaction with the map) so the page keeps its mobile speed. Store `lat`, `lng`, formatted address and, for snow, optionally the driveway polygon.
- Boost tiers (for example $45 to $70) and the suggested range are sample values in `fixtures/marketplace.php`; replace with the pricing service.

## 28. Offers: `public/offers.php?task=&state=`

- States to wire: no offers yet, one offer, many offers, boosted (the segmented control in the UI previews them).
- Endpoints: `GET /api/tasks/{id}/offers` (poll every 15 s or push), `POST /api/tasks/{id}/offers/{offer_id}/accept` (authorises payment, funds held, NOT captured, job becomes Confirmed), `POST /api/tasks/{id}/boost {budget}`, `POST /api/tasks/{id}/cancel`, `POST /api/threads {contractor_id, task_id}` for Message.
- An offer is `{contractor_id, price, eta, note, created_at, tags[]}`. The compare checkboxes are client-side only. Sort options: best match, lowest price, fastest, top rated. "Best match" needs a server score (price vs target, ETA, rating).

## 29. Open jobs board (providers): `public/open_jobs.php`, card `nu_open_job_card()`, `public/assets/jobs.js`

- The same card is used on the provider dashboard and the board. Actions: **Accept at the client's price**, **Counter-bid** (price stepper, ETA, optional note) and "Not for me".
- Endpoints: `GET /api/contractors/me/open-jobs?radius=&service=`, `POST /api/jobs/{id}/accept`, `POST /api/tasks/{id}/offers {price, eta, note}`. Only contractors approved in the vetting queue may see or accept jobs; filter by approved service, distance and online status ("Online" switch: `PUT /api/contractors/me/availability`).
- Roadside mechanic jobs are broadcast: first accept wins, the others get "no longer available". Use an atomic update on accept.
- The screen shows "You receive about $X after 12% commission" from a sample rate; compute from the real commission setting (section 32).

## 30. Mobile mechanics: `mechanics.php`, `mechanic_request.php`, `quote.php`

- **Pathway A, roadside** (flat tire, battery boost, lockout, emergency fuel, tire pressure, local towing): no scheduling; the request is broadcast to approved technicians within a radius; first accept confirms price and ETA. Preview states: `mechanic_request.php?path=roadside&svc=&state=broadcast|accepted|no_provider`.
- **Pathway B, scheduled repair** (oil and filter, brake pads, battery replacement, tire rotation or swap, fluid top-off, check engine diagnostic): vehicle, symptoms, media, location, preferred window; technicians send itemised quotes. Preview: `?path=repair&svc=&state=quote_pending`.
- Form fields: vehicle (year, make, model, trim, engine; `nu_vehicle_select()` with a sample make/model map; replace with a vehicle data source such as a VIN or year-make-model API), symptom checklist, optional photo/audio/video (roadside: photo and video only), location with map and parking surface, access notes.
- Endpoints: `POST /api/mechanic-requests {path, service, vehicle{}, symptoms[], details, media[], address, lat, lng, parking, window}`, `GET /api/mechanic-requests/{id}` (poll or SSE for state), `POST /api/mechanic-requests/{id}/cancel`, `POST /api/uploads` (photo, audio, video: type and size limits, virus scan, signed URLs; `public/assets/mech.js` only previews locally).
- Broadcast: select approved technicians by service, distance and availability; send push/SMS; store each notification so the "6 technicians within 12 km" and "viewing" counts are real. Roadside price: estimate range shown to the client, the technician confirms the final price on accept.
- **Quote** (`quote.php`): itemised parts + labour + call-out fee + taxes. Taxes come from the service address province: the sample shows GST 5% and PST 0% (not charged in Alberta). Taxes must be calculated by the server by province, not hard-coded. Client actions: Approve quote, Ask question (posts to the thread), Decline (with reason). `POST /api/quotes/{id}/approve|decline|questions`; provider composer: `?state=draft` (provider only) -> `POST /api/quotes`.
- **On-site revised quote** (`?state=revised`): `POST /api/quotes/{id}/amend` pauses the job and shows a new version; the client sees v1 approved items and the added items and must approve again before work continues. Keep versions immutable.

## 31. Escrow and payment states: `nu_payment_chip()`, `nu_job_machine()`, `nu_trust_note()`

- **Payment status is separate from job status.** Payment: `unpaid`, `authorized` (held), `captured` (paid out), `refunded` (and partially refunded, failed), `disputed`. Job: `requested`, `open_for_bids`, `confirmed` (funds held), `on_the_way`, `arrived`, `in_progress`, `pending_approval` (completed), `approved` or `disputed`; exceptions `cancelled`, `delayed`, `change_requested`, `weather_review`, `no_provider`. Both state lists live in `includes/ui.php` and `public/status.php` (the "Job states and rules" page) so there is one place to keep them in sync.
- Flow: client accepts an offer or approves a quote -> create a payment intent with manual capture (authorise and hold) via the secure processor -> job Confirmed -> after approval capture the intent and queue the payout. Cancel or refund releases the authorisation.
- Trust copy: "Funds held securely until you approve the work"; card details are tokenised by the payment processor and never touch Nuahn servers. Use the processor's hosted fields or checkout; the UI shows only a generic padlock and "Card ending 1234" (no brand logos). Add the real processor mark only if its licence allows it.
- All money fields in the UI are display strings; store amounts as integer cents plus currency (CAD).

## 32. Approve, dispute and payout: `public/approve.php`, provider dashboard payouts

- `approve.php?id=&state=pending|dispute|approved|disputed`: before and after photos (one or more of each), checklist, summary, **Approve and release payment** (optional rating, tip, comment) and **Report a problem** (reason, desired outcome, details, photos). An auto-release rule (sample: 48 h after completion if no action) is shown; confirm with the business before building it.
- Endpoints: `GET /api/jobs/{id}/completion`, `POST /api/jobs/{id}/approve {rating, tip?, comment?}` (capture then payout), `POST /api/jobs/{id}/dispute {reason, outcome, details, photos[]}` (job and payment -> Disputed, funds stay held, case created in the disputes queue).
- **Provider side.** `provider_dashboard.php` and `provider_route.php`: the "Submit completion report" button is disabled until at least one before and one after photo are chosen (client-side preview with a file input). The server must enforce the same rule in `POST /api/jobs/{id}/complete` and reject a report without both photo types. Photos need upload, timestamp, optional geotag and EXIF stripping for privacy.
- **Payout and commission.** Sample: job total, Nuahn commission 12% (a sample, not a business rate), payout, tip and a note that contractors handle their own taxes. Source from a commission setting per service. Payout statuses: pending approval, scheduled, paid. Use the processor's connected-account payouts and keep a ledger table (job, gross, fee, tax, net, payout id).

## 33. Operations: vetting queue, disputes, open jobs: `public/operations.php?view=overview|open|vetting|disputes`

- Dispatch is replaced by vetting and oversight. **Vetting queue**: identity check, insurance document, licence, mechanic certification, each with status (verified, needs review, rejected) and View/Approve/Reject/Request info. Endpoints: `GET /api/admin/applications?status=`, `POST /api/admin/applications/{id}/checks/{check}` `{status, note}`, `POST /api/admin/applications/{id}/decision {approve|reject, note}`. Approval sets the contractor badges and which services they may bid on. Also expiry dates for insurance and licences with reminders.
- **Disputes queue**: case, job, parties, held amount, evidence (photos, checklist, chat history), outcome buttons (Release to contractor, Refund client, Partial refund, Ask for info). Endpoints: `GET /api/admin/disputes`, `POST /api/admin/disputes/{id}/resolve {outcome, amount, note}` (captures or refunds the held funds and notifies both parties). Keep an audit log.
- **Open jobs** view is read-only monitoring (filters, search, "Nudge nearby verified contractors" action: `POST /api/admin/tasks/{id}/nudge`).
- Roles: superadmin and manager see the same queues; limit financial resolution to the roles the business chooses.

## 34. Updated existing screens

- `bookings.php`, `activity.php`, `track.php`, `snow_event.php`, `notifications`, `messages.php`, `change_appointment.php`, `client_home.php`, `index.php`, `plans.php`, `provider_home.php`: contractor wording, payment chip next to job status, the job state machine bar and "needs your attention" items (approve work, new offer). All still read fixtures.
- Events to push as notifications: new offer, offer accepted, contractor on the way, arrived, completion report submitted (approve now), payment held, payment released, dispute opened or resolved, quote ready, quote revised.
- Tracking: the live map still uses the fixture location; connect to the contractor location stream only while a job is On the way or In progress.

## 35. Brand assets

- The logo image is the four-petal mark only (`public/assets/img/mark-96.webp` and `mark-256.webp`, with the favicon set beside them); "Nuahn Seasonal Hub" is HTML text (Plus Jakarta Sans 800, tight letter-spacing) coloured by CSS (ink on light, white on dark), so there is no second image to swap for dark mode. Helpers: `nu_logo()` (mark plus text) and `nu_logo_mark()` (mark only). Favicons and the web manifest icons are generated from the same mark.

## 36. Sign-in return: `?next=`

- `post_task.php`, `contractor.php` (Message) and `offers.php` send guests to sign in. `login.php` and `register*.php` do not yet honour a `?next=` return path; add a validated same-origin relative `next` and keep the draft task in `sessionStorage` or the session so the guest returns to "Post task" with their scope intact.

## 37. Sample data index

- Fixtures added or reworked: `contractors.php`, `marketplace.php` (tasks, offers, open jobs, vetting, disputes, payout), `mechanic.php` (pathways, vehicles, parking, quotes, taxes), `bookings.php`, `tracking.php`, `visits.php`, `events.php`, `notifications.php`, `messages.php`, `ops.php`, `queue.php`, `proof_of_work.php`, `provider_day.php`. Contractor names in the fixtures are invented.
