<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?><?
foreach($arResult as $id => $arPost)
{
	if(intval($arPost["arUser"]["PERSONAL_PHOTO"]) > 0)
	{
		$arResult[$id]["AVATAR_file"] = CFile::ResizeImageGet(
			$arPost["arUser"]["PERSONAL_PHOTO"],
			array("width" => 100, "height" => 100),
			BX_RESIZE_IMAGE_EXACT,
			false
		);
	}
	else 
		$arResult[$id]["AVATAR_file"] = false;
}