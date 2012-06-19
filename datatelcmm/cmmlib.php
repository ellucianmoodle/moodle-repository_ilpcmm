<?php

// This file is part of Datatel CMM Plugin
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
 * datatelcmmhelper class
 *
 * @since 2.0
 * @package    repository
 * @subpackage datatelcmm
 * @copyright  2012 Datatel, Inc
 * @author     Datatel, inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot.'/user/lib.php');

class datatelcmmhelper {
    function __construct() {    
    }    
        
    /**
     * Converts a date to its external value
     *
     * param: @originaldate - date in json format
     *
     * @return "standard" date - converted date
     */
    function convert_date_to_external($originaldate, $date_format) {
        $date = $originaldate;
        // sample date: Modified=/Date(1315422686000)/
        $date = (str_replace(')/', '', str_replace('/Date(', '', $originaldate))) / 1000;
    
        $date = userdate($date, $date_format); //'%Y/%m/%d');
        return $date;
    }
    
    /**
     * Converts a date to its internal (unix * 1000) value
     *
     * param: @originaldate - date in "standard" format
     *
     * @return json date - converted date
     */
    function convert_date_to_internal($originaldate) {
        $date = strtotime($originaldate) * 1000; // JSON dates - epoch * 1000
        return $date;
    }
    
    /**
     * Formats a string based on a date format for display
     *
     * @param $string_to_format - string to format
     * @param $date_format - the internal date format - e.g. "%Y/%m/%d"
     *
     * @returns string - a date format that a user can understand, e.g. "YYYY/MM/DD"
     */
    function date_format_display($string_to_format, $date_format) {
        $formatted_date = str_replace('XX/XX/XX', $date_format, $string_to_format);
        $formatted_date = str_replace("%Y", 'YYYY', $formatted_date);
        $formatted_date = str_replace("%m", 'MM', $formatted_date);
        $formatted_date = str_replace("%d", 'DD', $formatted_date);
        return $formatted_date;
    
    }
    /**
     * Generate an initialization vector for encryption
     *
     * @return 8-character IV
     */
    function generate_iv() {
        /* mycrypt_create_iv does not work on Windows php 3.x */
        //return bin2hex(mcrypt_create_iv(4,  MCRYPT_DEV_URANDOM)); // 4 binary will turn into 8 hex
    
        return base_convert(mt_rand(46656, 431901), 10, 36) . base_convert(mt_rand(46656, 431901), 10, 36);
    }
    
    /**
     * Build the header information for service calls (authorization string)
     *
     * @return string - base-64 header
     */
    function build_service_header($iv, $shared_secret, $web_services_url, $domain_name) {
        $this->timestamp = $this->get_timestamp_token($web_services_url);
        $encoded_iv = base64_encode($iv);
        $token = $this->create_service_token($shared_secret, $domain_name, $web_services_url, $iv);
        $header = $encoded_iv . $token;
        return $header;
    }
    
    /**
     * Get a timestampt token that specifies the lenght in which the request is valid
     *
     * @return string - timestamp (time limit of the request)
     */
    function get_timestamp_token($web_services_url) {
        $timestamp_service = $web_services_url . '/CMMDocument.svc/CMMMaterials/GetTimeStamp';
        $c = new curl();
        $timestamp  = $c->get($timestamp_service);
    
        if (($timestamp != '') && (is_numeric($timestamp))) {
            debugging("CMM Timestamp is $timestamp", DEBUG_DEVELOPER);
            return $timestamp;
        }
        else {
            $error =  "Could not retrieve a valid timestamp token to access this course materials repository.  Please contact your system administrator.";
             debugging("$error Using service $timestamp_service.", DEBUG_DEVELOPER);
            throw new repository_exception('repositoryerror', 'repository', '', $error);

        }
    }
    
    /**
     *  Creates a token to be used for authentication
     *
     *  @param $shared_secret - the shared secret to use for creating the authentication token
     *  @return string $result - encrypted service token
     */
    function create_service_token($shared_secret, $domain_name, $web_services_url, $iv) {
        global $USER;
    
        //Data has the following format: 'user:user1;validty:634581728687191651'
        $timestamp = $this->get_timestamp_token($web_services_url);
        $data = 'user:' . $domain_name . $USER->username . ';validity:' . $timestamp;
    
        // md5 and base-64 encode the password before encrypting the data
        $md5_password = md5($shared_secret);
        $password = base64_encode(pack('H*', $md5_password));
    
        // add zero padding for encryption
        $size = 8;
        $extra = $size - (strlen($data) % $size);
    
        if ($extra > 0) {
            for($i = 0; $i < $extra; $i++) {
                $data .= "\0";
            }
        }
    
        $result = base64_encode(mcrypt_cbc(MCRYPT_3DES, $password, $data, MCRYPT_ENCRYPT, $iv));
    
        return $result;
    }
    
