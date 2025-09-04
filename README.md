# Vouchsafe – Easy ID & Age Verification for WordPress

Connect your WordPress admin to Vouchsafe for **age verification** (UK Online Safety Act–oriented) and **identity checks**. Fast setup, privacy-first.

## Why Vouchsafe?

- Age assurance flows to help meet UK Online Safety Act duties
- Inclusive verification options beyond photo ID
- Admin UI to test credentials and review recent checks

## What’s in this plugin (v0.1.0)

- Settings page + **Test connection**
- **Verifications** table (status, created time, email, **flow name**)
- ListFlows lookup (ID → Name)

> Requires a Vouchsafe account. Some features depend on Vouchsafe services.

## Install

1. Upload & activate the plugin.
2. **Settings → Vouchsafe** → enter Client ID/Secret → **Test connection**.
3. **Vouchsafe → Verifications** to monitor recent checks.

## Roadmap

- Front-end age gates for pages/products
- Checkout enforcement
- Optional WooCommerce integration
- Webhooks & action links

## Privacy & Security

The plugin stores API credentials in WordPress options. Verification data is fetched from Vouchsafe and shown in admin; no payloads are persisted. Admin-only screens, nonces, escaping, and network timeouts are used.

## Support

Issues & feature requests: open a ticket or contact your Vouchsafe representative.

## License

GPL-3.0-or-later
