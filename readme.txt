=== MacTrak for FindMeSpot (Spot Tracker) ===
Contributors: @ShaunMcCance
Tags: Spot, GPS, FindMeSpot, Find Me Spot, Tracker, Mapping, Google Maps, GPS track, Journey
Donate link: http://www.shaunmccance.com/buymeacoffee
Tested up to: 4.8.2
Stable tag: 1.4.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

... See it at: www.ChasingTheSunrise.org/tracker ... A WP plugin to save Find Me Spot (Spot Tracker) GPS route data and messages to your wordpress blog for backup and to display them on a Google Map via shortcode. 

== Description ==
... See it at: www.ChasingTheSunrise.org/tracker ... A WP plugin to save Find Me Spot (Spot Tracker) GPS route data and messages to your wordpress blog for backup and to display them on a Google Map via shortcode.

Spot Tracker will process data pings but will not save them or display them on the Find Me Spot website beyond a few days. This Plugin will save them from the Spot servers at regular intervals to your wordpress site and allow them to be displayed to the public or downloaded by site admins for backup. I developed this plugin for the interactive map of my journey Chasing the Sunrise; any problems or suggestions please email me, if it works and you like it please consider buying me a coffee and help me keep cycling :-)


Required Notice - This plugin interfaces with the following 3rd Party services:
1. Google Maps API
2. Spot (Find Me Spot) API
Further details can be found in the FAQ

== Installation ==
Install and Activate within Wordpress; Mactrak will appear in menu.

Refresh your browser to ensure full functionality.

After installation add Google API and Spot GLID keys on settings page or upload pre-existing data as desired.

User Privilages: Ensure you are an Editor or above to have full control within MacTrak. 

== Frequently Asked Questions ==
 
= What Third Party services does MacTrak use? =
 
MacTrak interfaces with the following 3rd Party services:

1. Google Maps for display of route and marker data in the viewers browser via the Javascript API interface. A specific Google API key is required (available free from Google - follow link on setup page) and is stored within you WordPress site. This key is sent to the Google servers to allow access to the Map object that is rendered in the users browser overlaid with your route and marker data as desired.

2. Spot (Find Me Spot) servers for download of GPS tracking and message data via the API interface provided by Spot LLC (http://www.findmespot.com). A user specific Spot share page GLID is stored within your WordPress site and is used to retrieve relevant information from the Spot severs via WordPress\' built in external HTTP functionality. No other data is sent to external servers. Received GPS data is validated and stored locally within your website database for display, backup and future use as desired.


== Screenshots ==
 
1. MacTrak Admin main screen as used on www.ChasingTheSunrise.org/tracker

== Changelog ==
v1.4.3 - First public release
v1.4.4 - Bug fix, php warning in remote API call script 
v1.4.5 - Code cleanup, unused marker table removed