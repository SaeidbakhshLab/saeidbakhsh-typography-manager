=== Saeidbakhsh Typography Manager ===
Contributors: saeidbakhsh
Tags: typography, fonts, font manager, custom fonts, woocommerce
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.3.16
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage typography for HTML, WordPress, and WooCommerce elements using system fonts, WordPress Font Library fonts, and remote font URLs.

== Description ==

Saeidbakhsh Typography Manager gives you full control over your website typography from:

Appearance > Typography Manager

Easily customize fonts, sizes, spacing, colors, and text direction for your WordPress website.

Key features:

* Choose system fonts, WordPress Font Library fonts, or direct HTTPS font URLs.
* Style HTML, WordPress, and WooCommerce elements.
* Add custom CSS selectors for advanced typography control.
* Configure responsive font sizes, font weights, spacing, colors, and text direction.
* Search fonts and elements instantly.
* Edit multiple typography groups before saving changes.
* Adjust CSS priority to work better with themes and page builders.
* Preview generated CSS before applying changes.
* Reuse compiled styles with support for popular caching plugins.

WooCommerce support is optional. WooCommerce typography controls remain inactive when WooCommerce markup is not detected.

== Screenshots ==

1. General Element Typography Settings  
Configure typography options for website elements.

2. Heading Typography Controls  
Manage typography settings for H1 to H6 headings.

3. Font Library and Weight Management  
Add and manage fonts with different weights.

4. Custom Element Selector Settings  
Apply typography rules to custom CSS selectors.

== Installation ==

1. Install and activate the plugin.
2. Go to Appearance > Typography Manager.
3. Select fonts, customize typography settings, and click Save all changes.

== Frequently Asked Questions ==

= Can I edit multiple groups before saving? =

Yes. You can switch between typography groups and continue editing before saving all changes together.

= How does cache compatibility work? =

The plugin generates reusable styles and updates them when typography settings change.

Supported caching and optimization plugins include WP Rocket, LiteSpeed Cache, W3 Total Cache, WP Super Cache, WP Fastest Cache, and Autoptimize.

If changes are not visible, clear your website, hosting, or CDN cache. CSS optimization features may require additional configuration.

= Does the plugin upload local fonts? =

No. Local font management is handled through the WordPress Font Library.

= Can I use a remote font? =

Yes. You can use direct HTTPS font URLs for supported formats such as WOFF2, WOFF, TTF, and OTF.

Remote fonts are loaded directly by visitors' browsers from the configured host.

= Does it support WooCommerce? =

Yes. The plugin includes typography controls for common WooCommerce areas such as shop pages, products, cart, checkout, and account sections.

= What are Strong and Maximum priority modes? =

Strong increases CSS priority for themes with more specific styles.

Maximum provides the highest priority mode for situations where builders or inline styles override typography settings.

= Does uninstalling the plugin remove WordPress Font Library fonts? =

No. WordPress Font Library fonts remain available after uninstalling the plugin.

== Privacy and external resources ==

Saeidbakhsh Typography Manager does not collect personal data, send telemetry, or download fonts from remote servers in the background.

When an administrator configures a remote font URL, visitors' browsers request the font file directly from the selected host.

The remote host may receive standard HTTP request information such as IP address and user agent. Website administrators are responsible for the privacy policy and terms of any external services they configure.

== Third-party assets ==

Vazirmatn is bundled with the plugin administration interface.

It is licensed under the SIL Open Font License 1.1.

The license file is available at: assets/fonts/OFL.txt

Source:
https://github.com/rastikerdar/vazirmatn

== Changelog ==

= 2.3.16 =

* Renamed the plugin to Saeidbakhsh Typography Manager.
* Improved plugin menu placement and administration interface wording.
* Simplified guides and release notes.

= 2.3.15 =

* Improved WordPress coding standards compliance.
* Removed development logging from cache integrations.
* Improved version handling.

= 2.3.14 =

* Prevented typography styles from affecting the WordPress admin toolbar.

= 2.3.13 =

* Preserved unsaved edits when switching between settings sections.
* Improved validation, save feedback, and protection against losing changes.

== Upgrade Notice ==

= 2.3.16 =
The plugin has been renamed to Saeidbakhsh Typography Manager. Existing settings are preserved.
