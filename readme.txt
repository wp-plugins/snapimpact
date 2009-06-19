=== SnapImpact ===
Contributors: Neil Simon, Dave Angulo, Richard Grote
Tags: volunteer, ivolunteer, community, services, sidebar, widget, handson network
Requires at least: 2.0
Tested up to: 2.8
Stable tag: trunk

We make volunteering easy. The SnapImpact Plugin displays local volunteering opportunities in the blog sidebar.

== Description ==

**[SnapImpact](http://snapimpact.org/) is a free location-aware service that makes it easy for people to find volunteer opportunities and get involved in their communities.**

**The SnapImpact Plugin:**

* Displays local community volunteering opportunities in the blog sidebar.
* Please see the [Screenshots](http://wordpress.org/extend/plugins/snapimpact/screenshots/) for examples.

Thanks to [The HandsOn Network](http://handsonnetwork.org) for providing the volunteer opportunity data.

For the latest news, please follow [@SnapImpact](http://twitter.com/SnapImpact) on Twitter. 

== Installation ==

**Upgrading?**

* Please **Deactivate** the previous SnapImpact Plugin first.

**Installation**

1. Upload the SnapImpact Plugin folder to **/wp-content/Plugins/**

2. Login to your WordPress admin web page.

3. Activate the Plugin:
   - Click on the **Plugins** tab.
   - Find SnapImpact in the list of Inactive Plugins (or Recently Active Plugins).
   - Click on **Activate** to activate the SnapImpact Plugin.

4. Configure the Plugin:
   - Click on **Settings**.
   - Click on **SnapImpact**.
   - Enter your SnapImpact options.
   - Press the **Save** button to save the SnapImpact options.

5. Add the Plugin as a sidebar widget:
   - Click on **Appearance** (or Design).
   - Click on **Widgets**.
   - On the left side, next to SnapImpact, click on **Add** to make it appear in the list of **Current Widgets**.
   - Click on **Save Changes**.

6. If your theme DOES NOT support widgets, place the following line of code in your sidebar file (e.g. sidebar.php):
   - `<?php SnapImpact_sidebar (); ?>`

7. Local volunteer opportunities should now appear in your sidebar.

== Screenshots ==

1. Local volunteer opportunities shown in the blog sidebar.

2. The SnapImpact settings page.

== Change History ==

= Rev 1.1.8  2009-Jun-18 =

- Changed Plugin name from iVolunteer to SnapImpact.

= Rev 1.1.7  2009-Jun-14 =

- Removed `<li>` tags from `SnapImpact_sidebar()` to remove preceeding bullet from list.
- Fixed with the help of [Andrew Hyde](http://andrewhyde.net). Thanks for helping debug this on your Mac!

= Rev 1.1.6  2009-Jun-13 =

- Tested with WordPress 2.8.

= Rev 1.1.5  2009-Jun-13 =

- Display events in ascending order by date.

= Rev 1.1.4  2009-Jun-09 =

- Added one additional check for zero records returned.  Displays "No opportunities found."
- Updated to force all descriptions to only capitalize first char of each word.

= Rev 1.1.3  2009-Jun-09 =

- Gracefully handle zero records returned from JSON api.

= Rev 1.1.2  2009-Jun-08 =

- Updated text on the options page.

= Rev 1.1.1  2009-Jun-08 =

- Updated to simplify sidebar.php installation for non-widgetized themes.

= Rev 1.1.0  2009-Jun-06 =

- Upon initial setup, default to 5 opportunities to be displayed.

= Rev 1.0.9  2009-Jun-02 =

- Replaced str_ireplace() with custom function to ensure PHP4 compatibility.

= Rev 1.0.8  2009-Jun-01 =

- Updated the readme.

= Rev 1.0.7  2009-Jun-01 =

- Added 2 screenshots: Local volunteer opportunities shown in the blog sidebar, The SnapImpact admin settings page.

= Rev 1.0.6  2009-Jun-01 =

- Added admin option to choose number of events to display in sidebar: 5, 10, or 15.

= Rev 1.0.5  2009-Jun-01 =

- Reformatted date, time, city/state/zip, and phone number for continuity.
- If no response from server, timeout after 5 seconds and display "please try later".

= Rev 1.0.4  2009-May-29 =

- Reformatted sidebar display.

= Rev 1.0.3  2009-May-29 =

- Added live datafeed.

= Rev 1.0.2  2009-Feb-17 =

- Localized to support multiple languages.

= Rev 1.0.1  2009-Feb-16 =

- Initial revision.
