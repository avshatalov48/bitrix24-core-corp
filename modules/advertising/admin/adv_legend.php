<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/tools.php");
include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/advertising/colors.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/img.php");

if (function_exists("FormDecode")) FormDecode();
UnQuoteAll();

// ������� �����������
$ImageHendle = CreateImageHandle(45, 2);

$dec=ReColor($color);
$color = ImageColorAllocate($ImageHendle,$dec[0],$dec[1],$dec[2]);
if ($dash=="Y") 
{
	$style = array (
		$color,$color,
		IMG_COLOR_TRANSPARENT, 
		IMG_COLOR_TRANSPARENT, 
		IMG_COLOR_TRANSPARENT
		); 
	//$white = ImageColorAllocate($ImageHendle,255,255,255);
	//$style = array ($color,$color,$white,$white,$white); 
	ImageSetStyle($ImageHendle, $style); 
	ImageLine($ImageHendle, 3, 0, 40, 0, IMG_COLOR_STYLED);
	ImageLine($ImageHendle, 1, 1, 40, 1, IMG_COLOR_STYLED);
}
else 
{
	ImageLine($ImageHendle, 3, 0, 40, 0, $color);
	ImageLine($ImageHendle, 3, 1, 40, 1, $color);
}

/******************************************************
				���������� �����������
*******************************************************/

ShowImageHeader($ImageHendle);
?>