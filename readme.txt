=== Vouchsafe – Easy ID & Age Verification for WordPress ===
Contributors: vouchsafe
Tags: age verification, age assurance, online safety act, identity verification, kyc, aml, compliance
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Add age verification and identity checks to your WordPress site with Vouchsafe. UK Online Safety Act–oriented, privacy-first, and simple to configure.

== Description ==

Vouchsafe helps you verify users quickly and inclusively, from **age checks** to full **KYC/AML**. This plugin connects your WordPress site to your Vouchsafe team so you can send your users to verify themselves and manage the results without leaving the WordPress dashboard.

> **Age verification**
> If you provide user-to-user or adult content, you may need proportionate age assurance under the UK Online Safety Act. Vouchsafe offers age verification flows that can help you meet those duties while minimising friction. This plugin is the first step: connect your site, verify your credentials, and monitor results from wp-admin.

**What’s included?**
- Settings page to provide your Vouchsafe API credentials and choose a verification flow
- A link to direct users to to get them verified, which you can do following a form submission with most popular form builder plugins.
- See recent verifications without leaving the WordPress dashboard, with links to explore further on Vouchsafe.

> A Vouchsafe account is required. Some functionality depends on services provided by Vouchsafe (https://vouchsafe.id).

== Features ==

- **Age verification ready**: use Vouchsafe’s age assurance flows
- **UK Online Safety Act–oriented** copy and setup guidance
- Fast admin setup: Client ID/Secret + “Test connection”
- Status monitoring: view recent results sorted by created date
- Friendly flow names (ID → Name mapping)

== Installation ==

1. Upload and activate the plugin.
2. Go to **Settings → Vouchsafe**.
3. Enter your **Client ID** and **Client Secret**, then click **Save API Credentials**.
4. Hit "Test Connection & Fetch Flows"
5. Choose the flow you'd like to use and hit "Save Selected Clow"

You can now trigger checks and review recent results without leaving WordPress.


== Frequently Asked Questions ==

= Is this compliant with the UK Online Safety Act? =
Vouchsafe provides **age verification flows** that can help you meet duties under the Act. Compliance depends on how you implement age assurance in your service and risk profile. This plugin connects your WordPress admin to Vouchsafe for monitoring; front-end enforcement (gating) depends on your integration choices and site setup.

= Can I gate pages or products based on age right now? =
This initial release focuses on triggering checks and monitoring the results. Front-end age gates/checkout enforcement will be added in future releases.

= Can I use this for Right to Work / Right to Rent / KYC/AML checks? =
Yes. Vouchsafe supports flows commonly used for **Right to Work**, **Right to Rent**, and **KYC/AML**. This plugin helps you trigger checks and **review recent results**. Any front-end process (e.g., collecting documents) is handled via Vouchsafe’s flows and your site integration.

= Does the plugin store personal data in WordPress? =
No. Only your API credentials in WordPress options. Verification data is fetched from Vouchsafe and rendered in the admin; the plugin does not persist verification payloads.

= What about privacy and data protection? =
This plugin makes outbound requests to Vouchsafe (vouchsafe.id) to authenticate and fetch verification metadata (e.g., names, emails, statuses).  
- **Controller**: You (site owner)  
- **Processor**: Vouchsafe (per your agreement)  
Ensure your privacy notice reflects this processing.

You can see the Vouchsafe [privacy notice here](https://vouchsafe.id/privacy/).