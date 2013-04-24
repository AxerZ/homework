<?php

/* This class revised from PHP-openID library common.php mainly and for Taichung city openID use.
   Taichung network center 2012.12.1 by axer@tc.edu.tw
*/

class TC_OID_BASE{
  public $pape_policy_uris;
  private $response;
  private $finishFile;
  public function __construct(){
    $path_extra = dirname(__FILE__);
    $path = ini_get('include_path');
    $path = $path_extra . PATH_SEPARATOR . $path;
    ini_set('include_path', $path);
    /**
     * Require the OpenID consumer code.
     */
    require "Auth/OpenID/Consumer.php";

    /**
     * Require the "file store" module, which we'll need to store
     * OpenID information.
     */
    require "Auth/OpenID/FileStore.php";

    /**
     * Require the Simple Registration extension API.
     */
    require "Auth/OpenID/SReg.php";

    /**
     * Require the PAPE extension module.
     */
    require_once "Auth/OpenID/PAPE.php";
    require "Auth/OpenID/AX.php";

    $pape_policy_uris  = array( PAPE_AUTH_MULTI_FACTOR_PHYSICAL, PAPE_AUTH_MULTI_FACTOR, PAPE_AUTH_PHISHING_RESISTANT );
    $this->finishFile= "finish_auth.php";
  }

  public function beginAuth($openid){
    $consumer = $this->getConsumer();
    $auth_request = $consumer->begin($openid);

    // No auth request means we can't begin OpenID.
    if (!$auth_request) { $this->displayError("認證錯誤，非合理的帳號"); }

    $attribute= $this->createTCaxs();
    // Create AX fetch request
    $ax = new Auth_OpenID_AX_FetchRequest;
    // Add attributes to AX fetch request
    foreach($attribute as $attr){ $ax->add($attr); }
    // Add AX fetch request to authentication request
    $auth_request->addExtension($ax);
    $sreg_request = Auth_OpenID_SRegRequest::build(
                                     // Required
                                     array('nickname'),
                                     // Optional
                                     array('fullname', 'email'));
    if ($sreg_request) {
      $auth_request->addExtension($sreg_request);
    }
    if ($auth_request->shouldSendRedirect()) {
      $redirect_url = $auth_request->redirectURL($this->getTrustRoot(), $this->getReturnTo());
      // If the redirect URL can't be built, display an error message.
      if (Auth_OpenID::isFailure($redirect_url)) {
        $this->displayError("無法導向 SERVER: " . $redirect_url->message);
      } else {
         // Send redirect.
         header("Location: ".$redirect_url);
      }
    } else {
      // Generate form markup and render it.
      $form_id = 'openid_message';
      $form_html = $auth_request->htmlMarkup($this->getTrustRoot(), $this->getReturnTo(),
                                               false, array('id' => $form_id));

      // Display an error if the form markup couldn't be generated;
      // otherwise, render the HTML.
      if (Auth_OpenID::isFailure($form_html)) {
          $this->displayError("Could not redirect to server: " . $form_html->message);
      } else {
        print $form_html;
      }
    }
  }

  private function createTCaxs(){
    // Create attribute request object
    // See http://code.google.com/apis/accounts/docs/OpenID.html#Parameters for parameters
    // Usage: make($type_uri, $count=1, $required=false, $alias=null)
    $attribute[] = Auth_OpenID_AX_AttrInfo::make('http://openid.tc.edu.tw/schema/1.0/schooldistrict',1,1, 'schooldistrict');
    $attribute[] = Auth_OpenID_AX_AttrInfo::make('http://openid.tc.edu.tw/schema/1.0/schoolname',1,1, 'schoolname');
    $attribute[] = Auth_OpenID_AX_AttrInfo::make('http://openid.tc.edu.tw/schema/1.0/schooltitle',1,1, 'schooltitle');
    $attribute[] = Auth_OpenID_AX_AttrInfo::make('http://openid.tc.edu.tw/schema/1.0/schooltype',1,1, 'schooltype');
    return $attribute;
  }

  public function displayError($message) {
//    $error = $message;
    include 'index.php';
    exit(0);
  }

