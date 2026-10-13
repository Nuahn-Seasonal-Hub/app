# UI ↔ data hook-up notes (client design v2)

**Who owns what:** Christo/Sharon (+ bot) own the UI. **Sir Paul builds the functionality.**
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
Used by: the landing "Basic or Premium" section, and the frequency switch in book.php (One-time = Basic, Recurring = Premium).
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
Status values: `requested | confirmed | assigned | on_the_way | in_progress | completed | cancelled` (chip labels in `nu_booking_status()`).
**Needs:** `GET /api/bookings?client_id=&status=upcoming|past`.

## 5. Live tracking: `fixtures/tracking.php`
Used by: `public/track.php?id=` (status pill, ETA card, Leaflet map, team card, status timeline, "Arrived in your driveway" alert).
Shape: `booking, service, status, eta, eta_min, team{name,members,vehicle,rating}, home[lat,lng], route[[lat,lng]…], timeline[{key,label,time,done,current,note}]`.
The vehicle marker currently animates along `route` (about 22s). When it reaches the end, the page switches to the "Arrived" state.
**Needs:** `GET /api/bookings/{id}/tracking`, plus push updates (SSE or WebSocket) of the provider's GPS and status events. On each ping, call `marker.setLatLng()`.
Fire the driveway-arrival alert from a geofence or from the provider's "Arrived" button (see 9).

## 6. Proof of work + escrow: `fixtures/proof_of_work.php`
Used by: track.php → "Completed work" (Before/After photos, amounts) and the **Confirm work & release payment** button.
The button is disabled until the job is completed. Preview the completed state with `track.php?state=completed`. POST `confirm=1` shows a "released" preview and **charges nothing**.
**Needs:** provider photo upload `POST /api/bookings/{id}/photos` (see 9), payment capture into escrow at booking,
`POST /api/bookings/{id}/confirm` → release (e.g. Stripe Connect manual payouts), and a dispute path ("Message the team" link).

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