    /**
     * Formats document data into a short title to be displayed to users
     *
     * params: @documenttitle - title of document
     *         @collectionname - name of the collection the document belongs to
     *         @datemodified - the date the document was last modified - in JSON format
     *         @maxlen - the maximum length of the resulting string
     *
     *
     * @return string - formatted_title
     */
    function format_title($documenttitle, $collectionname, $datemodified, $maxlen = 200, $date_format) {
        $collectionname = str_replace('Personal Collection - ', '', $collectionname);
        $collectionname = str_replace('Shared Collection - ', '', $collectionname);
    
        if ($datemodified) {
            $datemodified = 'modified ' . $this->convert_date_to_external($datemodified, $date_format);
        }
    
        $formatted_title = $documenttitle . ' (' . $datemodified . ' - ' . $collectionname . ' Collection)';
    
        // The icon takes up about 130 characters
        if (strlen($formatted_title) > ($maxlen - 130)) {
            $formatted_title = substr($formatted_title, 0, ($maxlen - 130)) . '...';
        }
        return $formatted_title;
    }

    /**
     * Get the defaults to be using when connecting to CMM services
     * 
     * @param : $service_url - the url to connect to
     *             $use_ssl - whether to use SSL (https)
     *             $ca_authority - 1 if using a CA authority certificate
     *             $cert_path - path to the certificate to be used for the connection
     * 
     * @return array defaults
     */
    function get_curl_defaults($service_url, $use_ssl = 1, $ca_authority = 1, $cert_path, $service_timeout) {    
        $options = array(
                CURLOPT_URL => $service_url,
                CURLOPT_HEADER => 0,
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_TIMEOUT => $service_timeout                
        );                        

        if ($use_ssl != 0) {                        
            $options[CURLOPT_SSL_VERIFYPEER] = $ca_authority;
            $options[CURLOPT_SSL_VERIFYHOST] = 2;
                                     
            if ($cert_path != "") {                                
                $options[CURLOPT_CAINFO] = $cert_path;                
            }                        
        }
        return $options;        
    }
	
	/**
     * Checks search input for errors
     * 
     * @param : $keyword - the keyword used to search for course materials
     *          $start_date - the start date for the search
     *          $end_date - the end date for the search
     *          $date_format - the date format being used - e.g. %Y/%m/%d
     * 
     * @return array defaults
     */
	function validate_search_input($keyword, $start_date, $end_date, $date_format) {
	   // check that $keyword != null
	    if ($keyword == "") {
	        throw error(get_string('search_error_keyword_required', 'repository_datatelcmm'));
	    }
		$dayindex = 0;
		$monthindex = 0;
		$yearindex = 0;
	    switch ($date_format) {
	        // 02/20/2012
	        case "%m/%d/%Y":
                $dayindex = 3;
			    $monthindex = 0;
			    $yearindex = 6;			   
				break;
            // 20/02/2012
		    case "%d/%m/%Y": 
		        $dayindex = 0;
			    $monthindex = 3;
			    $yearindex = 6;			
				break;
		    // 2012/02/02
		    case "%Y/%m/%d":
		        $dayindex = 8;
			    $monthindex = 5;
			    $yearindex = 0;		      
				break;
	    }
	   
	    if ($start_date != "") {		    
	        $start_date_day = substr($start_date, $dayindex, 2);
	        $start_date_month = substr($start_date, $monthindex, 2);
	        $start_date_year = substr($start_date, $yearindex, 4);	   
	        $valid_start_date = checkdate($start_date_month, $start_date_day, $start_date_year);			
	    }
	    else {
		    $valid_start_date = 1;
	    }
	   
	    if ($end_date != "") {
	        $end_date_day = substr($end_date, $dayindex, 2);
	        $end_date_month = substr($end_date, $monthindex, 2);
	        $end_date_year = substr($end_date, $yearindex, 4);	   
	        $valid_end_date = checkdate($end_date_month, $end_date_day, $end_date_year);			
	    }
		else {
		    $valid_end_date = 1;
        }	   
	    
		if (!($valid_start_date) || !($valid_end_date)) {
	        $error_string = get_string('search_error_date_format','repository_datatelcmm');
		    $error_string_formatted = $this->date_format_display($error_string, $date_format);
		    throw error ($error_string_formatted);
	    }	   	   	  
	}
}