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
 * repository_datatelcmmlinks class
 *
 * @since 2.0
 * @package    repository
 * @subpackage datatelcmm
 * @copyright  2012 Datatel, Inc
 * @author     Datatel, inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot. '/repository/datatelcmm/cmmlib.php');

class repository_datatelcmmlinks extends repository {
    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
        parent::__construct($repositoryid, $context, $options);

        $this->cmmhelper = new datatelcmmhelper();
        
        $this->web_services_url = trim(get_config('datatelcmm', 'webservicesurl'));  
        $this->shared_secret = trim(get_config('datatelcmm', 'sharedsecret'));
        $this->domain_name = trim(get_config('datatelcmm', 'domainname'));
        $this->date_format = trim(get_config('datatelcmm', 'dateformat'));
        $this->max_results = trim(get_config('datatelcmm', 'maxresults'));
        $this->service_timeout = trim(get_config('datatelcmm', 'servicetimeout'));
        if ($this->max_results == '') {
            $this->max_results = 50;
        }    
        if ($this->domain_name != '') {
            $this->domain_name = $this->domain_name . '\\';
        }    
        if ($this->service_timeout == '') {
            $this->service_timeout = 20;
        }

        $this->use_ssl = trim(get_config('datatelcmm', 'usessl'));
        $this->ca_authority = trim(get_config('datatelcmm', 'sslcaauthority'));
                
        $this->cert_path = trim(get_config('datatelcmm', 'certpath'));
        
        
        $this->site_collection_url = trim(get_config('datatelcmm', 'sitecollectionurl'));    
        $this->search_keyword = optional_param('search_keyword', '', PARAM_RAW);    
        $this->search_modified_before = optional_param('search_modified_before', '', PARAM_RAW);
        $this->search_modified_after = optional_param('search_modified_after', '', PARAM_RAW);
        $this->search_personal_only = optional_param('search_personal_only', '', PARAM_RAW);            
                                    
