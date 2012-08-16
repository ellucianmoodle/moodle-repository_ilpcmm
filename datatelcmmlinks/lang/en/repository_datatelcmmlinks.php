<?php
// This file is part of Moodle - http://moodle.org/
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
 * Strings for component 'repository_datatelcmmlinks'
 *
 * @package   repository_datatelcmmlinks
 * @copyright 2012 Ellucian, Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'CMM - Link to Course Material';
$string['pluginname_help'] = 'Retrieve files from the Course Materials Manager.';
$string['dateformat'] = 'Date format';
$string['sitecollectionurl'] = 'CMM Site Collection Url';
$string['webservicesurl'] = 'Web Services Url';
$string['sharedsecret'] = 'Shared Secret';
$string['maxresults'] = 'Maximum number of results to return from a search';
$string['domainname'] = 'Users Domain (required ONLY if you are using Active Directory for authentication in SharePoint)';
$string['search_heading'] = 'Search Keywords';
$string['search_modified_after'] = 'Modified on or after after [XX/XX/XX] (optional): ';
$string['search_modified_before'] = 'Modified on or before [XX/XX/XX] (optional):';
$string['search_personalonly'] = 'Limit results to my personal collection';
$string['search_note_docsonly'] =  'Note: only document-type items are supported in this context; hyperlinks cannot be imported.';
$string['datatelcmm:view'] = 'Course Materials Manager'; // This shows up when users are selecting a plugin.
$string['configplugin'] = 'CMM Repository Configuration';
$string['errorpermissions'] = 'You do not have permissions to access this resource.';
$string['errorpermissionsvisible'] = 'You cannot access this resource because it is not visible.';
$string['erroraccessdenied'] = 'Unauthorized access. Please return to the course or activity that contains this resource and ' .
        'try again.';
$string['errorinvalidlink'] = 'Invalid link. No document id found for link with id {$a}.';
$string['errordownloadfailed'] = 'There was an error retrieving this file from the repository. File download failed.' .
    'Please contact your system administrator.';
$string['errordirectaccess'] = 'You may not access this resource directly. Please return to the course or activity that contains ' .
    'this link and try again.';