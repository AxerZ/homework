<?php
/*
作業上傳系統獨立精簡版 v1
CopyRight(C) 程式設計 Coding axer@tc.edu.tw 20120216-0228
版權宣告：本程式遵從GNUv3規範 http://www.gnu.org/licenses/gpl.html 

使用者作業管理程式

*/

include "includes/init.php";
include "includes/homework.class.php";

$obj = new Homework_class();
$obj->DB= $DB;
$obj->f = $f;
$obj->SetSession($_SESSION);
// $obj->InitAllCatArr();

//For index only
$view->caching = 0;
//$view->compile_check = true;
//$view->cache_lifetime = 10800; //3 hours
$view->assign('obj', $obj);
$view->assign('f', "HW");

switch($f){
  case "ChkCanUpload":  //AJAX
    $hID =  (int)$_POST['sn'];
    $upPasswd= isset( $_POST['p'])?$_POST['p']:"";
    print $IsOk= $obj->CheckCanUpload($hID, $upPasswd);
    break;
  case "DlHwIframe":
    $sn =  (int)$obj->LongDecode($_GET['c']);
    $obj->SendFile2Browser($sn);
    break;
  case "DoMyHw":
    $wt=5000;
    $sn =  (int)$obj->LongDecode($_POST['c']);
    $crypt = md5($_POST['passwd']);
    $IsOk= $obj->CheckHwPasswd($sn, $crypt);
    if($IsOk <0){
      $msg="密碼錯誤，無法操作 Err{$IsOk}";
      $msg .= $obj->JS_CntDn( "{$_SESSION['currURL']}" , $wt);
      $view->assign('msg', $msg);
      $view->display('Message.mtpl');
      break;
    }
    switch($_POST['o']){
      case 'd':
        $IsOk =$obj->ProcDelOneUploadHw( $sn );
        if( $IsOk>0 ) { $msg ="檔案刪除成功 <br />"; $wt=2000; }
        else $msg = "檔案刪除失敗 Err{$IsOk}";
        break;
      case 'dl':
        $view->assign('sn', $sn);
        $view->assign('c', $_POST['c']);
        $view->display('HwDownloadPage.mtpl');
        exit; //中止
        break;
      case 'm':
        $view->assign('sn', $sn);
	    $view->display('HwModPage.mtpl');
        exit; //中止 
        break;
      default:
        $msg="不明的操作錯誤Err-11";
        break;
    }   
    $msg .= $obj->JS_CntDn( "{$_SESSION['currURL']}" , $wt);
    $view->assign('msg', $msg);
    $view->display('Message.mtpl');
    break;
  case "ModMyHw":
    $sn = (int)$obj->LongDecode($_POST['snc']);
    $arr= array();
    $delFile=false;
    $row = $obj-> GetOneUploadHw($sn);
    if($_FILES['MyFile']['size']>0){ // 有上傳新檔
      if(!isset($row['hID']) || $row['hID']<= 0){ 
        $msg="錯誤的作業編號Err-12";
        $msg .= $obj->JS_CntDn( "{$_SESSION['currURL']}" , 5000);
        $view->assign('msg', $msg);
        $view->display('Message.mtpl');
        break;
      }
      $imgDir = HWPREFIX ."{$row['hID']}/";   //ex: 2008DecMedia/
      $IsOk= $obj->ProcUpFiles($_FILES['MyFile'], $imgDir, $rrr);
      if( $IsOk >0){ $arr= $rrr; $delFile=true;  }
      else $msg="檔案上傳失敗 Err{$IsOk}";
    }
    $arr['sn']= $sn;
    $arr['modPasswd']= $_POST['passwd'];
    $arr['remark']= mysql_real_escape_string(trim( $_POST['remark'] ));
    $arr['cname']= mysql_real_escape_string(trim( $_POST['cname'] ));
    $arr['uDT']=time();
    if( !$row)$msg = "修改失敗，參數錯誤 Err-13";
    else {
      $IsOk =$obj->ProcModMyHw ( $arr );
      if( $IsOk>0 ){ $msg ="檔案修改成功 <br />"; if($delFile)@unlink(UPLOAD_DIR. $row['fileName']); }
      else $msg = "檔案修改失敗 Err{$IsOk}";
    }
    $msg .= $obj->JS_CntDn( SITE_URL ."?f=HwDetail&c={$obj->LongEncode($row['hID'])}" , 5000);
    $view->assign('msg', $msg);
    $view->display('Message.mtpl');
    break;
  case "UploadHw":
    $hID = (int)$_POST['hID'];
    $upPasswd= isset( $_POST['upPasswd'])?$_POST['upPasswd']:"";
    $IsOk= $obj->CheckCanUpload($hID, $upPasswd);
    if( $IsOk <=0){
      if($IsOk ==-3) $msg ="檔案上傳失敗，非上傳時間 Err{$IsOk}";
      elseif($IsOk ==-4) $msg ="檔案上傳失敗，上傳密碼錯誤 Err{$IsOk}";
      else $msg= "檔案上傳失敗 Err{$IsOk}";
      $msg .= $obj->JS_CntDn( SITE_URL . "?f=HwDetail&amp;c={$obj->LongEncode($hID)}" , 5000);
      $view->assign('msg', $msg);
      $view->display('Message.mtpl');
      break;
    }
    $imgDir = HWPREFIX .$hID. "/";   //ex: xx00/
    $IsOk= $obj->ProcUpFiles($_FILES['MyFile'], $imgDir, $rrr);
    $msg="";
    if( $IsOk >0){
      $arr=$rrr;
      $arr['hID']=$hID;
      $arr['modPasswd']= $_POST['passwd'];
      $arr['remark']= mysql_real_escape_string(trim( $_POST['remark'] ));
      $arr['cname']= mysql_real_escape_string(trim( $_POST['cname'] ));
      $arr['cDT']=time();
      $IsOk =$obj->ProcAddHwUpload( $arr );
      if( $IsOk>0 ) $msg .="檔案上傳儲存成功 <br />";
      else $msg .= "檔案上傳儲存失敗 Err{$IsOk}";
    }else{
      if($IsOk ==-1) $msg ="檔案傳輸錯誤 Err{$IsOk}";
      elseif($IsOk ==-4) $msg ="檔案類型不被允許 Err{$IsOk}";
      else $msg= "目錄建立失敗，請檢查目錄權限是否可供寫入 Err{$IsOk}";
    }
    $msg .= $obj->JS_CntDn(  SITE_URL . "?f=HwDetail&c={$obj->LongEncode($hID)}" , 3000);
    $view->assign('msg', $msg);
    $view->display('Message.mtpl');
    break;
  case "View":
    $sn = (int)$obj->LongDecode($_GET['c']);
    $IsOk= $obj->SendFile2Browser($sn);   
    break;
  default:
    $msg = "連結錯誤操作，一秒後導至首頁{$f}".  $obj->JS_CntDn(  SITE_URL ,10000);
    $view->assign('msg', $msg);
    $view->display('Message.mtpl');
    break;
}

?>


