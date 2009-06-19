<?php

/*
Plugin Name: SnapImpact
Plugin URI: http://wordpress.org/extend/plugins/snapimpact/
Description: Retrieves local community volunteer opportunities and displays them in the sidebar. After activating the Plugin, go to Widgets to add SnapImpact to your sidebar.
Version: 1.1.8
Author: Neil Simon, Dave Angulo, Richard Grote
Author URI: http://solidcode.com/
*/

/*
 Copyright 2009 SnapImpact.

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, 5th Floor, Boston, MA 02110 USA
*/

// From wordpress271/js/tinymce/plugins/spellchecker/classes/utils
include_once ('JSON.php');

// Constants
define ('SI_PLUGIN_VERSION', 'SnapImpact-v1.1.8');
define ('SI_OPTIONS',        'SnapImpact_options');
define ('SI_API_URL',        'http://snapimpact.org/server/resources/events/consolidated/');
define ('SI_URL',            'http://snapimpact.org/');

function SnapImpact_cURL ()
    {
    // Init curl session
    $hCurl = curl_init ();

    // Return response from curl_exec() into variable
    curl_setopt ($hCurl, CURLOPT_RETURNTRANSFER, 1);

    // Max seconds to allow cURL to execute
    curl_setopt ($hCurl, CURLOPT_TIMEOUT, 5);

    // Get the visitor's ip address
    if (($ip = $_SERVER ['REMOTE_ADDR']) == "127.0.0.1")
        {
        // Use this for testing on //localhost
        $ip = "67.166.52.44";
        }

    // Set the API URL
    curl_setopt ($hCurl, CURLOPT_URL, SI_API_URL . '?ip=' . $ip);

    // Exec the call
    $response = curl_exec ($hCurl);

    // Close the session
    curl_close ($hCurl);

    // Output the results
    return ($response);
    }

function SnapImpact_buildSidebar (&$buf)
    {
    $rc = 1;  // Reset to 0 on success

    // Get the SnapImpact local events list -- returned as XML
    if (($response = SnapImpact_cURL ()) == FALSE)
        {
        // This can happen when the site is found, but the API is down
        // -- just exit
        printf ("No opportunities found -- please try later.<br />");
        }

    elseif (strpos ($response, "Not Found"))
        {
        // This can happen when the site is NOT found -- just exit
        printf ("No opportunities found - please try later.<br />");
        }

    else
        {
        // Instantiate JSON object
        $json_SnapImpact = new Moxiecode_JSON ();

        // Decode the returned JSON
        if (($consolidated = $json_SnapImpact->decode ($response)) == 0)
            {
            // No data came back
            printf ("No opportunities found, please try later.<br />");
            }

        else
            {
            // Parse JSON and load sidebar buf
            parse_json ($consolidated, $buf);

            $rc = 0;
            }

        // NULL the object
        $json_SnapImpact = 0;
        }

    return ($rc);
    }

