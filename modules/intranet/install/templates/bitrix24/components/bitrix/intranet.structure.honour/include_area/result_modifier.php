<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (is_array($arResult['USERS']))
{
	foreach ($arResult['USERS'] as $key => $arUser)
	{
		if ($arUser['PERSONAL_PHOTO'])
		{
			$imageFile = CFile::GetFileArray($arUser['PERSONAL_PHOTO']);
			if ($imageFile !== false)
			{
				$arFileTmp = CFile::ResizeImageGet(
					$imageFile,
					array("width" => 42, "height" => 42),
					BX_RESIZE_IMAGE_EXACT,
					true
				);
			}

			if($arFileTmp && array_key_exists("src", $arFileTmp))
				$arUser["PERSONAL_PHOTO"] = CFile::ShowImage($arFileTmp["src"], 42, 42);
		}
		
		$arResult['USERS'][$key] = $arUser;
	}
}
?>