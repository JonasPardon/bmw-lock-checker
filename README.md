# bmw-lock-checker

I sometimes forget to lock my car. This small Laravel app reminds me when I arrive home
and the car is still unlocked: an iOS Shortcut fires on arrival, calls this app, the app
reads the lock state from **BMW CarData**, and sends a push notification to my phone via
[ntfy](https://ntfy.sh) if the doors are not secured.

```
iPhone arrives home (iOS Shortcuts automation)
  → POST https://<host>/api/bmw/check  (X-Api-Key)
  → app reads vehicle.cabin.door.lock.status from BMW CarData
  → not SECURED/LOCKED? → ntfy push "BMW not locked"
  → one tap in the My BMW app to lock it
```

## Why not just lock the car remotely?

Because no BMW API that is open to third parties can do that (checked September 2026):

| | |
|---|---|
| **BMW CarData** (EU/UK, the official customer API) | Read-only. Its swagger has nine GET endpoints plus create/delete of "containers" that define which data you may read. No command endpoint. |
| **MyBMW app backend** (what `bimmer_connected` / Home Assistant used) | Blocked for third parties since 2025-09-29. |
| **Siri Shortcuts lock action in the My BMW app** | Needs Digital Key Plus (UWB), i.e. iDrive 8 or newer. Not available on iDrive 7.x. |

So the app reads instead of writes, and the `POST /api/bmw/lock` route deliberately answers
`501` with that explanation. The lock state itself is readable via the CarData descriptor
`vehicle.cabin.door.lock.status` (`SECURED`, `LOCKED`, `SELECTIVE-LOCKED`, `UNLOCKED`,
`INVALID`, `UNKNOWN`).

## Requirements

- PHP 8.4, Composer, a BMW ID with the car mapped in the My BMW app (EU/UK account).
- A **CarData client ID** from the
  [CarData customer portal](https://bmw-cardata.bmwgroup.com/customer/public/api-documentation)
  with *CarData API* enabled.
- The [ntfy](https://ntfy.sh) app on your phone, subscribed to a long random topic.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Fill in `.env`:

| Variable | Meaning |
|---|---|
| `BMW_CLIENT_ID` | client ID generated in the CarData portal |
| `BMW_API_KEY` | shared secret your Shortcut sends (`X-Api-Key` header or `?key=`) |
| `NTFY_TOPIC` | your ntfy topic |
| `BMW_VIN`, `BMW_CONTAINER_ID` | filled in after the steps below |

Then authenticate (OAuth device code flow, prints a URL to open in a browser):

```bash
php artisan bmw:login
```

Find your VIN and create the data container that selects the lock/door/window descriptors:

```bash
php artisan bmw:vehicles
php artisan bmw:create-container
```

Put the VIN and the returned `containerId` in `.env`, then test:

```bash
php artisan bmw:check --raw      # prints the lock state and all telematic values
php artisan bmw:check --notify   # sends the ntfy push when the car is not locked
```

Tokens are stored in `storage/app/bmw-tokens.json`. Access tokens last one hour and are
refreshed automatically; refresh tokens last two weeks, after which `bmw:login` is needed again.

## HTTP API

All routes require the API key.

| Route | Purpose |
|---|---|
| `GET /api/bmw/vehicles` | mapped VINs |
| `GET /api/bmw/status` | lock state |
| `POST /api/bmw/check` | lock state, sends the push when unlocked |
| `POST /api/bmw/lock` | always `501`, see above |

Example: `curl -X POST -H "X-Api-Key: $KEY" https://host/api/bmw/check`

## iOS Shortcut

Shortcuts → Automation → *When I arrive* (Home) → run immediately:

1. **Wait** 120 seconds (the car uploads its state a moment after you lock or leave it).
2. **Get Contents of URL**: `https://<host>/api/bmw/check?key=<BMW_API_KEY>`, method POST.

## Notes

- CarData allows 50 REST requests per day per client. A commented schedule in
  `routes/console.php` shows a 30-minute poll that stays within that.
- Values are event based, not live. Expect a delay of a minute or two after locking.
- CarData also offers an MQTT stream (`customer.streaming-cardata.bmwgroup.com:9000`,
  username = GCID, password = id_token). Not implemented here.
- `php artisan test` runs offline tests with faked HTTP.

## Sources

- [CarData customer portal & docs](https://bmw-cardata.bmwgroup.com/customer/public/api-documentation)
- [CarData swagger](https://bmw-cardata.bmwgroup.com/customer/public/assets/swagger/swagger-customer-api-v1.json)
- [Customer Telematics Data Catalogue](https://mybmwweb-utilities.api.bmw/en-gb/utilities/bmw/api/cd/catalogue/file)
- [bimmer_connected](https://github.com/bimmerconnected/bimmer_connected) and [discussion #745](https://github.com/bimmerconnected/bimmer_connected/discussions/745)
