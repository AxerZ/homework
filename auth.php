<?php
/*
作業上傳系統獨立精簡版 v1
CopyRight(C) 程式設計 Coding axer@tc.edu.tw 20120216-0228
Blog http://note.tc.edu.tw 精讚BLOG
版權宣告：本程式遵從GNUv3規範 http://www.gnu.org/licenses/gpl.html 

認證管理程式

*/

include "includes/init.php";
include "includes/auth.class.php";

$obj = new Auth_class();
$obj->DB= $DB;
$obj->f = $f;
$obj->SetSession($_SESSION);
// $obj->InitAllCatArr();

//For index only
$view->caching = 0;
$view->compile_check = true;
$view->cache_lifetime = 10800; //3 hours

if( !isset($_SESSION['priv'])) $_SESSION['priv'] = $obj->GetNoLoginPrivArr();
$view->assign('obj', $obj);
$view->assign('f', "HW");

switch($f){
  case "LoginChk":
    $wt=5000;
    if( empty( $_POST["validate"])){ $msg =  "認證碼未填！5 秒後導至首頁"; }
    else{
      $validcode = $_POST["validate"];
      if($validcode !== "好" ) $msg =  "您輸入的認證碼{$validcode}錯誤，無法送出！";
      else{
        $IsOk =$obj->Member_Auth( $_POST["email"], md5($_POST["passwd"]));
        if( $IsOk>0 ){ $msg ="登入成功"; $wt=1000; }
        else{
          if($IsOk==-3)$msg = "登入失敗： 帳號密碼錯誤";
          else $msg = "登入失敗 Err". $IsOk;
        }
      }
    }
    if( !empty($_POST['r']))$url=$_POST['r'];
    else $url= SITE_URL . "manage.php?f=Homework";
    $msg .= $obj->JS_CntDn( $url, $wt);
    $view->assign('msg', $msg);
    $view->display('Message.mtpl');
    break;

  case "Logout":
    $_SESSION=array();
    session_destroy();
    $obj->SetSession($_SESSION);    //登出後再傳 _SESSION 傳遞給物件
    $msg ="您已經順利登出，歡迎下次再來。";
    $msg .= $obj->JS_CntDn(  SITE_URL, 1000);
    $view->assign('msg', $msg);
    $view->display('Message.mtpl');
    break;

  case "TcAuth":
    include "includes/commonclass.php";
    $tobj = new TC_OID_BASE();
    $tobj->setFinishFile("auth.php?f=TcAuthCallback");
    if(empty($_GET['openid_identifier'])) { $tobj->displayError("請輸入公務帳號"); }
    $openid= "http://" . $_GET['openid_identifier'] .".openid.tc.edu.tw";
    $tobj->beginAuth($openid);
    break;

  case "TcAuthCallback":
    $url= SITE_URL;
    $wt=5000;
    require_once "includes/commonclass.php";
    $tobj = new TC_OID_BASE();
    $tobj->setFinishFile("auth.php?f=TcAuthCallback");
    if( $tobj->getResponseStatus($msg) >0) {
      $arr= $tobj->getResponseArray();
      if( !SCH_NAME ) $msg= "無法使用中市教育局公務帳號，請設定init.php 中之 SCH_NAME 欄位";
      elseif( isset($arr['schoolname'])){
        $fullschname =$arr['schooldistrict']. $arr['schoolname']; 
        if( $fullschname === SCH_NAME){
          $msg= "登入成功";
          $url= SITE_URL . "manage.php?f=Homework";
          $wt=2000;
          $IsOk =$obj->Tc_Auth( $arr);
        }
        else $msg= "你不屬於".SCH_NAME ."，無法取得權限";
      }else{
        $msg= "認證失敗，無法取得伺服器之學校的名稱";
      }
  /*
Array
(
    [identity] => http://axer.openid.tc.edu.tw/
    [fullname] => 張本和
    [email] => cpth2512@cpjh.tcc.edu.tw
    [schooldistrict] => 西屯區
    [schoolname] => 福科國中
    [schooltitle] => 專任教師
    [schooltype] => 市立國民中學
)

  */
    }else $msg= "認證失敗：". $msg;
    $msg .= $obj->JS_CntDn( $url, $wt);
    $view->assign('msg', $msg);
    $view->display('Message.mtpl');
    break;

  default:
    $view->display('Index.mtpl');
    break;
}

?>


