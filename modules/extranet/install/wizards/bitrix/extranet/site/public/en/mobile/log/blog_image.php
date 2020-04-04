<?
require($_SERVER["DOCUMENT_ROOT"]."/mobile/headers.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$width = intval($_REQUEST["width"]);
$height = intval($_REQUEST["height"]);
$fid = intval($_REQUEST["fid"]);
$bfid = intval($_REQUEST["bfid"]);

if ($fid > 0)
{
	$db_img_arr = CFile::GetFileArray($fid);
	if ($db_img_arr)
	{
		CFile::ScaleImage($db_img_arr["WIDTH"], $db_img_arr["HEIGHT"], Array("width" => $width, "height" => $height), BX_RESIZE_IMAGE_PROPORTIONAL, $bNeedCreatePicture, $arSourceSize, $arDestinationSize);
		?><img width="<?=intval($arDestinationSize["width"]/2)?>" height="<?=intval($arDestinationSize["height"]/2)?>" src="/bitrix/components/bitrix/blog/show_file.php?fid=<?=$bfid?>&width=<?=$width?>&height=<?=$height?>" alt="" title=""><?
	}

}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php")?>