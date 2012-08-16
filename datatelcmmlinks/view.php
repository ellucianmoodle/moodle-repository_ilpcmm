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
 * cmmlinks user interface to view files
 *
 * @package    repository
 * @subpackage datatelcmmlinks
 * @copyright  2012 Ellucian, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once("$CFG->dirroot/repository/datatelcmmlinks/locallib.php");

global $USER;

$cmmlinksid = optional_param('id', 0, PARAM_INT);  // Cmmlinks ID.

// Remove title from url if present.
if (strrpos($cmmlinksid, '#')) {
    $cmmlinksid = substr($cmmlinksid, 0, strrpos($cmmlinksid, '#'));
}

// Get the cmm document from the db.
$cmm_link_record = $DB->get_record('repository_datatelcmmlinks', array('id'=>$cmmlinksid), '*', MUST_EXIST);

if ($cmm_link_record->documentid == "") {
    print_error('errorinvalidlink', 'repository_datatelcmmlinks', $cmmlinksid);
}

/*
 * Normally users would not see any links they don't have access to since the permissions
 * of the course/activity the link belongs to would control what links the user sees.
 * The following checks are in place in case users try to access the url to a cmmlinks record
 * directly (which, again, should never happen under normal circumstances when users are
 * navigating through courses and activities using the Moodle UI).
 *
 * The following checks are performed to see if the user has acccess to this document:
 * 1. Get the context for the link. Use the referrer url (it will either be a course or an activity) as a starting point.
 *    Run checks to make sure the referrer is really a valid referrer for this user before continuing.
 *    If the referrer is not set, use the course from the cmm link record instead.
 *    Either way, we get a context_course (the course the link belongs to), and a module_id if we have a referrer.
 * 2. The user must be logged in, and have access to the context_course.
 * 3. Check permissions on the parent course and/or parent activity for this user
 *     3.a. If parent activity, check visibility
 *     3.b. If the parent activity is not visible, check if they are a teacher (teachers can view items that are not visible)
 */

 // 1. Use the referrer and cmm_link_record to get the context for this link.

$cmmlink_context = new stdClass();
if ((isset($_SERVER['HTTP_REFERER']) && (strpos($_SERVER['HTTP_REFERER'], "id=")))) {
    $cmmlink_context = repository_datatelcmmlinks_get_activity_for_cmmlink( $cmm_link_record, $_SERVER['REQUEST_URI'],
            $_SERVER['HTTP_REFERER']);
} else {
    $cmmlink_context = repository_datatelcmmlinks_get_activity_for_cmmlink($cmm_link_record, $_SERVER['REQUEST_URI']);
}

if (isset($USER->username)) {
    $currentusername = $USER->username;

} else {
    $currentusername = "guest";
}
debugging("Processing a request to get a document by user '" . $currentusername . "' looking for document with cmmlink_id of '" .
        $cmm_link_record->id . "' in course '" . $cmmlink_context->course . "'.", DEBUG_DEVELOPER);

// 2. User must have access to the course that has this link.
$current_page_url = new moodle_url('/repository/datatelcmmlinks/view.php');
$current_page_url->param('id', $cmmlinksid);
$PAGE->set_url($current_page_url);
require_course_login($cmmlink_context->course, false);

// 3. Now check permissions on the parent activity.
if (isset($cmmlink_context->module_id)) {
    // If the module that contains the activity is visible, the user has access to it.
    if (!$cmmlink_context->module_visible) {
        /*
         * if the item is not visible, check if this is a user who can edit the course;
         * they would not have gotten to this place if the item is not visible unless (1) they are a
         * teacher or (2) they got the url from someone else.
         */
        $context = get_context_instance(CONTEXT_COURSE, $cmmlink_context->course);

        if (!has_capability('moodle/course:update', $context)) {
            debugging("Attempt to open a link to a cmm document that the user does not have access to. User: " . $USER->id .
                    "; Cmmdocument id: " . $cmmlinksid . ".", DEBUG_NORMAL);
            print_error('errorpermissionsvisible', 'repository_datatelcmmlinks');
        }
    }
}

repository_datatelcmmlinks_display_link_contents($cmm_link_record, $COURSE->id, $COURSE->shortname);