function parse_json ($consolidated, &$buf)
    {
    $organizations = $consolidated ['organizations'];
    $timestamps    = $consolidated ['timestamps'];     // iso 8601 format
    $events        = $consolidated ['events'];
    $locations     = $consolidated ['locations'];

    // Extra check -- if empty array, return
    if (empty ($locations))
        {
        printf ("No opportunities found; please try later.<br />");
        return;
        }

    $locationsNormalized = array ();
    foreach ($locations as $location) 
        {
        $locationsNormalized [$location ['id']] = $location;
        }

    $timestampsNormalized = array ();
    foreach ($timestamps as $timestamp) 
        {
        $timestampsNormalized [$timestamp ['id']] = $timestamp;
        }

    // Load existing options from wp database
    $SnapImpact_options = get_option (SI_OPTIONS);

    // Get option for number of events to show
    $maxEvents = $SnapImpact_options ['numEventsToDisplay'];

    // If not setup...
    if ($maxEvents == 0)
        {
        // Default to 5
        $maxEvents = 5;
        }

    $eventCount = 0;
    $row        = array ();
    $sortedList = array ($row);
    $arrayCount = 0;

    foreach ($events as $event)
        {
        if ($eventCount++ < $maxEvents)
            {
            $organizationIds = (array) $event ['organizationCollection'];
            $timestampIds    = (array) $event ['timestampCollection'];
            $locationIds     = (array) $event ['locationCollection'];

            $event ['organizations'] = array ();
            $event ['timestamps']    = array ();
            $event ['locations']     = array ();

            foreach ($organizationIds as $organizationId)
                {
                $event ['organizations'] [$organizationId] = $organizations [$organizationId];
                }

            foreach ($timestampIds as $timestampId)
                {
                $event ['timestamp'] = $timestampsNormalized [$timestampId];
                }

            foreach ($locationIds as $locationId)
                {
                $event ['location'] = $locationsNormalized [$locationId];
                }

            // 1st element -- eventTime -- becomes the sort key
            $sortedList [$arrayCount] ['eventTime']          = $event ['timestamp'] ['timestamp'];
            $sortedList [$arrayCount] ['eventTimeFormatted'] = "";
            $sortedList [$arrayCount] ['eventTitle']         = $event ['title'];
            $sortedList [$arrayCount] ['eventLocation']      = $event ['location'] ['city']  . ', ' .
                                                               $event ['location'] ['state'] . ', ' .
                                                               $event ['location'] ['zip'];
            $sortedList [$arrayCount] ['eventPhone']         = $event ['phone'];
            $sortedList [$arrayCount] ['eventUrl']           = $event ['url'];

            // Remove non-ascii characters from $eventTitle
            $sortedList [$arrayCount] ['eventTitle'] = preg_replace ('/[^(\x20-\x7F)]*/', '',
            $sortedList [$arrayCount] ['eventTitle']);

            // Lower the entire string, then upcase the first char of each word
            $sortedList [$arrayCount] ['eventTitle'] = ucwords (strtolower (
            $sortedList [$arrayCount] ['eventTitle']));

            // Reformat the time/date to "Monthname, dd, yyyy  [hh:mm] am/pm"
            $sortedList [$arrayCount] ['eventTimeFormatted'] = SnapImpact_format_datetime (
            $sortedList [$arrayCount] ['eventTime']);

            // Some locations have a trailing space after city name (replace " ," with ", ")
            $sortedList [$arrayCount] ['eventLocation'] = SnapImpact_ireplace (" ,", ", ",
            $sortedList [$arrayCount] ['eventLocation']);

            // Some locations have double spaces in them (replace "  " with " ")
            $sortedList [$arrayCount] ['eventLocation'] = SnapImpact_ireplace ("  ", " ",
            $sortedList [$arrayCount] ['eventLocation']);

            // If the phone number is NOT blank...
            if ($sortedList [$arrayCount] ['eventPhone'] != "")
                {
                // Reformat the phone number to "(xxx) xxx-xxxx" style
                $sortedList [$arrayCount] ['eventPhone'] = SnapImpact_format_phone (
                $sortedList [$arrayCount] ['eventPhone']);
                }

            $arrayCount++;
            }
        }

    if ($arrayCount > 0)
        {
        $buf .= 'Upcoming Volunteer Opportunities:<br /><br />';

        // Open list
        $buf .= '<ul>';

        // Sort the array by date-time
        sort ($sortedList);

        for ($i = 0; $i < $arrayCount; $i++)
            {
            // Open the list item
            $buf .= '<li>';

            // Event title as a clickable link
            $buf .= '<a href="' .
                     $sortedList [$i] ['eventUrl']   . '" target="_blank">' .
                     $sortedList [$i] ['eventTitle'] . '</a><br />';

            // Formatted date-time
            $buf .= $sortedList [$i] ['eventTimeFormatted'] . '<br />';

            // City-State-Zip
            $buf .= $sortedList [$i] ['eventLocation']      . '<br />';

            // Formatted phone
            $buf .= $sortedList [$i] ['eventPhone']         . '<br />';

            // Close the list item
            $buf .= '<br /></li>';
            }

        // Close list
        $buf .= '</ul>';
        }
    }

function SnapImpact_ireplace ($needle, $replacement, $haystack)
    {
    $i = 0;

    while ($pos = strpos (strtolower ($haystack), $needle, $i))
        {
        $haystack = substr ($haystack, 0, $pos) . $replacement . substr ($haystack, $pos + strlen ($needle));

        $i = $pos + strlen ($replacement);
        }

    return ($haystack);
    }


