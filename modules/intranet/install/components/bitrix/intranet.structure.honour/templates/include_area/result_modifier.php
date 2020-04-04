<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (is_array($arResult['USERS']))
{
	foreach ($arResult['USERS'] as $key => $arUser)
	{
		if ($arUser['PERSONAL_PHOTO'])
		{
			$arImage = CIntranetUtils::InitImage($arUser['PERSONAL_PHOTO'], 50);
			$arUser['PERSONAL_PHOTO'] = $arImage['IMG'];
		}
		
		$arResult['USERS'][$key] = $arUser;
	}
}
?>