=== PropertyHive ValPal ===
Contributors: PropertyHive,BIOSTALL
Tags: propertyhive, property hive, property, real estate, software, estate agents, estate agent, property management, valpal, valuation, appraisal
Requires at least: 3.8
Tested up to: 6.4.3
Stable tag: trunk
Version: 2.0.0
Homepage: https://wp-property-hive.com/addons/valpal-instant-valuation/

This add on for Property Hive allows users to get an instant valuation.

== Description ==

This add on for Property Hive allows your users to get an instant valuation through your WordPress website powered by Property Hive by seamlessly integrating with ValPal.

Simply add the shortcode [valpal] where you wish the instant valuation form and results to appear.

== Installation ==

= Manual installation =

The manual installation method involves downloading the Property Hive ValPal Add-on plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

Once installed and activated, you can access the settings for this add on by navigating to 'PropertyHive > Settings > ValPal' from within Wordpress. Then enter the details provided to you by ValPal.

Add the shortcode [valpal] where you wish the instant valuation form and results to appear.

= Updating =

Updating should work like a charm; as always though, ensure you backup your site just in case.

== Changelog ==

= 2.0.0 =
* Property Hive Pro compatibility, disabling the functionality if a pro user but no valid license in place
* New filter 'propertyhive_valpal_allowed_postcodes' to restrict postcodes allowed to be searched
* New action 'propertyhive_valpal_send_success' when a ValPal request is submitted successfully
* PHP 8.2 compatibility
* Declared support for WordPress 6.4.3

= 1.0.7 =
* Also show normal Google map on results as well as existing street view
* Added new filters to map and street view can be deactivated: 'propertyhive_valpal_show_map_in_results' and 'propertyhive_valpal_show_street_view_in_results'
* Declare support for WordPress 6.2

= 1.0.6 =
* Correct variable name to prevent PHP fatal error
* Declare support for WordPress 5.8.1

= 1.0.5 =
* Bring add on up to date with latest ValPal postcode lookup and API requests
* Corrected issue whereby you couldn't untick the address lookup checkbox setting
* Declare support for WordPress 5.5.3

= 1.0.4 =
* Only show sales or lettings options when associated department active in settings
* Added new GDPR setting so a disclaimer can be added and must be agreed before the form is submitted
* Tweaks to address lookup URL. Still not right though and should look to swap out to use our own Postcode Lookup add on
* Declare support for WordPress 5.5.1

= 1.0.3 =
* Catered for &pound; being sent back in response from ValPal
* Declare support for WordPress 5.2.2

= 1.0.2 =
* Added ability to customise amounts returned by percentage using new 'propertyhive_valpal_translation_array' filter
* Added settings link to main plugins page
* Declare support for WordPress 5.1

= 1.0.1 =
* Move form HTML to template so can be overwritten by creating propertyhive/valpal-form.php in theme
* Declare support for WordPress 4.9.5

= 1.0.0 =
* First working release of the add on