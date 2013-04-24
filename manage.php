<?php
/*
作業上傳系統獨立精簡版 v1
CopyRight(C) 程式設計 Coding axer@tc.edu.tw 20120216-0228
版權宣告：本程式遵從GNUv3規範 http://www.gnu.org/licenses/gpl.html 

作業管理程式

*/

include "includes/init.php";
include "includes/manage.class.php";

$obj = new Manage_class();
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
if( $f=="AccManage")$view->assign('f', "AccManage");
else $view->assign('f', "HW");

if( empty($_SESSION['email'])){ $view->display('LoginPage.mtpl'); exit; }
switch($f){
  case "AccManage":
    $view->display('AccManage.mtpl');  
    break;
  case "AddAccount":
    $sup = $_POST['superpasswd'];
    if( $sup !== SUPER_PASSWD ){ $IsOk =-12; $msg="超級密碼錯誤！ Err-12"; }
    else{
      $arr= array(
        'email' => $_POST['email'],
        'shadow' => md5($_POST['email']),
        'passwd' => md5($_POST['passwd']),
        'usID' => mysql_real_escape_string($_POST["usID"]),
        'cname' => mysql_real_escape_string($_POST["cname"]),
        "regDT" => date("Y-n-d H:i:s")
      );
      $IsOk =$obj->ProcAdmAddMember( $arr );
      $msg="伺服器狀態異常，新增失敗！ Err{$IsOk}";
    }
    if( $IsOk >0 ) $msg="會員新增成功";
    elseif( $IsOk =-4 ) $msg="帳號已經存在";
    $msg .= $obj->JS_CntDn( SITE_URL. "manage.php?f=AccManage" , 4000);
    $view->assign('msg', $msg);
    $view->display('Message.mtpl');
    break;
  case "ChangeHomework":
    if( !isset($_POST['stp'])){ $view->display('AddHwPage.mtpl'); break; }
    $stp= (int)$_POST['stp'];
    $c= empty($_POST['c'])?"":$_POST['c'];
    $rank=(int)$_POST["rank"];
    if($rank>99)$rank=99;elseif($rank<0)$rank=0;
    $arr=array(
      "hwTitle"=> mysql_real_escape_string(trim($_POST["hwTitle"])),
      "hwO"=> mysql_real_escape_string(trim($_POST["hwO"])),
      "email"=> $_SESSION['email'],
      "passwd"=> mysql_real_escape_string($_POST["passwd"]),
      "classID"=> mysql_real_escape_string($_POST["classID"]),
      "fromDT" => empty( $_POST["from"])?"": $_POST["from"],
      "dueDT" => empty($_POST["to"])?"": $_POST["to"],
      "remark" => addslashes($_POST["remark"]),
      "closed" => (int)$_POST["closed"],
      "display" => (int)$_POST["display"],
      "upPasswd" => (int)$_POST["upPasswd"],
      "rank" =>$rank,
      "cDT" => date("Y-n-d H:i:s")
    );
    if($stp==3){ $IsOk =$obj->ProcAddHw( $arr );
      if( $IsOk <=0 )$msg="伺服器狀態異常，寫入終止！ Err{$IsOk}";
      else $msg="新增作業成功";
    }elseif($stp==5){
      $arr['hID']= (int)$_POST["hID"];
      $IsOk =$obj->ProcModHw( $arr );
      if( $IsOk <=0 )$msg="伺服器狀態異常，寫入終止！ Err{$IsOk}";
      else $msg="修改作業成功";
    }
    $msg .= $obj->JS_CntDn( SITE_URL . "manage.php?f=Homework&amp;c={$c}" , 4000);
    $view->assign('msg', $msg);
    $view->display('Message.mtpl');
    break;
  case "DelHw":
    $wt=5000;
    if( isset($_GET['c'])){
      $sn =  (int)$obj->LongDecode($_GET['c']);
     include "includes/homework.class.php";
      $obj2 = new Homework_class();
      $obj2->DB= $DB;
      $obj2->SetSession($_SESSION);
      $IsOk =$obj2->ProcDelOneUploadHw( $sn );
      unset( $obj2);
      if( $IsOk>0 ) { $msg ="檔案刪除成功 <br />"; $wt=2000; }
      else $msg = "檔案刪除失敗 Err{$IsOk}";
    }
    else $msg = "錯誤的操作 Err-11";
    $msg .= $obj->JS_CntDn( $_SESSION['currURL'] , $wt);
    $view->assign('msg', $msg);
    $view->display('Message.mtpl');
    break;
  case "DelAccount":
    $wt=1000;
    $sup = $_POST['superpasswd'];
    if( $sup !== SUPER_PASSWD ){ $IsOk =-12; $msg="超級密碼錯誤！ Err-12"; }
    else{
      $IsOk =$obj->ProcDelMember( $_POST['email'] );
      if( $IsOk <=0 ){$msg="伺服器狀態異常，寫入終止！ Err{$IsOk}"; $wt=3000;}
      else $msg="會員刪除成功";
    }
    $msg .= $obj->JS_CntDn( SITE_URL. "manage.php?f=AccManage", $wt, false);
    $view->assign('msg', $msg);
    $view->display('Message.mtpl');
    break;
  case "Homework":
    if( empty($_POST['c']) &&  empty($_GET['c'])){
      $tbl=array(
        "pg"=>1,
        "kw"=> empty($_POST["kw"])?"": trim(addslashes($_POST["kw"])),
        "s" => empty( $_POST["s"])?"": $_POST["s"],
        "from" => empty( $_POST["from"])?"": $_POST["from"],
        "to" => empty($_POST["to"])?"": $_POST["to"],
      );
    }else{
      $c =empty($_POST['c'])? $_GET['c']:$_POST['c'];
      $tbl=  $obj->Decrypt_c2Arr( $c );
      if(! isset($tbl['pg']))$tbl['pg']=1;
    }
    if(! empty($tbl['s'])){ //_kw=1,_date=1,_state=1,state=0
      $s= $tbl['s'];
      $srr = explode(",",$s);
      foreach($srr as $it){
        preg_match('/(\w+)=(\w+)/', $it, $match);
        $k= $match[1];
        $v= $match[2];
        $tbl[$k]=$v;
      }
    }
    if(isset($tbl['stp'])) $stp= (int)$tbl['stp'];
    else $stp= empty($_POST['stp'])?(empty($_GET['stp'])?0:(int)$_GET['stp']):(int)$_POST['stp'];
    $tbl['odr']= empty($_GET['odr'])?(empty($tbl['odr'])?0:$tbl['odr']):(int)$_GET['odr'];  //default no orderby
    $obj->SetCurrParam($tbl);
    unset($tbl['stp']);  //clear
    $c=  $obj->Encrypt_Arr2c($tbl);
    $view->assign('c', $c);
    switch($stp){
      case 2: //Modi
        $sn= $tbl['sn'];
        $view->assign('hID', $sn);
        $view->display('ModHwPage.mtpl');
        break;
      case 3: //Save Modi
        break;
      case 4: //close
        $sn= $tbl['sn'];
        $IsOk = $obj->ProcChangeHwAttr($sn, "closed");
        if( $IsOk <=0 )$msg="伺服器狀態異常，寫入終止！ Err{$IsOk}";
        else $msg="修改作業屬性[有效]成功";
        $msg .= $obj->JS_CntDn( SITE_URL ."manage.php?f=Homework&amp;c={$c}" , 2000);
        $view->assign('msg', $msg);
        $view->display('Message.mtpl');
        break;
      case 5: //display
        $sn= $tbl['sn'];
        $IsOk = $obj->ProcChangeHwAttr($sn, "display");
        if( $IsOk <=0 )$msg="伺服器狀態異常，寫入終止！ Err{$IsOk}";
        else $msg="修改作業屬性[展示]成功";
        $msg .= $obj->JS_CntDn(  SITE_URL ."manage.php?f=Homework&amp;c={$c}" , 2000);
        $view->assign('msg', $msg);
        $view->display('Message.mtpl');
        break;
      case 6: //delete
        $sn= $tbl['sn'];
        $IsOk = $obj->ProcDelHw($sn);
        if( $IsOk <=0 )$msg="伺服器狀態異常，寫入終止！ Err{$IsOk}";
        else $msg="刪除作業成功，刪除目錄：". HWPREFIX. $sn;
        $msg .= $obj->JS_CntDn(  SITE_URL ."manage.php?f=Homework&amp;c={$c}", 4000);
        $view->assign('msg', $msg);
        $view->display('Message.mtpl');
        break;
      case 7: //hw Manage
        $_SESSION['currURL']= $_SERVER['REQUEST_URI'];
        $sn= $tbl['sn'];
        $view->assign('hID', $sn);
        $view->display('ManageUpHw.mtpl');
        break;
      case 9: //Dl hw
        $sn= $tbl['sn'];
        $wt=4000;
        $targetDir = HWPREFIX. $sn;
        $IsOk =$obj-> CreateDirectory(UPLOAD_TEMP_DIR);
        if( $IsOk == -1 )$msg="存在但不是目錄，新增目錄失敗！ Err{$IsOk}". $obj->JS_CntDn( $_SESSION['currURL'] , $wt);
        elseif( $IsOk == -2 ) $msg="新增目錄失敗！ Err{$IsOk}". $obj->JS_CntDn( $_SESSION['currURL'] , $wt);
        elseif ( !system('command -v zip')) {
           $msg= "系統無zip命令，請安裝zip套件 Err-3。<br/>安裝範例: yum install zip";
        }
        else{
//          $tmpPath =  UPLOAD_TEMP_DIR. "$targetDir.zip";
          $currPath= UPLOAD_DIR. $targetDir;
          $cmd = "cd ". UPLOAD_TEMP_DIR. "; rm -f {$targetDir}.zip; zip -rjq {$targetDir}.zip $currPath/*";
          system( $cmd );
          sleep(1);
          $msg="<h3>檔名{$targetDir}.zip，請 [<a href='". SITE_URL. UPDIR . TEMP_PATH . $targetDir .".zip'>點選此處下載</a>] </h3>檔案資訊：<div style='text-align:left;'>";
          $arr= $obj-> GetUploadHwList($sn);
          foreach($arr as $it){
             $msg .= "{$it['fileName']} ,{$it['size']}bytes ,擁有人{$it['cname']} ,原檔名{$it['oFileName']} ,備註{$it['remark']}<br/>";
          } 
          $msg .="</div>";
        }
        $view->assign('msg', $msg);
        $view->display('Message.mtpl');
        break;
      default:
        $_SESSION['currURL']= $_SERVER['REQUEST_URI'];
        $view->display('HwManage.mtpl');
        break;
    }
    break;
  case "MngUpHw": //AJAX
    $opr= isset($_POST['opr'])?$_POST['opr']:"";
    $snc = isset($_POST['sn'])?$_POST['sn']:"";
    if( empty($opr) || empty($snc)){ print "參數錯誤Err-10"; break; }
    $row = $obj->Decrypt_c2Arr( $snc );
    if($row['sn'] <= 0){ print "參數錯誤Err-11"; break; }
    $sn =$row['sn'];
    switch($opr){
      case "ex":  //exhibition
        $IsOk = $obj->ProcChangeUpHwAttr($sn, "display");
        if( $IsOk <=0 )$msg="伺服器狀態異常，寫入終止！ Err{$IsOk}";
        else $msg="1";
        print $msg;
        break;
      case "p": //passed
        $IsOk = $obj->ProcChangeUpHwAttr($sn, "passed");
        if( $IsOk <=0 )$msg="伺服器狀態異常，寫入終止！ Err{$IsOk}";
        else{
          $obj->UpdatePassedCnt( $sn, $row['p'] );
          $msg="2";
        }
        print $msg;
        break;
      default:
        print "參數錯誤Err-12";
        break;
    }
    break;
  case "SaveUpHwScore":
    $wt=5000;
    $z= $_POST['z'];
    $h= $_POST['h'];
    $IsOk = $obj->UpdateUpHwScore($z,$h);
    if( $IsOk == -1 )$msg="沒有資料，動作無效！ Err{$IsOk}";
    if( $IsOk <=0 )$msg="伺服器狀態異常，寫入終止！ Err{$IsOk}";
    else {$msg="儲存作業成績成功"; $wt=2000;}
    $msg .= $obj->JS_CntDn( $_SESSION['currURL'] , $wt);
    $view->assign('msg', $msg);
    $view->display('Message.mtpl');
    break;
  case "SaveOneHwScore":
    $IsOk = $obj->UpdateOneHwScore($_POST);
    if( $IsOk == -1 )$msg="沒有資料，動作無效！ Err{$IsOk}";
    if( $IsOk <=0 )$msg="伺服器狀態異常，寫入終止！ Err{$IsOk}";
    else {$msg="儲存作業成績成功"; $wt=2000;}
    print $msg;
    break;

  default:
    $msg = "". $obj->JS_CntDn( SITE_URL, 0);
    $view->assign('msg', $msg);
    $view->display('Message.mtpl');
    break;
}

?>


