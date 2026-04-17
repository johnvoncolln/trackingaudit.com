# Tracking

Multi-carrier package tracking app (UPS, USPS, FedEx) with CSV bulk import, API ingestion, automatic carrier detection, scheduled updates, late shipment notifications, Livewire UI, and Jetstream auth.

## Models
- **User** -> hasMany Tracker (via user_id). Jetstream + Sanctum + 2FA. Has notification preferences (late_shipment_notifications_enabled/frequency, late_shipment_report_enabled/frequency) and `api_token` (UUID) for API access. `generateApiToken()` method.
- **Tracker** -> hasOne TrackerData (via trackers_id), belongsTo User. Casts: reference_data (array), status_time/delivery_date/delivered_date (datetime). Key fields: carrier, tracking_number, status, location, reference_id, reference_name, recipient_name, recipient_email.
- **TrackerData** -> belongsTo Tracker. Stores full raw API response as JSON (`data` cast to array).

## Enums
- **Carrier** — UPS, FedEx, USPS. Used in factory routing and carrier detection.
- **TrackerStatus** — UNKNOWN, PRE_TRANSIT, IN_TRANSIT, OUT_FOR_DELIVERY, DELIVERED, AVAILABLE_FOR_PICKUP, RETURN_TO_SENDER, FAILURE, CANCELLED, ERROR. Has `label()`, `activeStatuses()`, `terminalStatuses()`, and `fromUps()`/`fromUsps()`/`fromFedex()` factory methods.
- **NotificationFrequency** — Daily, Weekly, Monthly. Used for user notification preferences.

## Services
- **CarrierDetector** — Static `detect(string): Carrier`. Checks UPS hard rules (1Z, T-prefix, 9-digit), then FedEx hard rules (96-prefix, 7-prefix, 12/14/15/34-digit), defaults to USPS for everything else.
- **Api/UpsApiService** — OAuth2 (Basic Auth), GET tracking. Token cached as `ups_access_token`.
- **Api/UspsApiService** — OAuth2 (client_credentials + scope:tracking), GET tracking with `expand=DETAIL`. Token cached as `usps_access_token`.
- **Api/FedexApiService** — OAuth2 (client_credentials), POST tracking with JSON body. Token cached as `fedex_access_token`.
- **Tracking/CarrierTracker** — Interface: `track(User, array): Tracker` and `update(Tracker): Tracker`.
- **Tracking/UpsTracker** — Parses `trackResponse.shipment[0].package[0]`. Extracts `deliveryDate` and sets `delivered_date` on DELIVERED status.
- **Tracking/UspsTracker** — Parses `trackingEvents[]`. Extracts `expectedDeliveryDate` and sets `delivered_date` on DELIVERED.
- **Tracking/FedexTracker** — Parses `output.completeTrackResults[0].trackResults[0]`. Extracts `estimatedDeliveryTimeWindow` and sets `delivered_date` on DELIVERED.
- **Tracking/TrackerFactory** — `make(string|Carrier): CarrierTracker`. Single match on Carrier enum.
- **TrackingRouter** — Static `route(array)`: auto-detects carrier via CarrierDetector if not provided, calls `track()` with Auth::user().

## Jobs
- **DelegateTrackersJob** — Receives User + array of records, dispatches one TrackingImportJob per record.
- **TrackingImportJob** — Receives User + single record, resolves tracker via TrackerFactory, calls `track()`.
- **UpdateTrackerJob** — Receives single Tracker, calls `TrackerFactory::make()->update()`. 3 tries, backoff [30, 60].

## Scheduled Commands (routes/console.php)
- `tracking:update-active` — Hourly. Dispatches UpdateTrackerJob for all trackers with active (non-terminal) statuses.
- `notifications:late-shipments {frequency}` — Daily 08:00, Weekly Monday 08:00, Monthly 1st 08:00. Sends LateShipmentNotification digest to opted-in users with trackers past delivery_date but not yet delivered.
- `reports:late-shipments {frequency}` — Same schedule. Sends LateShipmentReportNotification to opted-in users for UPS/FedEx shipments delivered late (delivered_date > delivery_date). Includes UPS Service Guarantee refund claim note.

