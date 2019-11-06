=== VivoKey OpenID Connect ===
Contributors: vivokey
Donate link: https://github.com/VivoKey/plugin-wordpress
Tags: Authentication, OpenID Connect, OAuth2, VivoKey
Requires at least: 4.6
Tested up to: 5.2.4
Stable tag: 1.2
Requires PHP: 5.2.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Authenticate your WordPress account by scanning your VivoKey cryptobionic implant instead of using your username and password.

== Description ==

VivoKey is a digital identity platform that links your online digital identity VivoKey profile with one or more implantable cryptobionic NFC transponders. This plugin will enable you to use your VivoKey digial identity profile to authetnicate your WordPress account by scanning your VivoKey cryptobionic implant, rather than enter your username and password. The plugin utilizes VivoKey's web-standard OpenID Connect API. Details about the API can be found at [https://vivokey.com/api](https://vivokey.com/api)

Check out this video that details how to configure and use the VivoKey OpenID Connect plugin for WordPress:

https://www.youtube.com/watch?v=N30D9uImIxA

To see a list of available VivoKey plugins, please check here: [https://vivokey.com/plugins](https://vivokey.com/plugins)

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/vivokey-openid-connect` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the **Settings->VivoKey OpenID** screen to configure the plugin

== Frequently Asked Questions ==

= Is a VivoKey cryptobionic implant required to use the VivoKey OpenID Connect plugin? =

Yes, the VivoKey OpenID Connect API is simply the interface and protocol between WordPress and the cryptobionic implant.

= Where can I get a VivoKey cryptobionic implant? =

Visit [https://vivokey.com/distributors](https://vivokey.com/distributors) to find out where you can purchase a VivoKey cryptobionic implant.

== Changelog ==

= 1.2 =
* Resolved issue with routing and state that interfered with other plugins that use ajax calls

= 1.1 =
* Code clean up, input sanitizing, etc.

= 1.0 =
* Initial release

== Contribute ==

Please feel free to clean up the code, add features and fix bugs. The current code serves only as a basic implementation with many potential improvements. 

[https://github.com/VivoKey/plugin-wordpress](https://github.com/VivoKey/plugin-wordpress)

**Pull requests welcome.**