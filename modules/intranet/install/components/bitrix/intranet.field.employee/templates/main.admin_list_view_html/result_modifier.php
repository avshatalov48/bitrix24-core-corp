<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

$user = CIEmployeeProperty::_GetUserArray($arResult['additionalParameters']['VALUE']);
if($user)
{
	if(defined('PUBLIC_MODE') && PUBLIC_MODE == 1)
	{
		$titleUserId = $user['ID'];
	}
	else
	{
		$titleUserId = '<a title="' . Loc::getMessage('MAIN_EDIT_USER_PROFILE') . '" href="user_edit.php?ID=' . $user['ID'] . '&lang=' . LANGUAGE_ID . '">' . $user['ID'] . '</a>';
	}

	$arResult['titleUserId'] = $titleUserId;
	$arResult['user'] = $user;
}