=== Plugin Name ===  
Contributors: Justin_Maxwell_  
Donate link: n/a  
Tags: micropayments, microdonations, monetisation, monetization, fundraising,   button, integration, shortcode  
Requires at least: 3.0.1  
Tested up to: 3.9.3  
Stable tag: 1.2.34  
License: GPL3  
License URI: http://www.gnu.org/licenses/gpl-3.0.html  

tibs are tiny payments typically around 15 pence (25 cents).  

This plugin makes collecting tibs as either micropayments or microdonations simple for WordPress users.

tib buttons are placed on the site using either widgets or shortcodes.

=== Description ===
# Description

## tib 
/tɪb/  
*pl.* tibs,  *v.tr* tibbed  
*s.a.* tibber, tibbee

**noun:**  
A small online payment, typically around 15p (25¢), sent by a tibber to a tibbee, either as payment for access to content or a service, or as a gratuity.

**verb:**  
A request to tibdit to transfer a micro-payment, with a value previously specified by the tibber, to the tibbee.

## tibdit
tibdit provides a simple, original approach to collecting micropayments from visitors.

Users, or *tibbers*, pre-define their *tib* amount with a value where they 'won't have to think twice', and purchase a bundle of these tibs.

Publishers, or *tibbees* can collect tibs from tibbers simply by linking to the tibdit web application from their site.  There is no need to register or create any sort of account with tibdit to use our system to collect micropayments and microdonations through our service.  All that is required is a bitcoin address. Links to some suggested ways obtain one are included in the instructions on the dashboard settings page.

This plugin automates the process for WordPress site operators, including the receipt and processing of the token returned from the tibdit application which acknowledges the payment of a tib and displays a count of tibs received.   This counter can be site-wide, widget-specific, or post-specific.

To get a feel for how tibdit works, please visit http://demo.tibdit.com



== Installation ==

1. Install and activate the tibdit plugin from the Plugins menu in your WordPress Dashboard.

2. Go to the new tibdit page in the Settings menu of the Dashboard, read through the instructions there, and configure:  
    * A title and intro text to appear when the widget is used,
    
    * Your bitcoin address,
    
    * How many days the site should acknowlege a users tib.
    
          The tibbing button is deactivated with a checkmark overlayed for this period.
    
1. Place either the widget, or the shortcodes [tib_post] and [tib_site] at the locations you wish to display a tib button.

tibdit provides a testmode, where you can experiment with the plugin and the tibdit service without using actual currency.  Details are at the bottom of the settings page.

If bitcoin concerns you as perhaps to complex, follow the simple instructions on the settings page.  You can easily start collecting tibs now and get to grips with bitcoin later on.


== Frequently Asked Questions ==

### what is tibdit? 

tibdit is a system that enables users to send microdonations and micropayments to sites publishing a tib button alongside their content or service.  Unlike other micropayment offerings, tibdit is designed so that these payments can be made 'without a second thought'.

In time, this will result in your blog receiving many tiny donations if your site visitors are appreciative, rather than hardly any with other approaches to collecting donations.

### what is a tib? 

A tib is a user-specific pre-set value that is sent to sites with the user confirms the payment.

### it sounds very complicated 

It's really not.  We suggest you look at demo.tibdit.com, and the instructions on the plugin settings page, to see just how straightforward it is.

### what is the difference between `[tib_post]` and `[tib_site]` 

The `[tib_post]` shortcode passes a subreference of `"WP_ID_nnn"` to tibdit, so it is the particular post with the wordpress id of `nnn` that is being tibbed.  The returned tib count will show the total of tibs sent for just this post.

Per-post counters are persisted in the wordpress post metadata.

On the other hand `[tib_site]` always passes a subreference of `"WP_SITE"` to tibdit. This means the counter will include the total number of tibs from `[tib_site]` buttons across the wordpress site, because the subreference is constant.  

The `"WP_SITE"` counter is persisted in the WordPress site options data.

### what about the Widget 

The widget also uses the options data to record the count of tibs, but you can manually specify a different subreference for each widget, giving each it's own counter, or share a subreference, or set it to `"WP_SITE"` if you wish.  

The widget also lets you specify widget-specific headings, and a widget-specific bitcoin address - although this is not recommended.

### how do the counters get set 

Every time someone confirms a tib to some content of yours, we send back a token via the users browser, which is collected and processed by the Plugin. This token includes the total accumulated tibs for the unique combination of bitcoin address and subreference.  It is stored by the plugin inside WordPress so it can be displayed on the button when the page or post is displayed to a user.

== Screenshots ==
##Screenshots

1. The tib button in action.  These examples are 'testmode', the yellow beaker is shown only when the plugin is configured with a bitcoin testnet address.  

2. When a tib button is clicked, the tibdit application opens in a new window/tab (popup where allowed).  If the user is an existing user with a balance of unspent tibs, they are taken directly to the tib confirmation stage.  

3. After the user confirms the tib, the popup window is closed, which triggers the WordPress window to refresh.  Any paid-for content is revealed, and the tib button is replaced by a static acknowledgement of the tib being received.  

4. This is the tibdit page within the Dashboard Settings menu.


== Changelog ==



= 1.2.34 =

* fixed tooltips

* CSS improvements

* svn glitch

= 1.2.31 =

* Improved this README file.

* Fixed an incompatibility with earlier versions of PHP.

= 1.2.30 =

Fixed a bug with plugin options not saving
Significantly increased the amount of bitcoin address validation. 
Prevented setting save with invalid bitcoin address
Further improved the CSS to avoid collissions with themes or other plugins.
Added beta icon to widget and settings page


= 1.2.22 =

Added .bd CSS class to avoid style collisions with themes or other plugins

= 1.2.21 = 

Fixed tooltip glitch

= 1.2.20 =

First version uploaded to wordpress.org
