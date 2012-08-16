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
 * Private cmmlinks module utility functions
 *
 * @package    repository
 * @subpackage cmmlinks
 * @copyright  2012 Ellucian, Inc.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/repository/datatelcmm/cmmlib.php");

function repository_datatelcmmlinks_get_activity_for_cmmlink($cmmlink_record, $request_uri, $url_referrer = '' ) {

    GLOBAL $DB, $CFG;

    $cmmlinks_context = new stdClass();

    if ($url_referrer != '') {
        $parsed_url = parse_url($url_referrer);
        $referrer = new stdClass();
        parse_str($parsed_url['query'], $referrer);
        $referrer_host = clean_param($parsed_url['host'], PARAM_HOST);
        $referrer_path = clean_param($parsed_url['path'], PARAM_PATH);
        $referrer_id = clean_param($referrer['id'], PARAM_INT);

        debugging("In cmmlinks, trying to find the activity and course associated with this request. The referrer is " .
                "'$url_referrer', the host is '$referrer_host', the path is '$referrer_path' and the id we're looking ".
                "for is '$referrer_id'.", DEBUG_DEVELOPER);

        if (($referrer_host != '') && (!strrpos($CFG->wwwroot, $referrer_host) > 0)) {
            debugging("In cmmlinks, the referrer_host of the current link '$referrer_host' with url referrer '$url_referrer'" .
                    " does not match the current site root '". $CFG->wwwroot . "'.", DEBUG_NORMAL);
            print_error('erroraccessdenied', 'repository_datatelcmmlinks');
        }
        if (strrpos($referrer_path, "course/view.php") > 0) {
            /*
             * We're coming directly from the course home page; the id in the referrer url is a course id.
             * Get all links for the current course, check if any has the external url we're looking for:
             */
            $urls_in_course = $DB->get_records_list('url', 'course', array ($referrer_id));

            foreach ($urls_in_course as $url) {
                if (strrpos($url->externalurl, $request_uri)) {
                    $cm = get_coursemodule_from_instance('url', $url->id);
                    $module_id = $cm->id;
                    $context_course = $url->course;
                    $module_visible = $cm->visible;
                    debugging("Found a url record that matches our search criteria. The url record id is $module_id and the " .
                            "match external url is $url->externalurl.", DEBUG_DEVELOPER);
                    break;
                }
            }
        } else {
            // We're coming from an activity; the id in the referrer url is an activity id.
            $module_id = $referrer_id;
            $cm = get_coursemodule_from_id('', $module_id, 0, false, MUST_EXIST);
            $context_course = $cm->course;
            $module_visible = $cm->visible;
        }
    } else {
        /*
         * Referrer is not set; fall back to the course of the CMM record as stored in the db
         * TODO: this may not be correct after a backup/restore; the Moodle 2.3 link to document
         * functionality does not handle this either.
         */
        debugging("In cmmlinks, no referrer is available; using course id " . $cmmlink_record->course .
                " for cmmlink record with id " . $cmmlink_record->id . ".", DEBUG_DEVELOPER);
        $context_course = $cmmlink_record->course;
        // Now check if any of the urls in this course links to this item.
        $urls_in_course = $DB->get_records_list('url', 'course', array ($context_course));
        foreach ($urls_in_course as $url) {
            if (strrpos($url->externalurl, $request_uri)) {
                $cm = get_coursemodule_from_instance('url', $url->id);
                $module_id = $cm->id;
                $module_visible = $cm->visible;
                debugging("Found a url record that matches our search criteria. The url record id is $module_id and the " .
                    "match external url is $url->externalurl.", DEBUG_DEVELOPER);
                 break;
            }
        }
    }

    if (isset($context_course)) {
        $cmmlinks_context->course = $context_course;
    }
    if (isset($module_id)) {
        $cmmlinks_context->module_id = $module_id;
    }
    if (isset($module_visible)) {
        $cmmlinks_context->module_visible = $module_visible;
    }

    return $cmmlinks_context;
}

/**
 * Present the document to the user as a download
 *
 * @param object $document
 * @param object $course
 * @param object $course_name
 *
 * @return - does not return
 */
function repository_datatelcmmlinks_display_link_contents($document, $course, $course_name) {
    global $OUTPUT;

    $fullfile = repository_datatelcmmlinks_get_file($document, $course, $course_name);
    if ($fullfile === false) {
        print_error('errordownloadfailed', 'repository_datatelcmmlinks');
    } else {
        @header("Content-Type: application/force-download;  charset=utf-8");
        @header("Content-Disposition: attachment; filename=\"" . $document->title . "." . $document->document_extension . "\"");

        echo $fullfile;

        die;
    }
}

 /**
  * Downloads an individual file from the CMM
  *
  * @param $document - ID of the CMM document to be downloaded
  * @param $course - ID of the current course
  * @param @course_name - name of the current course
  *
  * @return array - downloaded file
  */
function repository_datatelcmmlinks_get_file($document, $course, $course_name) {

    $cmmhelper = new datatelcmmhelper();
    $service = $cmmhelper->get_service_properties();
    // Get an initialization vector to be used by all service calls.
    $service->iv = $cmmhelper->generate_iv();

    $filepath = $service->web_services_url .
            '/CMMDocument.svc/?cmmSiteCollectionUrl=' .    urlencode($service->site_collection_url) .
            '&docId=' . $document->documentid .
            '&classSite=' . urlencode($course_name) .
            '&classSiteId=' . urlencode($course);

    debugging("CMM Request for GetFile for service url: " . $filepath, DEBUG_NORMAL);

    $c = new curl(array('cache' => true, 'module_cache' => 'repository'));

    $service_header = $cmmhelper->build_service_header($service->iv, $service->shared_secret, $service->web_services_url,
            $service->domain_name);

    $defaults = $cmmhelper->get_curl_defaults($filepath, $service->use_ssl, $service->ca_authority, $service->cert_path,
            $service->service_timeout);
    $options = array();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: '. $service_header));
    curl_setopt_array($ch, ($options + $defaults));

    if (!$result = curl_exec($ch)) {
        print_error('errordownloadfailed', 'repository_datatelcmmlinks');
        trigger_error(curl_error($ch));
        return false;
    }

    /*
     * Curl_exec is always returning true in Windows; curl_error returns 0 even in error conditions (401, 404, etc)
     * check the beginning of the return string
     */
    if (is_numeric(substr($result, 0, 3))) {
        debugging("Curl error - return code: " . $result , DEBUG_NORMAL);
        return false;
    }
    curl_close($ch);

    // Check for "valid" errors coming from the service.
    if (strpos(substr($result, 0, 40), 'CMM Web Service') > 0) {
        debugging("Error retrieving document: " . $result, DEBUG_NORMAL);
        return false;
    }
    return $result;
}
