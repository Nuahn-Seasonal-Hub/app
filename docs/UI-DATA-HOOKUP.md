# UI ↔ data hook-up notes (client design v2)

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
