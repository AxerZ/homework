<?php
// 程式的初始檔，包括建立smarty物件、資料庫等。
// 安全性檢查
if (__FILE__ == ''){ die('Fatal error code: 0'); }

// ----==== 程式初始檔開始 ====-----
define('DB_NAME', "hw"); //資料庫名

define('DB_ADDR', "localhost");  //資料庫ip

define('DB_USR', "hw");  // 資料庫使用者

define('DB_PWD', "HKuA6ntKGRdm6veu");  //資料庫密碼

define('SUPER_PASSWD', "12345");  //最高權限user 密碼

define('SendAllEmail', 1);  //Set 1 to enable email

define('SITE_CNAME', "作業上傳精簡版");  //網站標頭

define('SITE_DN', "heart.tc.edu.tw"); //網域，請勿加上'/'

define('SITE_URL', "http://". SITE_DN ."/homework/");  // 修改網站位址，請保留字串最後的 '/'

// 若為台中地區學校，可指定學校名稱，例：北區太平國小，以供公務帳號登入，留空代表不使用公務帳號功能
// 若已設定學校，則公務帳號認證後，若屬於設定的學校，將可以直接使用管理功能。
define('SCH_NAME', "西屯區福科國中");

define('UPDIR', "upload/");

define('UPLOAD_DIR', "/var/www/html/homework/".UPDIR ); //修改上傳位址

define('TEMP_PATH', "temp/");

define('UPLOAD_TEMP_DIR', UPLOAD_DIR . TEMP_PATH);  

define('UPURL', SITE_URL. UPDIR);  

define('RANK_DIFFERENCE', 1); // 排序差值大小

define('PAGE_ENCRYPT_KEY',"QQHW589632"); //全頁加密碼，勿更動

define('MAX_IMG_SIZE', 16777210); //16MB 最大上傳檔案大小

define('MAX_IMG_WIDTH', 2400);  // 上傳圖片最大寬

define('MAX_IMG_HEIGHT', 2400);

define('ROWS_PER_PAGE', 30);    //頁面一頁筆數

define('ROWS_EXHIBITION', 40);    //展示頁面筆數

define('SEMESTER_NUM',2); // 學期制 2 or 3

define('SEMESTER1', 8); // 學期1起始月份

define('SEMESTER2', 2); // 學期2起始月份

define('HWPREFIX', 'hw'); // 作業目錄字首xx，產生的目錄會是 xx1, xx2, xx3...

//define('SEMESTER3', 2); // 學期3起始月份

//圖片伺服器   正式用-------------------------------------------
//$FtpServer = "122.146.203.240"; //ftp圖片至遠端主機之IP
//$FtpAcc = "web5_admin"; //Ftp主機登入帳號
//$FtpPwd = "adminuser";  //Ftp主機登入密碼
//$FtpPath = "/web/webimage/";//ftp圖片至遠端主機之資料路徑
//$PIC_SERVER = "http://image.hotkt.com/webimage/";//圖片讀取主機及資料夾

//$SWF_GATEWAY = $SITE_URL . "libs/amfphp1.9/gateway.php";

// ========== 程式設定結束 ===============
// 以下內容請勿任意修改，否則會造成程式損害

define('ROOT_PATH', str_replace('includes/init.php', '', str_replace('\\', '/', __FILE__)));
// ex. ROOT_PATH=/home/plurkgo/public_html/ss/

@ini_set('memory_limit',          '64M');
@ini_set('session.cache_expire',  60);
@ini_set('session.use_trans_sid', 0);
@ini_set('session.use_cookies',   1);
@ini_set('session.auto_start',    0);
@ini_set('display_errors',        1);
@ini_set('include_path', '.:' . ROOT_PATH); //引入路徑

include "libs/Smarty.class.php";      //讀入SMARTY函式庫(2.6.26 June 18th, 2009)
include "adodb5/adodb.inc.php";    //讀入ADODB的類別函式庫 V5.09a (2009.6.26)
include "base.class.php";        //讀入基礎的function

//Database
$DB = NewADOConnection('mysql');
$DB->Connect(DB_ADDR, DB_USR, DB_PWD, DB_NAME);
$DB->Execute("set names utf8");

//Smarty
$view = new Smarty();
define('SMARTY_DIRECTOR', 'libs');
$view->template_dir = SMARTY_DIRECTOR . "/templates/";
$view->compile_dir = SMARTY_DIRECTOR . "/templates_c/";
$view->config_dir = SMARTY_DIRECTOR . "/configs/";
$view->cache_dir = SMARTY_DIRECTOR . "/cache/";
$view->left_delimiter = '{{';
$view->right_delimiter = '}}';

//啟動 session
session_start();
$f= (empty($_POST["f"]))? (empty($_GET["f"])? "":$_GET["f"]):$_POST["f"];
$f=trim($f);    //hacking proof

//錯誤字串
$PRIV_ERR ="你的操作不被允許，權限不足？";
$CLOSE_WINDOW = "，按此<a href='javascript:window.close();'>關閉視窗</a>";

?>