function SnapImpact_format_datetime ($eventTime)
    {
    $formattedDateTime = "";

    // Place 8601 time into a Unix-timestamp, for the date() function below
    $unixTime = mktime (substr ($eventTime, 11, 2), // hour
                        substr ($eventTime, 14, 2), // minute
                        0,                          // second
                        substr ($eventTime, 5, 2),  // month
                        substr ($eventTime, 8, 2),  // day
                        substr ($eventTime, 0, 4)); // year

    // Split-out the time (ex. 08:00)
    $timePart = substr ($eventTime, 11, 5);

    // If there is NOT an actual time
    if ($timePart == "00:00")
        {
        // Don't show time
        $formattedDateTime = date ("F j, Y", $unixTime);
        }
    else
        {
        // Show time as "Monthname, dd, yyyy  [hh:mm] am/pm"
        $formattedDateTime = date ("F j, Y  g:i a", $unixTime);
        }

    return ($formattedDateTime);
    }

function SnapImpact_format_phone ($eventPhone)
    {
    $formattedPhone = "";

    // Strip-out all dots, parens, spaces, hyphens... leaving only a 10-char numeric string
    $allNumeric = preg_replace ("/[\. \(\)\-]/", "", $eventPhone);

    // Format into "(xxx) xxx-xxxx" style
    for ($i = 0; $i < 10; $i++)
        {
        if ($i == 0)
            {
            $formattedPhone .= "(";
            }
        elseif ($i == 3)
            {
            $formattedPhone .= ") ";
            } 
        elseif ($i == 6)
            {
            $formattedPhone .= "-";
            }

        $formattedPhone .= substr ($allNumeric, $i, 1);
        }

    return ($formattedPhone);
    }

function SnapImpact_initWidget ()
    {
    // MUST be able to register the widget... else exit
    if (function_exists ('register_sidebar_widget'))
        {
        // Declare function -- called from Wordpress -- during page-loads
        function SnapImpact_widget ($args)
            {
            // Load existing options from wp database
            $SnapImpact_options = get_option (SI_OPTIONS);

            // Accept param array passed-in from WP (e.g. $before_widget, $before_title, etc.), theme CSS styles
            extract ($args);

            // Display sidebar title above the about-to-be-rendered SnapImpact events table
            echo $before_widget . $before_title .
                 '<a href="http://snapimpact.org" title="Visit SnapImpact" target="_blank">SnapImpact</a>' .
                 $after_title;

            // Open a plugin-version-tracking DIV tag
            printf ("<div id=\"%s\">", SI_PLUGIN_VERSION);

            $buf = '';

            // Dynamically build the table and display it
            if (SnapImpact_buildSidebar ($buf) == 0)
                {
                printf ("%s", $buf);
                }

            // Close the plugin-version-tracking DIV tag
            printf ("</div>");

            echo $after_widget;
            }

        // Register the widget function to be called from Wordpress on each page-load
        register_sidebar_widget ('SnapImpact', 'SnapImpact_widget');
        }
    }

function SnapImpact_sidebar ()
    {
    // This function is not called from within the Plugin.
    //
    // It is documented on the SnapImpact Plugin Installation page
    // for manual sidebar.php installs for non-widgetized themes.
    //
    // From non-widgetized themes, call this function directly from sidebar.php.
    //
    // For specific instructions, please see the Instructions in the readme.txt.

    // Load existing options from wp database
    $SnapImpact_options = get_option (SI_OPTIONS);

    // Display sidebar title above the about-to-be-rendered SnapImpact events table
    $buf  = '<a href="http://snapimpact.org" title="Visit SnapImpact" target="_blank"><h2>SnapImpact</h2></a>'; 

    // Open a plugin-version-tracking DIV tag
    $buf .= '<div id="' . SI_PLUGIN_VERSION . '">';

    // Dynamically build the table and display it
    SnapImpact_buildSidebar ($buf);

    // Close the plugin-version-tracking DIV tag
    $buf .= '</div>';

    printf ("%s", $buf);
    }

function SnapImpact_createOptions ()
    {
    // Create the initialOptions array of keys/values
    $SnapImpact_initialOptions = array ('numEventsToDisplay' => 5);

    // Store the initial options to the wp database
    add_option (SI_OPTIONS, $SnapImpact_initialOptions);
    }

