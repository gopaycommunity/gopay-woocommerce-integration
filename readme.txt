=== GoPay for WooCommerce ===
Contributors: GoPay
Tags: WooCommerce, GoPay
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.32
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WooCommerce and GoPay payment gateway integration

== Description ==
Official plugin for integrating the GoPay payment gateway with WooCommerce. Fast, secure, and simple – no external setup needed.

= Key Features =
* Integrated support for multiple payment methods: cards, Apple Pay, Google Pay, bank transfer, QR payments, saved cards.
* Responsive checkout – works seamlessly on both desktop and mobile.
* Subscriptions and recurring payments (compatible with WooCommerce Subscriptions).
* Security: PSD2, 3D Secure, and encrypted data transfer.
* Support for multiple currencies and 19+ languages.
* Automatic notifications (webhooks) about payment and order status.
* Refunds, cancellations, and payment renewals directly from WooCommerce admin.

= Supported Payment Methods =
* **Credit and debit card payments** – Accept secure card payments directly on your site, with support for PSD2 and 3D Secure.
* **Google Pay** – Fast and simple payments using saved cards through Android devices or web browsers.
* **Apple Pay** – Seamless checkout with Apple’s secure payment platform, optimized for iPhone, iPad, and Mac.
* **Click to Pay** – Modern payment method supported by Visa and Mastercard, enabling one-click checkout with stored cards.
* **Bank transfer** – Standard bank transfers for customers preferring direct payments from their bank account.
* **QR payments** – Convenient QR code payments widely used in the Czech Republic and Slovakia.
* **Saved cards** – Customers can store their card details securely for faster one-click payments in the future.

= Why Choose GoPay? =
* Trusted by more than 19,000 merchants in the Czech Republic, Slovakia, and beyond.
* Wide range of modern and local payment methods in one integration.
* Fully secure and compliant with European standards (PSD2, 3D Secure).
* Easy setup and administration inside WooCommerce.
* Local support and documentation in English and Czech.

= Need help? =
If something doesn't work as expected, please reach out via our [support page](https://wordpress.org/support/plugin/gopay-gateway/) first. We'll do our best to resolve your issue.

= Plugin functions: =
* 56 payment methods including Google Pay, Apple Pay, Click to Pay and PSD2 bank transfers
* 9 currencies and 19 language localizations
* mobile and desktop payment gateway
* remember mode on the payment gateway - customer can remember payment card details and pay just by one click
* payment cancellation
* recurring payments
* payment restart

== Installation ==
First of all, install WordPress and WooCommerce, then upload and configure the plugin by following the steps below:
1. Copy the plugin files to the '/wp-content/plugins/' directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the Plugins screen in WordPress.
3. Configure the plugin by providing goid, client id and secret to load the other options (they can be found on your GoPay account).
4. Finally, choose the options you want to be available in the payment gateway (payment methods and banks must also be enabled in your GoPay account).

== Frequently Asked Questions ==

= How will I receive my payments? =
Successful payments will be automatically credited to the GoPay merchant account. We will send it from the merchant account to the registered bank account at the time of clearing.

= How often is clearing done? =
We offer 3 clearing frequencies - daily, weekly and monthly.

= Do I need to have a bank account to receive payments? =
Yes, it is necessary to register a bank account to receive a clearing.

= How do I know that the customer has successfully paid? =
After a successful payment, we send a notification about the change of the payment status. You can also check the payment status in your GoPay merchant account.

== Screenshots ==

1. Card payment - desktop version
2. Card payment - mobile version
3. Saved cards - desktop version
4. Saved cards - mobile version
5. Payment method selection - desktop version
6. Payment methods selection - mobile version

= Minimum requirements =
* WordPress 5.8
* PHP version 8.1
* WooCommerce version 7.0
* WooCommerce Subscriptions¹ 4.0

1 - WooCommerce Subscriptions must be installed if you need to deal with recurring payments.

