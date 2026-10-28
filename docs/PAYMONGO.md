# Connecting PayMongo

DentAI uses **PayMongo Hosted Checkout (v2)**: we create a checkout page for the bill, the patient pays on PayMongo's
secure page (GCash, Maya, card, QR Ph…), and PayMongo tells us by a **signed webhook**. The bill is marked paid only
when that webhook arrives, so a patient can't fake it by opening the "success" URL.

## Steps

1. **Create a PayMongo account** at https://dashboard.paymongo.com/signup. Their docs say you need a completed
   account (KYC) and a test secret key to start.
2. **Copy your secret key** from *Developers → API keys* (`sk_test_…` for testing, `sk_live_…` for real money).
   Never put it in front-end code or Git.
3. **Turn on the payment methods** you want in the dashboard (*Settings → Payment Methods*). The `.env` list
   `PAYMONGO_METHODS` must only contain methods that are enabled there.
4. **Register the webhook once** (don't create it from code): *Developers → Webhooks → Add endpoint*
   - URL: `https://YOUR-DOMAIN/webhooks/paymongo`
   - Event: `checkout_session.payment.paid`
   - Copy the endpoint's **secret key**.
5. **Fill `.env`:**
   ```
   APP_URL=https://YOUR-DOMAIN
   PAYMONGO_SECRET_KEY=sk_test_xxxxxxxx
   PAYMONGO_WEBHOOK_SECRET=whsk_xxxxxxxx
   PAYMONGO_METHODS=gcash,paymaya,card,qrph
   ```
   then `php artisan config:clear`.
6. **Try it:** as assistant open a visit → create a bill → bill page → **Online payment: Turn on**.
   Log in as the patient (phone + code) → open the bill → **Pay online**. Pay with PayMongo's test details
   (see their *Testing* page). The bill flips to **Paid** within seconds of the webhook.

## Testing on your own computer

PayMongo can't reach `localhost`. Start a tunnel and use its HTTPS address for both `APP_URL` and the webhook URL:

```bash
cloudflared tunnel --url http://localhost:8000     # or: ngrok http 8000
```

## Going live

Test and live mode are separate: switch to `sk_live_…`, **create a second webhook endpoint in live mode**, put its new
secret in `PAYMONGO_WEBHOOK_SECRET`, and run `php artisan config:clear`.

## How it's built (files)

| File | Job |
|---|---|
| `config/paymongo.php` | keys + allowed methods |
| `app/Services/PaymongoService.php` | creates the checkout, verifies the signature, records the payment (idempotent) |
| `app/Http/Controllers/PaymongoWebhookController.php` | receives the webhook |
| `app/Http/Controllers/Portal/PortalController.php` | `pay()` – only works if staff turned **Online payment** ON |
| `routes/web.php` | `POST /webhooks/paymongo` (CSRF-exempt, protected by the signature) |

Signature rule (from PayMongo's docs): HMAC-SHA256 of `"{t}.{raw body}"` using the webhook secret, compared with `te`
(test) or `li` (live) in the `Paymongo-Signature` header.

## Troubleshooting

- **Pay button is greyed out** → staff switch is OFF, or `PAYMONGO_SECRET_KEY` is empty.
- **“Online payment could not be started: …”** → PayMongo's message is shown; usually a disabled method in `PAYMONGO_METHODS`
  or an amount below PayMongo's minimum.
- **Paid on PayMongo but bill still unpaid** → webhook not arriving. Check the delivery log in the PayMongo dashboard
  (you can retry a delivery), the URL, HTTPS, and that the secret matches the mode (test vs live).
- **Webhook gets HTTP 419** → your Laravel version still checks CSRF on it. In `bootstrap/app.php` add
  `->withMiddleware(function (Middleware $middleware) { $middleware->validateCsrfTokens(except: ['webhooks/paymongo']); })`.
- **HTTP 400 “Bad signature”** → wrong webhook secret, or a proxy is altering the request body.
