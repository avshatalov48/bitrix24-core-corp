<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$arResult["ELEMENT"]["URL"]["~DOWNLOAD"] = $arResult["ELEMENT"]["URL"]["DOWNLOAD"];
if (!isset($arParams["THUMB_SIZE"]) && isset($_REQUEST["size"]))
	$arParams["THUMB_SIZE"] = ($_REQUEST["size"] > 0 && $_REQUEST["size"] < 600 ? $_REQUEST["size"] : 600);
if (isset($arParams["THUMB_SIZE"])) {
	if (CFile::IsImage($arResult["ELEMENT"]['NAME'], $arResult["ELEMENT"]['FILE']["CONTENT_TYPE"]))
	{
		CFile::ScaleImage(
			$arResult["ELEMENT"]["FILE"]["WIDTH"], $arResult["ELEMENT"]["FILE"]["HEIGHT"],
			array("width" => $arParams["THUMB_SIZE"], "height" => $arParams["THUMB_SIZE"]),
			BX_RESIZE_IMAGE_PROPORTIONAL,
			$bNeedCreatePicture,
			$arSourceSize, $arDestinationSize);
		if ($bNeedCreatePicture)
		{
			$arResult["ELEMENT"]["original"] = array(
				"src" => $arResult["ELEMENT"]["URL"]["DOWNLOAD"],
				"width" => $arResult["ELEMENT"]["FILE"]["WIDTH"],
				"height" => $arResult["ELEMENT"]["FILE"]["HEIGHT"]
			);
			$arResult["ELEMENT"]["FILE"]["WIDTH"] = $arDestinationSize["width"];
			$arResult["ELEMENT"]["FILE"]["HEIGHT"] = $arDestinationSize["height"];

			$arResult["ELEMENT"]["URL"]["DOWNLOAD"] = WDAddPageParams($arResult["ELEMENT"]["URL"]["DOWNLOAD"],
				array("cache_image" => "Y", "width" => $arParams["THUMB_SIZE"], "height" => $arParams["THUMB_SIZE"]));
		}
	}
}
?>