# Tracking

Multi-carrier package tracking app (UPS, USPS, FedEx) with CSV bulk import, Livewire UI, and Jetstream auth.

## Models
- **User** -> hasMany Tracker (via user_id). Jetstream + Sanctum + 2FA.
- **Tracker** -> hasOne TrackerData (via trackers_id). Casts: reference_data (array), status_time/delivery_date/delivered_date (datetime). Key fields: carrier, tracking_number, status, location, reference_id, reference_name, recipient_name, recipient_email.
- **TrackerData** -> belongsTo Tracker. Stores full raw API response as JSON (`data` cast to array).

## Enums
- **Carrier** — UPS, FedEx, USPS. Used in validation and factory routing.
- **TrackerStatus** — UNKNOWN, PRE_TRANSIT, IN_TRANSIT, OUT_FOR_DELIVERY, DELIVERED, AVAILABLE_FOR_PICKUP, RETURN_TO_SENDER, FAILURE, CANCELLED, ERROR. Has `label()` for display and `fromUps()`, `fromUsps()`, `fromFedex()` factories that normalize carrier-specific status strings.

## Services
- **Api/UpsApiService** — OAuth2 (Basic Auth), GET tracking. Token cached as `ups_access_token`.
- **Api/UspsApiService** — OAuth2 (client_credentials + scope:tracking), GET tracking with `expand=DETAIL`. Token cached as `usps_access_token`.
- **Api/FedexApiService** — OAuth2 (client_credentials), POST tracking with JSON body. Token cached as `fedex_access_token`.
- **Tracking/CarrierTracker** — Interface: `track(User, array): Tracker` and `update(Tracker): Tracker`.
- **Tracking/UpsTracker** — Parses `trackResponse.shipment[0].package[0].activity[]`.
- **Tracking/UspsTracker** — Parses `trackingEvents[]` with eventCity/eventState/eventType.
- **Tracking/FedexTracker** — Parses `output.completeTrackResults[0].trackResults[0].scanEvents[]`.
- **Tracking/TrackerFactory** — `make(string|Carrier): CarrierTracker`. Single switch on Carrier enum.
- **TrackingRouter** — Static `route(array)`: resolves carrier via factory, calls `track()` with Auth::user().

## Jobs
- **DelegateTrackersJob** — Receives User + array of CSV records, dispatches one TrackingImportJob per record.
- **TrackingImportJob** — Receives User + single record, resolves tracker via TrackerFactory, calls `track()`.

## Controllers & Routes (all behind auth:sanctum + verified)
- `GET /tracking` — TrackingController@index (renders Livewire TrackingTable)
- `GET /tracking/new` — TrackingController@create (single + CSV form)
- `POST /tracking` — TrackingController@store (via TrackingRouter)
- `GET /tracking/{tracker}` — TrackingController@show (details + UPS activity history)
- `POST /tracking/{tracker}/update` — TrackingController@update (manual refresh via TrackerFactory)
- `POST /tracking/import` — ImportTrackingController@create (CSV parse, validate, queue DelegateTrackersJob)
- `GET /tracking/template` — ImportTrackingController@downloadTemplate

## Livewire
- **TrackingTable** — Paginated (10/page), searchable (tracking_number, reference_id, reference_name), filterable (carrier dropdown, date range), sortable columns. All params in query string.

## Validation Rules
- **UpsTrackingNumber** — 1Z+16 alnum, 12/9/26 digits, T+10 digits
- **UspsTrackingNumber** — 20/26/30 digits, 91-95 prefix+20, EC+9+US, letter patterns
- **FedexTrackingNumber** — 12/14/15/20/22/34 digits, 96+20, 7+10

## Integrations
- **UPS** — `UPS_CLIENT_ID`, `UPS_CLIENT_SECRET`, `UPS_TOKEN_URL`, `UPS_TRACK_URL`
- **USPS** — `USPS_CONSUMER_KEY`, `USPS_CONSUMER_SECRET`, `USPS_TOKEN_URL`, `USPS_TRACK_URL` (Note: USPS added IP Agreement access controls 4/1/2026)
- **FedEx** — `FEDEX_API_KEY`, `FEDEX_SECRET_KEY`, `FEDEX_TOKEN_URL`, `FEDEX_TRACK_URL`
- All tokens cached with 5-min pre-expiry buffer. Config in `config/services.php`, creds in `.env`.

## Database
- **trackers** — user_id (FK), carrier, tracking_number, reference_id, reference_name, reference_data (JSON), recipient_email, recipient_name, origin, destination, location, status, status_time, delivery_date, delivered_date
- **tracker_data** — trackers_id (FK cascade), data (JSON). One-to-one with trackers.
- Queue: database driver, `jobs` table. Failed jobs in `failed_jobs`.

## Key Patterns
- Factory pattern for carrier dispatch (TrackerFactory)
- Strategy pattern via CarrierTracker interface
- API layer (Http clients) separated from tracking layer (business logic)
- CSV import fan-out: DelegateTrackersJob -> N x TrackingImportJob
- Status normalization: raw carrier strings -> TrackerStatus enum via per-carrier factory methods
- Show view activity history only renders for UPS response shape (USPS/FedEx history not yet rendered)