## Notifications
- **LateShipmentNotification** — Mail channel, queued. Lists currently-late trackers with carrier, expected date, status.
- **LateShipmentReportNotification** — Mail channel, queued. Lists late deliveries with days late. UPS entries get refund claim callout.

## Controllers & Routes
**Web (auth:sanctum + verified):**
- `GET /tracking` — TrackingController@index (Livewire TrackingTable)
- `GET /tracking/new` — TrackingController@create (single + CSV form, no carrier dropdown)
- `POST /tracking` — TrackingController@store (carrier auto-detected via CarrierDetector)
- `GET /tracking/{tracker}` — TrackingController@show (details + UPS activity history)
- `POST /tracking/{tracker}/update` — TrackingController@update (manual refresh)
- `POST /tracking/import` — ImportTrackingController@create (CSV, carrier column optional, auto-detected)
- `GET /tracking/template` — ImportTrackingController@downloadTemplate
- `GET /settings` — Settings page (notification preferences + API token)

**API (no auth middleware, token-in-URL):**
- `POST /api/v1/tracking/{api_token}` — TrackingApiController@store. Accepts JSON array of tracking numbers (max 500), auto-detects carriers, dispatches DelegateTrackersJob. Throttled 60/min.

## Livewire
- **TrackingTable** — Paginated (10/page), searchable, filterable (carrier, date range), sortable. Query string params.
- **NotificationSettings** — Notification preference toggles + frequency selectors, API token display/generation.

## Integrations
- **UPS** — `UPS_CLIENT_ID`, `UPS_CLIENT_SECRET`, `UPS_TOKEN_URL`, `UPS_TRACK_URL`
- **USPS** — `USPS_CONSUMER_KEY`, `USPS_CONSUMER_SECRET`, `USPS_TOKEN_URL`, `USPS_TRACK_URL`. OAuth works but tracking returns 403 — USPS IP Agreement access controls enforced 4/1/2026.
- **FedEx** — `FEDEX_API_KEY`, `FEDEX_SECRET_KEY`, `FEDEX_TOKEN_URL`, `FEDEX_TRACK_URL`
- All tokens cached with 5-min pre-expiry buffer. Config in `config/services.php`, creds in `.env`.
- UPS Track API is single-tracking only (GET per tracking number). No batch endpoint available.

## Database
- **users** — Standard Jetstream fields + late_shipment_notifications_enabled, late_shipment_notifications_frequency, late_shipment_report_enabled, late_shipment_report_frequency, api_token (UUID, unique).
- **trackers** — user_id (FK), carrier, tracking_number, reference_id, reference_name, reference_data (JSON), recipient_email, recipient_name, origin, destination, location, status, status_time, delivery_date (populated from carrier API), delivered_date (set on DELIVERED status).
- **tracker_data** — trackers_id (FK cascade), data (JSON). One-to-one with trackers.
- Queue: database driver, `jobs` table. Horizon configured.

## Key Patterns
- Factory pattern for carrier dispatch (TrackerFactory)
- Strategy pattern via CarrierTracker interface
- API layer (Http clients) separated from tracking layer (business logic)
- CSV/API import fan-out: DelegateTrackersJob -> N x TrackingImportJob
- Status normalization: raw carrier strings -> TrackerStatus enum via per-carrier factory methods
- Carrier auto-detection: UPS/FedEx hard rules, USPS fallback default (CarrierDetector)
- Show view activity history only renders for UPS response shape (USPS/FedEx history not yet rendered)

## Adding a New Carrier
To add a carrier (e.g., DHL), touch ~6 files:
1. Add case to `Carrier` enum (`app/Enums/Carrier.php`)
2. Create API service (`app/Services/Api/DhlApiService.php`) — OAuth + tracking endpoint
3. Create tracker (`app/Services/Tracking/DhlTracker.php`) implementing `CarrierTracker` — parse response, populate delivery_date/delivered_date
4. Add case to `TrackerFactory::make()` match (`app/Services/Tracking/TrackerFactory.php`)
5. Add `fromDhl()` method to `TrackerStatus` enum (`app/Enums/TrackerStatus.php`)
6. Add detection patterns to `CarrierDetector` (`app/Services/CarrierDetector.php`)
7. Add config entries in `config/services.php` + `.env` credentials
