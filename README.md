# Vouchsafe WP

Add age verification and identity checks to your WordPress site with Vouchsafe. UK Online Safety Act–oriented, privacy-first, and simple to setup.

## Requirements

WordPress 6.8 or better

## Installation

1. Upload and activate the plugin.
2. Go to **Settings → Vouchsafe**.
3. Enter your **Client ID** and **Client Secret**, then click **Save API Credentials**.
4. Hit **Test Connection & Fetch Flows**
5. Choose the flow you'd like to use and hit **Save Selected Flow**

You can now trigger checks and review recent results without leaving WordPress.

## Features

1. **Simple setup wizard** to provide your Vouchsafe API credentials and choose a verification flow
2. **One link** to direct users to, which you can do following a form submission with most popular form builder plugins.
3. **Check recent verifications** without leaving the WordPress dashboard, with links to explore further on Vouchsafe.

## Development

**[See the contribution guidelines for this project](https://github.com/vouchsafe/vouchsafe-wp/blob/main/CONTRIBUTING.md)**

Contributions including issues and pull requests are welcome.

To run the project locally, just clone the repo. When ready to package for release, run:

```bash
make zip
```

## Further reading

- [Developer docs](https://help.vouchsafe.id/en/collections/12439003-developers)
- [Full API endpoint reference](https://app.vouchsafe.id/docs)
- [3-minute video guide](https://www.youtube.com/playlist?list=PLx6V6SSTMuF_ZNWBPnysvwmdIwboLViE8)