  public function &getConsumer() {
    /**
     * Create a consumer object using the store object created
     * earlier.
  public function &getConsumer() {
    /**
     * Create a consumer object using the store object created
     * earlier.
     */
    $store = $this->getStore();
    $r = new Auth_OpenID_Consumer($store);
   return $r;

  }

  public function getResponseStatus(&$msg){
    $consumer = $this->getConsumer();

    // Complete the authentication process using the server's response.
    $return_to = $this->getReturnTo();  //%s://%s:%s%s/finish_auth.php
    $response = $consumer->complete($return_to);
    $this->response = $response;
    // Check the response status.
    if ($response->status == Auth_OpenID_CANCEL) {
    // This means the authentication was cancelled.
      $msg = 'Verification cancelled.'; 
      return -1;
    } else if ($response->status == Auth_OpenID_FAILURE) {
    // Authentication failed; display the error message.
      $msg = "OpenID authentication failed: " . $response->message;
      return -2;
    } else if ($response->status == Auth_OpenID_SUCCESS) {
      $msg = "You have successfully verified";
      return 1;
    }
  }

  public function getResponseArray(){
    if(empty($this->response)) return array();
    $arr= array();
    $response= $this->response;
    // This means the authentication succeeded; extract the
    // identity URL and Simple Registration data (if it was
    // returned).
    $openid = $response->getDisplayIdentifier();
    $arr['identity'] = htmlentities($openid);
    if ($response->endpoint->canonicalID) {
        $escaped_canonicalID = htmlentities($response->endpoint->canonicalID);
        $arr['canonicalID'] = $escaped_canonicalID;
    }

    $sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse($response);
    $sreg = $sreg_resp->contents();
    $arr= array_merge($arr, $sreg);
    $ax = new Auth_OpenID_AX_FetchResponse();
    $axobj = $ax->fromSuccessResponse($response);
    foreach( $axobj->data as $k=>$v){
      $krpos= strrpos( $k, '/');
      if( $krpos === false) $arr[$k] = $v[0];
      else{ 
        $newk = substr( $k, $krpos+1);
        $arr[$newk] = $v[0];
      }
    }
    return $arr;
  }

  public function getReturnTo() {
    return sprintf("%s://%s:%s%s/%s",
                   $this->getScheme(), $_SERVER['SERVER_NAME'],
                   $_SERVER['SERVER_PORT'],
                   dirname($_SERVER['PHP_SELF']),
                   $this->finishFile
    );
  }

  public function &getStore() {
    /**
     * This is where the example will store its OpenID information.
     * You should change this path if you want the example store to be
     * created elsewhere.  After you're done playing with the example
     * script, you'll have to remove this directory manually.
     */
    $store_path = null;
    if (function_exists('sys_get_temp_dir')) {
        $store_path = sys_get_temp_dir();
    }
    else {
        if (strpos(PHP_OS, 'WIN') === 0) {
            $store_path = $_ENV['TMP'];
            if (!isset($store_path)) {
                $dir = 'C:\Windows\Temp';
            }
        }
        else {
            $store_path = @$_ENV['TMPDIR'];
            if (!isset($store_path)) {
                $store_path = '/tmp';
            }
        }
    }
    $store_path .= DIRECTORY_SEPARATOR . '_php_consumer_test';
    if (!file_exists($store_path) &&
        !mkdir($store_path)) {
        print "Could not create the FileStore directory '$store_path'. ".
            " Please check the effective permissions.";
        exit(0);
    }
    $r = new Auth_OpenID_FileStore($store_path);
    return $r;
  }

  public function getScheme() {
    $scheme = 'http';
    if (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on') {
        $scheme .= 's';
    }
    return $scheme;
  }

  public function getTrustRoot() {
    return sprintf("%s://%s:%s%s/", $this->getScheme(), $_SERVER['SERVER_NAME'],
                   $_SERVER['SERVER_PORT'], dirname($_SERVER['PHP_SELF']));
  }

  public function setFinishFile($filepath){
    $this->finishFile= $filepath;
  }
}

?>
