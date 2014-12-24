=== Wordpress TextIt SDK ===
Contributors: amber.au@gmail.com
Tags: sdk, textit
Requires at least: 3.1
Tested up to: 4.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

TextIt is a platform for building interactive SMS applications. This SDK makes using the TextIt API v1 in your Wordpress plugin easy.

== Description ==

**How it works**

1. Create your TextIt account 
1. Go to your TextIt account page and enter {your wordpress url}/textit-webhook-receiver as your WebHook URL 
1. Paste the generated API token in the Wordpress TextIt SDK settings page and save changes 
1. Go to Settings > Permalinks and save to refresh the Wordpress rewrite rules 
1. Now you can use the textit_webhook_event hook in your custom plugin or theme to respond to TextIt webhook events, and WordpressTextItSDK::textItDo() to call the TextIt REST API

**TextIt API**
Full documentation is available: https://textit.in/api/v1

Quick method reference:

* contacts - To list or modify contacts. 
* fields - To list or modify contact fields. 
* messages - To list and create new SMS messages. 
* relayers - To list, create and remove new Android phones. 
* calls - To list incoming, outgoing and missed calls as reported by the Android phone. 
* flows - To list active flows. 
* runs - To list or start flow runs for contacts. 
* campaigns - To list or modify campaigns on your account. 
* events - To list or modify campaign events on your account. 
* boundaries - To retrieve the geometries of the administrative boundaries on your account. 

**textItDo()**

To call the TextIt API, use `WordpressTextItSDK::textItDo($method, $args, $http)`

`$method` - string - One of the TextIt API methods listed above

`$args` - array - 2 dimensional array containing argument names and values. Details of accepted arguments in the TextIt documentation

`$http` - string - Either `GET` or `POST`, depending on whether you want to list or add / modify data

The return value will be either an array with a TextIt API response or an exception

E.g. `WordpressTextItSDK::textItDo( 'contacts', array(), 'GET' )` would return a list of contacts from your TextIt account

**textit_webhook_event** 

To respond to TextIt webhook events (e.g. Incoming Messages, Outgoing Messages, Incoming Calls, Outgoing Calls, Relayer Alarms) use `add_action( 'textit_webhook_event', 'my_custom_function', 2, 1 )`

Your custom function should accept a single argument - `$event` - an array containing the data TextIt posted via the webhook

== Installation ==

1. Upload `wp-textit-sdk` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress