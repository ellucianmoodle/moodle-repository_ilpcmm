<?php

// Created by Datatel, Inc.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'datatelcmm_youtube', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   repository_datatelcmm
 * @copyright 2012 onwards Datatel, Inc  {@link http://www.datatel.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'CMM - Documents';
$string['pluginname_help'] = 'Retrieve files from the Course Materials Manager.';
$string['dateformat'] = 'Date format';
$string['sitecollectionurl'] = 'CMM Site Collection Url';
$string['webservicesurl'] = 'Web Services Url';
$string['servicetimeout'] = 'Web Service Timeout (in seconds)';
$string['sharedsecret'] = 'Shared Secret'; 
$string['usessl'] = 'Use SSL?';
$string['certpath'] = 'Optional: path to CA Certificate Bundle';
$string['sslcaauthority'] = 'Is the CMM site SSL certificate issued by a Certificate Authority (CA)?';
$string['sslcawarning'] = 'Without a certificate issued by a CA, the communications between Moodle and the CMM site are less secure since Moodle will trust any certificate it receives.';
$string['certexplanation'] = 'If the SSL certificate used by the CMM site was issue by a CA, you may use this field to specify the location of the CA certificate bundle in your system. In most cases you will not need to specify the location of a certificate bundle if a default bundle is configured system-wide. However, under certain configurations, a default bundle is not available and the relative path to a certificate bundle must be specified. If your Moodle site is hosted by Moodlerooms, leave this field blank.';
$string['maxresults'] = 'Maximum number of results to return from a search';
$string['domainname'] = 'Portal Users Domain';
$string['domainexplanation'] = 'Enter a domain ONLY if you are using Active Directory for authentication in SharePoint. Enter the value users would enter when logging in. For example, if your usernames have a format of "COLLEGE\username", you would enter "COLLEGE" in this field.';
$string['search_heading'] = 'Search Keywords';
$string['search_modified_after'] = 'Modified on or after [XX/XX/XX] (optional): ';
$string['search_modified_before'] = 'Modified on or before [XX/XX/XX] (optional):';
$string['search_personalonly'] = 'Limit results to my personal collection';
$string['search_note_docsonly'] =  'Note: only document-type items are supported in this context; hyperlinks cannot be imported.';
$string['datatelcmm:view'] = 'Course Materials Manager'; // this shows up when users are selecting a plugin
$string['configplugin'] = 'Datatel CMM repository configuration';
$string['search_error_keyword_required'] = 'Please enter at least one search keyword';
$string['search_error_date_format'] = 'Invalid date format. Please use the format [XX/XX/XX]';
