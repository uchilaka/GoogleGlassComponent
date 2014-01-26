<?php

// embedded PHP_VENDOR_BASE 
// define("PHP_VENDOR_BASE", ABS_ROOT . 'assets/common/php/');
// define("SQLITE_DB", ABS_ROOT . 'slidesonglass' . DS . 'res' . DS . 'database.sqlite');
// define("GLASS_BASE_URL", APP_BASE . 'slides/glass');

// # Path to Google_Client class in Google PHP API library
require_once PHP_VENDOR_BASE . 'google' . DS . 'google-api-php-client' . DS . 'src' . DS . "Google_Client.php";
// # Path to Google_MirrorService class in Google PHP API library
require_once PHP_VENDOR_BASE . 'google' . DS . 'google-api-php-client' . DS . 'src' . DS . "contrib" . DS . "Google_MirrorService.php";
// # Path to Google_Oauth2Service class in Google PHP API library
require_once PHP_VENDOR_BASE . 'google' . DS . 'google-api-php-client' . DS . 'src' . DS . "contrib" . DS . "Google_Oauth2Service.php";
// #Path to SQLite database file - MUST be writable by application user!
define('SQLITE_DB', 'var' . DS . 'www' . DS . 'sqlitedb' . DS . 'database.sqlite');

App::uses('Component', 'Controller');
App::uses('Application', 'Model');

class GoogleGlassComponent extends Component {
    
    private $config = array();
    private $ready = false;
    public $service = null;
    public $oauth = null;
    public $google = null;
    
    public function initialize(\Controller $controller) {
        parent::initialize($controller);
    }
    
    public function connect(\Controller $controller, $config, $api_mode=false) {
        // MUST verify credentials FIRST after connecting
        
        // must include client_id, client_secret, simple_key, base_url and sqllite_path
        $this->config = array_merge($this->config, $config);
        $this->google = new Google_Client();
        $this->google->setClientId($config['client_id']);
        $this->google->setClientSecret($config['client_secret']);
        $this->google->setRedirectUri($config['redirect_url']);
        $this->google->setScopes(array(    
                'https://www.googleapis.com/auth/glass.timeline',
                'https://www.googleapis.com/auth/glass.location',
                'https://www.googleapis.com/auth/userinfo.profile'
            )
        );
        if(!empty($_REQUEST['code'])):
            $this->authenticate($controller, $_REQUEST['code']);
        else:    
            try {
                $credentials = $this->get_credentials($this->getUserId());
                $this->google->setAccessToken($credentials);
                $this->service = new Google_MirrorService($this->google);
                $this->oauth = new Google_Oauth2Service($this->google);
                $this->google->setUseObjects(true);
                $this->verify_credentials($controller, $credentials, $api_mode);
                $this->ready = true;
            } catch (Exception $ex) {
                if(!$api_mode):
                    $controller->redirect($this->getAuthUrl());
                endif;
            }
        endif;
    }
    
    private function authenticate(\Controller $controller, $code) {
        $this->service = new Google_MirrorService($this->google);
        $this->oauth = new Google_Oauth2Service($this->google);
        $token = $this->google->authenticate($code);
        $this->google->setAccessToken($token);
        $this->user = $this->verify_credentials($controller, $token);
        if(!empty($this->user)):
            $this->store_credentials($this->user['id'], $token);
            $this->setUserId($this->user['id']);
            $this->ready = true;
            if(!DEV):
                $this->bootstrap_new_user();
            endif;
            $controller->redirect($this->config['redirect_url']);
        endif;
        
    }
    
    // Create the credential storage if it does not exist
    function init_db() {
      // global $sqlite_database;
      $db = new SQLite3(SQLITE_DB);
      $test_query = "select count(*) from sqlite_master where name = 'credentials'";
      if ($db->querySingle($test_query) == 0) {
        $create_table = "create table credentials (userid text not null unique, " .
            "credentials text not null);";
        $db->exec($create_table);
      }
      return $db;
    }
    
    function store_credentials($user_id, $credentials) {
      $db = $this->init_db();
      $this->setUserId(SQLite3::escapeString(strip_tags($user_id)));
      $credentials = SQLite3::escapeString(strip_tags($credentials));
      $insert = "insert or replace into credentials values ('{$this->getUserId()}', '$credentials')";
      $db->exec($insert);
    }

    function get_credentials($user_id) {
      $db = $this->init_db();
      $user_id = SQLite3::escapeString(strip_tags($user_id));

      $query = $db->query("select * from credentials where userid = '$user_id'");

      $row = $query->fetchArray(SQLITE3_ASSOC);
      return $row['credentials'];
    }

    function list_credentials() {
      $db = $this->init_db();
      // Must use explicit select instead of * to get the rowid
      $query = $db->query('select userid, credentials from credentials');
      $result = array();
      while ($singleResult = $query->fetchArray(SQLITE3_ASSOC)){
        array_push($result,$singleResult);
      }
      return $result;
    }