== Changelog ==

= 1.0.32 =
* Declared compatibility with other shipping Wordpress plugins

= 1.0.31 =
* Add fixes and compatibility improvements when using WooCommerce Subscriptions plugin

= 1.0.30 =
* Fixed timeout notification causing order status change

= 1.0.29 =
With latest plugin update we are introducing the following changes:
* New Feature: Saved Payment Cards
Customers can now store and manage their payment cards directly within the checkout process, eliminating the need to manually enter card details on every purchase.
This feature can be enabled in the plugin settings. Please note that your GoPay merchant account must support this functionality before enabling it.
* New Feature: Global Sales for Virtual & Downloadable Products
Virtual and downloadable products can now be sold without any country restrictions, making them available to customers worldwide.
This option can be enabled in the plugin settings.
* Compatibility Updates
The release adds support for the latest WordPress version 7.0 and WooCommerce version 10.8.1.

= 1.0.28 =
* Compatibility with WPML - added missing file

= 1.0.27 =
* Compatibility with WPML

= 1.0.26 =
* Payment status check adjustment

= 1.0.25 =
* Integrate payment_complete method for paid orders
* Supports latest WooCommerce version 10.5.0

= 1.0.24 =
* Minor adjustment in thank you order hook
* Plugin version added to the API parameters
* Supports WooCommerce version 10.4.3

= 1.0.23 =
Build: Update Composer dependency set to latest available versions

= 1.0.22 =
* This release includes minor fixes, code improvements, and a general refactoring of the plugin.
* Applied a CSS update to correct the display of payment icons.
* Added full block-based checkout support for payment methods, based on a contribution by adammaly.
* Updated the payment handling function for virtual and downloadable products.
* Added the missing BLIK option within Poland’s bank payment methods.
* Ensured compatibility with the latest WordPress version 6.9.

= 1.0.21 =
* This version includes minor fixes and improvements to support the latest WordPress version.
* Added a fix when using the payment plugin without WooCommerce.
* Supports WooCommerce version 10.3.4.

= 1.0.20 =
Fix: Block-based checkout issue when using the Local Pickup option; updated readme description text.

= 1.0.19 =
Feature: create admin notifications for user plugin feedback

= 1.0.18 =
Fix script issue causing appendChild failure on gateway load in some specific WordPress themes.

= 1.0.17 =
Fix the error for an undefined GoPay reference that prevents the payment gateway from opening in some popular WordPress themes.

= 1.0.16 =
Updated supported WordPress version to v6.8.1 and WooCommerce to v10.1.0.

= 1.0.15 =
Add extra API parameters and fix an issue that, in certain cases, prevents virtual products from being added to checkout.

= 1.0.14 =
Updated supported WordPress version to v6.7.1 and WooCommerce to v9.6.1.

= 1.0.13 =
Introduced new payment methods: Twisto and Skip Pay. The checkout payment methods language now aligns with the WordPress site language.

= 1.0.12 =
Plugin compatibility with WC Block based checkout, Fix db duplicate entry error after payment status check

= 1.0.11 =
Replace get_post_meta with get_meta to fully leverage the performance benefits of HPOS and prevent issues with payment refunds if compatibility mode is disabled.

= 1.0.10 =
Fix available supported shipping methods in plugin configuration

= 1.0.9 =
Fix transport method settings for downloadable products

= 1.0.8 =
Add HPOS support

= 1.0.7 =
Removed Docker files and updated readme-dev

= 1.0.6 =
Update PHP version and libraries to the latest supported releases

= 1.0.5 =
Correction added for inconsistency of total amount in cents

= 1.0.4 =
Add fix when the order is not Object type

= 1.0.3 =
Translation fix for payment options

= 1.0.2 =
Fixed issues when enabled payment instruments was empty

= 1.0.1 =
Fixed variable products error

= 1.0.0 =
WooCommerce and GoPay gateway integration.
