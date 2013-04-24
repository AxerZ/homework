<?php
// 認證碼產生程式 ---Program coded by 
// http://jax-work-archive.blogspot.com/2007/11/php.html?showComment=1232821260000#c4000714540630615955
// Revised By uhoo.tw@gmail.com
// 自傳認證碼 990401，若無傳入 v 值，則自行產生亂數，並以Session作認證；否則由傳入v值產生認證碥，並以v值作認證
// v 值為數字串藉由 LongEncode() 轉成為密文顯示

// 認證圖片寬
$imageWidth = 120;
$imageHeight = 32;
define("DEFAULT_WD_NUM", 3);  // 影響動態非傳值位數

header("Content-type:image/png");
header("Content-Disposition:filename=image_code.png");
//定義 header 的文件格式為 png，第二個定義其實沒什麼用


$wd=DEFAULT_WD_NUM;  //字數
// 由取得值產生 captacha
if(! empty($_GET['v'])){
//  include 'init.php';
  include 'base.class.php';
  $obj = new Bila_base_class();
  $verification__session= substr(strtoupper( $obj->LongEncode($_GET['v'])),0,$wd);
  //$wd= strlen($verification__session);
  $imageWidth= $wd *32+30;
  $imageHeight = 36;
}
else{
  // 開啟 session
  session_start();

  // 設定亂數種子
  mt_srand((double)microtime()*1000000);

  // 驗證碼變數
  $verification__session = '';

  // 定義顯示在圖片上的文字，可以再加上大寫字母
  $str = 'ACDEFGHJKLMNPQRSTUVWXYZ23456789';

  $l = strlen($str); //取得字串長度

  //隨機取出 $wd 個字
  for($i=0; $i<$wd; $i++){
     $num=rand(0,$l-1);
     $verification__session.= $str[$num];
  }

  // 將驗證碼記錄在 session 中
  $_SESSION["simg_code"] = $verification__session;
}


// 建立圖片物件
$im = @imagecreatetruecolor($imageWidth, $imageHeight)
or die("無法建立圖片！");


//主要色彩設定
// 圖片底色
//$bgColor = imagecolorallocate($im, rand(200,255),rand(200,255),rand(200,255));
$bgColor = imagecolorallocate($im, 252,237 ,240);

//設定圖片底色
imagefill($im,0,0,$bgColor);

//底色干擾線條
for($i=0; $i<10; $i++){

	$gray1 = imagecolorallocate($im, rand(100,255),rand(100,255),rand(100,255));
   imageline($im,rand(0,$imageWidth),rand(0,$imageHeight),
   rand($imageHeight,$imageWidth),rand(0,$imageHeight),$gray1);
}

//利用true type字型來產生圖片
for($ii=0; $ii< $wd; $ii++){
	$Color = imagecolorallocate($im, rand(0,150), rand(0,150) , rand(0,150));
	$this_wd = substr ($verification__session, $ii, 1);
	imagettftext($im, 15+rand(0,10), -15+rand(0,30), 20+ 32*$ii, 25, $Color, "ariblk.ttf", $this_wd);
}
/*
imagettftext (int im, int size, int angle,
int x, int y, int col,
string fontfile, string text)
im 圖片物件
size 文字大小
angle 0度將會由左到右讀取文字，而更高的值表示逆時鐘旋轉
x y 文字起始座標
col 顏色物件
fontfile 字形路徑，為主機實體目錄的絕對路徑，
可自行設定想要的字型
text 寫入的文字字串
*/

// 干擾像素
for($i=0;$i<30;$i++){
	$gray2 = imagecolorallocate($im, rand(60,255),rand(60,255),rand(60,255));
   	imagesetpixel($im, rand()%$imageWidth ,
   	rand()%$imageHeight , $gray2);
}

imagepng($im);
imagedestroy($im);
?>