function SnapImpact_deleteOptions ()
    {
    // Remove the SnapImpact options array from the wp database
    delete_option (SI_OPTIONS);
    }

function SnapImpact_updateSettingsPage ()
    {
    // Load existing options from wp database
    $SnapImpact_options = get_option (SI_OPTIONS);

    // Localize all displayed strings
    $SnapImpact_enterOptionsStr       = __('Please enter the SnapImpact Plugin options:',    'snapimpact');
    $SnapImpact_numEventsToDisplayStr = __('Number of events to display in the sidebar:',    'snapimpact');
    $SnapImpact_saveStr               = __('Save',                                           'snapimpact');
    $SnapImpact_optionsSavedStr       = __('SnapImpact Plugin options saved successfully.',  'snapimpact');
    $SnapImpact_show5Str              = __('Show 5 events in the sidebar',                   'snapimpact');
    $SnapImpact_show10Str             = __('Show 10 events in the sidebar',                  'snapimpact');
    $SnapImpact_show15Str             = __('Show 15 events in the sidebar',                  'snapimpact');

    // If data fields contain values...
    if (isset ($_POST ['numEventsToDisplay']))
        {
        // Copy the fields to the persistent wp options array
        if ($_POST ['numEventsToDisplay'] == "5")
            {
            $SnapImpact_options ['numEventsToDisplay'] = 5;
            }

        else if ($_POST ['numEventsToDisplay'] == "10")
            {
            $SnapImpact_options ['numEventsToDisplay'] = 10;
            }

        else   // must be 15
            {
            $SnapImpact_options ['numEventsToDisplay'] = 15;
            }

        // Store changed options back to wp database
        update_option (SI_OPTIONS, $SnapImpact_options);

        // Display update message to user
        echo '<div id="message" class="updated fade"><p>' . $SnapImpact_optionsSavedStr . '</p></div>';
        }

    // Initialize data fields for radio buttons
    $show5  = "";
    $show10 = "";
    $show15 = "";

    // Set variable for form to use to show sticky-value for radio button
    if ($SnapImpact_options ['numEventsToDisplay'] == 5)
        {
        $show5 = "checked";
        }

    else if ($SnapImpact_options ['numEventsToDisplay'] == 10)
        {
        $show10 = "checked";
        }

    else // must be 15
        {
        $show15 = "checked";
        }

    // Display the options form to the user

    echo
     '<div class="wrap">

      <h3>&nbsp;' . $SnapImpact_enterOptionsStr . '</h3>

      <form action="" method="post">

      <table border="0" cellpadding="10">

      <tr>
      <td width="300"><input type="radio" name="numEventsToDisplay" value="5"  ' . $show5  . ' />
      ' . $SnapImpact_show5Str . '<br />
                      <input type="radio" name="numEventsToDisplay" value="10" ' . $show10 . ' />
      ' . $SnapImpact_show10Str . '<br />
                      <input type="radio" name="numEventsToDisplay" value="15" ' . $show15 . ' />
      ' . $SnapImpact_show15Str . '</td>

      </tr>

      </table>

      <p>&nbsp;<input type="submit" value="' . $SnapImpact_saveStr . '" /></p>

      </form>

      </div>';
    }

function SnapImpact_addSubmenu ()
    {
    // Define the options for the submenu page
    add_submenu_page ('options-general.php',             // Parent page
                      'SnapImpact page',                 // Page title, shown in titlebar
                      'SnapImpact',                      // Menu title
                      10,                                // Access level all
                      __FILE__,                          // This file displays the options page
                      'SnapImpact_updateSettingsPage');  // Function that displays options page
    }

// Initialize for localized strings
load_plugin_textdomain ('snapimpact', 'wp-content/plugins/snapimpact');

// Runs only once at activation time
register_activation_hook (__FILE__, 'SnapImpact_createOptions');

// Runs only once at deactivation time
register_deactivation_hook (__FILE__, 'SnapImpact_deleteOptions');

// Load the widget, show it in the widget control in the admin section
add_action ('plugins_loaded', 'SnapImpact_initWidget');

// Add the SnapImpact submenu to the Settings page
add_action ('admin_menu', 'SnapImpact_addSubmenu');

?>