    public function verify_credentials(\Controller $controller, $credentials, $api_mode=false) {
        // TODO: Use the oauth2.tokeninfo() method instead once it's
        //       exposed by the PHP client library
        $this->google->setAccessToken($credentials);
        try {
          return $this->oauth->userinfo->get();
        } catch (Google_ServiceException $e) {
            if ($e->getCode() == 401) {
                  // This user may have disabled the Glassware on MyGlass.
                  // Clean up the mess and attempt to re-auth.
                  $this->cleanUser();
                  if($api_mode):
                      return array('success'=>false, 'message'=>$e->getMessage());
                  else:
                       $controller->redirect($this->getAuthUrl());
                  endif;
                  // $controller->redirect($this->config['redirect_url']);
                  // echo $this->getAuthUrl();
                  // exit;
            } else {
                  // Let it go...
                  // throw $e;
                  if($api_mode):
                      return array('success'=>false, 'message'=>$e->getMessage());
                  else:
                      throw $e;
                  endif;
            }
        }
    }
    
    public function isReady() {
        return $this->ready;
    }
    
    function insert_timeline_item(\Google_TimelineItem $timeline_item, $content_type=null, $attachment=null) {
        // parse POST contentType and file contents for attachment
        /** @throws  Exception **/
        $opt_params = array();
        if ($content_type != null && $attachment != null) {
          $opt_params['data'] = $attachment;
          $opt_params['mimeType'] = $content_type;
        }
        return $this->service->timeline->insert($timeline_item, $opt_params);
    }
    
    function bootstrap_new_user() {
        $timeline_item = new Google_TimelineItem();
        $timeline_item->setText("Welcome to the Mirror API PHP Quick Start");
        $this->insert_timeline_item($timeline_item, null, null);
        $this->insert_contact("slides-on-glass", "SlidesOnGlass",
            APP_BASE . "img/welcome-image.png");
        $this->subscribe_to_notifications("timeline", $this->getUserId(), APP_BASE . "api/callback/glass");
    }
    
    function subscribe_to_notifications($collection, $user_token, $callback_url) {
        /** @throws Exception **/
        $subscription = new Google_Subscription();
        $subscription->setCollection($collection);
        $subscription->setUserToken($user_token);
        $subscription->setCallbackUrl($callback_url);
        $this->service->subscriptions->insert($subscription);
        return "Subscription inserted!";
    }
    
    function insert_contact($contact_id, $display_name, $icon_url)
    {
        /** @throws Exception **/
        $contact = new Google_Contact();
        $contact->setId($contact_id);
        $contact->setDisplayName($display_name);
        $contact->setImageUrls(array($icon_url));
        return $this->service->contacts->insert($contact);
    }

    /**
     * Delete a contact for the current user.
     *
     * @param Google_MirrorService $service Authorized Mirror service.
     * @param string $contact_id ID of the Contact to delete.
     */
    function delete_contact($contact_id) {
        /** @throws Exception **/
        $this->service->contacts->delete($contact_id);
    }

    /**
     * Download an attachment's content.
     *
     * @param string item_id ID of the timeline item the attachment belongs to.
     * @param Google_Attachment $attachment Attachment's metadata.
     * @return string The attachment's content if successful, null otherwise.
     */
    /*
    function download_attachment($item_id, $attachment) {
        $request = new Google_HttpRequest($attachment->getContentUrl(), 'GET', null, null);
        $httpRequest = Google_Client::$io->authenticatedRequest($request);
        if ($httpRequest->getResponseHttpCode() == 200) {
          return $httpRequest->getResponseBody();
        } else {
          // An error occurred.
          return null;
        }
    }
     */

    /**
     * Delete a timeline item for the current user.
     *
     * @param Google_MirrorService $service Authorized Mirror service.
     * @param string $item_id ID of the Timeline Item to delete.
     */
    function delete_timeline_item($service, $item_id) {
        /** @throws Exception **/
        $service->timeline->delete($item_id);
    }
    
    function getService() {
        return $this->service;
    }
    
    function getAuthUrl() {
        try {
            $authUrl = $this->google->createAuthUrl();
            return $authUrl;
            // return array('success'=>true, 'authUrl'=>$authUrl);
        } catch (Exception $ex) {
            return false;
            //return array('success'=>false, 'message'=>$ex->getMessage());
        }
    }
    
    public function setUser($id) {
        $_SESSION["gg_user"] = json_encode(array(
          'tokens' => json_decode($tokens, true)
        ));
    }
    
    private function setUserId($id) {
        $_SESSION['gg_user']['id'] = $id;
    }
    
    private function getUserId() {
        if(empty($_SESSION['gg_user'])):
            throw new Exception("No user ID found in session.");
        endif;
        
        return $_SESSION['gg_user']['id'];
    }
    
    function getUser() {
        if (isset($_SESSION["gg_user"])) {
          return $_SESSION["gg_user"];
        }
        return NULL;
    }
    
    public function cleanUser() {
        unset($_SESSION['gg_user']);
    }
    
    public function getTokens() {
        $user = $this->getUser();
        return json_encode($user['tokens']);
    }
    
    
}