        // Get an initialization vector to be used by all service calls
        $this->iv = $this->cmmhelper->generate_iv();                    
                    
    }     
     
    /**
     * Check if we should print the "login" page (if there is no keyword)
     */
    public function check_login() {
                
        return !empty($this->keyword);        
    }

    /**
     * Perform a search
     * 
     * @param $search_text - the text to search for
     * @return array - search results 
     */
    public function search($search_text) {        
        $this->search_keyword = $search_text;        
        $ret  = array();
        $ret['nologin'] = true;        
        $ret['list'] = $this->execute_search($this->search_keyword,  $this->search_modified_after, $this->search_modified_before, $this->search_personal_only);
        return $ret;
    }

    /**
     * Return an initial list of items.
     * Not used by this plugin
     */
    public function get_listing($path='', $page = '') {    
        return array();        
    }
    
    /**
     * Override the "search" pop up from search results
     */
    /*        
    public function print_search() {
    }
    */
    
    
    /**
     * Execute a search for documents
     * 
     * @param $keyword
     * @param $modifiedbefore - return all documents modified before this date
     * @param $modifiedafter - return all documents modified after this date
     * @param $personalonly - if true, return only personal documents; otherwise, return both personal
     *                             and shared documents
     * @return json - list of results
     */    
    private function execute_search($keyword, $modifiedbefore = '', $modifiedafter = '', $personalonly = 'false') {
                
        global $OUTPUT;
        global $USER, $COURSE;
        debugging("In CMM Repository plug-in, will attempt to search for course materials for user " . 
                $USER->username . " using the following selection criteria: keyword->" . 
                $keyword . ", modified before date->". 
                $modifiedbefore . ", modified after date->". 
                $modifiedafter . ", personal docs only->" .
                $personalonly, DEBUG_DEVELOPER);
				
		$this->cmmhelper->validate_search_input($keyword, $modifiedbefore, $modifiedafter, $this->date_format);
                
        $list = array();
                  
        $this->get_materials_service_url = $this->build_service_url($keyword, $modifiedbefore, $modifiedafter, $personalonly);
        debugging("CMM Request by user $USER->username for GetDocuments url: $this->get_materials_service_url", DEBUG_DEVELOPER);
        
        // Build the header for the service request                
        $service_header = $this->cmmhelper->build_service_header($this->iv, $this->shared_secret, $this->web_services_url, $this->domain_name);                                         
        debugging("Service header: ". $service_header, DEBUG_DEVELOPER);
        
        $options = array();        
        $ch = curl_init();        
        curl_setopt($ch,CURLOPT_HTTPHEADER,array('Authorization: '. $service_header));
        $defaults = $this->cmmhelper->get_curl_defaults($this->get_materials_service_url, $this->use_ssl, $this->ca_authority, $this->cert_path, $this->service_timeout);
        curl_setopt_array($ch, ($options + $defaults));
        if (!$result = curl_exec($ch)) {
            debugging("Error calling web service at $this->get_materials_service_url. " . curl_error($ch) , DEBUG_NORMAL);
            throw error ("Search failed. Please contact your system administrator.");                            
        }
        curl_close($ch);                          
        $materials = json_decode($result, true);
        
        if (is_array($materials)) {
            foreach ($materials as $item) {                
                $shorttitle = $this->cmmhelper->format_title((string)$item['Title'], (string)$item['CollectionName'], (string)$item['Modified'], 200, $this->date_format);
                
                $list[] = array(
                        'title' => (string)$item['Title'],  // the name of the document/filename
                        'shorttitle' => $shorttitle,  // this is what actually gets displayed to users
                        'path' => (string)$item['Title'],
                        'thumbnail' => $OUTPUT->pix_url(file_extension_icon($item['Title']))->out(false),
                        'thumbnail_width' => 200,
                        'thumbnail_height' => 30,
                        'size' => '',
                        'date' => $this->cmmhelper->convert_date_to_external($item['Modified'], $this->date_format),
                        'source' => (string)$item['Url']
                        );
            }            
        }   
        return $list;
    }

    /**
     * No support for global search
     */
    public function global_search() {
        return false;
    }
    
    /**
     * Generate search form - it has to redefine "print_login" but no login is required for datatelcmm
     * 
     * @param $ajax - whether to use AJAX to return result
     * @return json for the data to be printed (search form)     
     */
    public function print_login($ajax = true) {      
        
        // Search Keyword                            
        $ret = array();
        $keyword = new stdClass();
        $keyword->type = 'text';
        $keyword->id   = 'search_keyword';
        $keyword->name = 's';
        $keyword->label = get_string('search_heading', 'repository_datatelcmm').': ';            

        // Search for documents modified after...
        $search_modified_after = new stdClass();
        $search_modified_after->type = 'text';
        $search_modified_after->id   = 'search_modified_after';
        $search_modified_after->name = 'search_modified_after';
        $search_modified_after->label = $this->cmmhelper->date_format_display(get_string('search_modified_after', 'repository_datatelcmm'), $this->date_format);
        
        // Search for documents modified before...
        $search_modified_before = new stdClass();
        $search_modified_before->type = 'date_selector';
        $search_modified_before->id   = 'search_modified_before';
        $search_modified_before->name = 'search_modified_before';
        $search_modified_before->label = $this->cmmhelper->date_format_display(get_string('search_modified_before', 'repository_datatelcmm'), $this->date_format);
                   
        // Search personal collections only?    
        $personalonly = new stdClass();
        $personalonly->type = 'select';
        $personalonly->id = 'search_personal_only';
        $personalonly->name = 'search_personal_only';
        $personalonly->label = get_string('search_personalonly', 'repository_datatelcmm').' ';
        $personalonly->options = array(
                (object)array(
                        'value' => 'false',
                        'label' => 'No'
                ),
                (object)array(
                        'value' => 'true',
                        'label' => 'Yes'
                )    
        );
                
        // Build the "login" form
        $ret['login'] = array($keyword, $search_modified_after, $search_modified_before, $personalonly);
        $ret['login_btn_label'] = get_string('search');
        $ret['login_search_form'] = true;
        $ret['login_btn_action'] = 'search';
           
        return $ret;
    }
                   
    
    /**
    * Builds the service url used to search for a list of documents in the CMM
    *
    * @return string url - fully formatted url for service request
    */
   private function build_service_url($keyword, $modifiedbefore = '', $modifiedafter = '', $personalonly = 'false') {
        /* The search service url looks something like this:
             ServicePath/CMMDocument.svc/GetCMMMaterials/?  
                     cmmSiteCollectionUrl=http%3A%2F%2Fcmmrootsiteurl.com
                     &shared=true
                     &personal=true
                     &modifiedBefore=20111115 
                     &ModifiedAfter=20111130
                     &keyword=art
        */        
        $service_path = '/CMMDocument.svc/GetCMMMaterials/?';
        $param_collectionurl = 'cmmSiteCollectionUrl=' . urlencode($this->site_collection_url);
        $param_maxresults = '&maxResults=' . $this->max_results;
        $param_personal = '&personal=' . 'true';
        if ($personalonly == 'true') {
            $param_shared = '&shared=' . 'false';    
        }
        else {
            $param_shared = '&shared=' . 'true';    
        }
        $param_keyword = '&keyword=' . urlencode($keyword);        
                        
        $param_modifiedbefore = '';
        if ($modifiedbefore != '') {
            $modifiedbefore = strtotime($modifiedbefore);             
            $param_modifiedbefore = '&modifiedBefore=' . date('Ymd', $modifiedbefore);                            
        }
        
        $param_modifiedafter = '';        
        if ($modifiedafter != '') {
            $modifiedafter = strtotime($modifiedafter);
            $param_modifiedafter = '&modifiedAfter=' . date('Ymd', $modifiedafter);            
        }                                          
                        
        // This is the 'links only' version of the plug-in; only return items of type 'Links'
        $param_doctype = '&Type=Links';
        
        $service_url = $this->web_services_url . 
                $service_path .
                $param_collectionurl .
                $param_shared .
                $param_personal . 
                $param_keyword . 
                $param_modifiedbefore . 
                $param_modifiedafter .
               $param_maxresults .
               $param_doctype;
        return $service_url;
   }          
            
    
    /**
     * Downloads an individual file from the CMM
     * 
     * @param $filepath - the path to the file to be downloaded
     * @param $saveas - name of new file
     * 
     * @return array - new file 
     */
    public function get_file($filepath, $saveas = '') {
     
        global $COURSE, $CFG;
        
        $saveas = $this->prepare_file($saveas);        
        $current_course_name = $COURSE->shortname;
        $current_course_id = $COURSE->id;
                
        $filepath = $this->web_services_url . 
                '/CMMDocument.svc/?cmmSiteCollectionUrl=' .    urlencode($this->site_collection_url) .
                '&docId=' . $filepath .
                '&classSite=' . urlencode($current_course_name) .                    
                '&classSiteId=' . urlencode($current_course_id);
                    
        debugging("CMM Request for GetFile url: " . $filepath, DEBUG_DEVELOPER);
        
        $c = new curl(array('cache' => true, 'module_cache' => 'repository'));                                
        
        $service_header = $this->cmmhelper->build_service_header($this->iv, $this->shared_secret, $this->web_services_url, $this->domain_name);        
        $defaults = $this->cmmhelper->get_curl_defaults($this->get_materials_service_url, $this->use_ssl, $this->ca_authority, $this->cert_path, $this->service_timeout);
        $options = array();        
        $ch = curl_init();        
        curl_setopt($ch,CURLOPT_HTTPHEADER,array('Authorization: '. $service_header));
        curl_setopt_array($ch, ($options + $defaults));
        if (!$result = curl_exec($ch)) {
            throw error("Import failed. Please contact your system administrator.");
            trigger_error(curl_error($ch));
        }
        curl_close($ch);        
                        
        file_put_contents($saveas, $result);
        return array('path' => $saveas, 'url' => $filepath);
                      
    }
    
    
    /* ----------------------------------------------------- Admin ----------------------------------------------------- */
    
    /**
    * List of admin configuration parameters
    * 
    * @return array - list of admin configuration parameters
    */
    public static function get_type_option_names() {
        return array('webservicesurl', 'pluginname', 'sharedsecret', 'sitecollectionurl', 'domainname', 'dateformat', 'maxresults');
    }
    
    /**
    *  Admin settings page
    *  
    *  @param $mform - admin settings form
    */
    public function type_config_form($mform) {
        parent::type_config_form($mform);
            
        $mform->addElement('html', "<p>All connection settings for this plug-in are defined in the <a href='repository.php?action=edit&repos=datatelcmm'>CMM Documents</a> repository configuration page.");    
    
    }
    
    /**
    * Types of files supported by the CMM plug-in
    * 
    * @return array - file types
    * 
    */
    public function supported_filetypes() {
        return '*';
    }
    
    /**
     * Type of plugin - internal, external or both
     * 
     * @return string - type of plug-in
     */
    public function supported_returntypes() {
        return FILE_EXTERNAL;
    }
    
}
