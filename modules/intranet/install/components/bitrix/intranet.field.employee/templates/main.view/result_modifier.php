<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UserTable;

CJSCore::init(['uf', 'intranet_userfield_employee']);

$resultItem = [];
if($arResult['userField']['EDIT_IN_LIST'] !== 'Y')
{
	$resultItem['disabled'] = 'disabled';
}

$pathToUser = COption::GetOptionString(
	'main',
	'TOOLTIP_PATH_TO_USER',
	false,
	SITE_ID
);
$pathToUser = ($pathToUser ?: SITE_DIR . 'company/personal/user/#user_id#/');

$users = UserTable::getList([
	'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'PERSONAL_PHOTO', 'WORK_POSITION'],
	'filter' => [
		'=ID' => $arResult['value']
	],
]);

$results = [];
$i = 0;
while($user = $users->fetch())
{
	$name = \CUser::FormatName(
		\CSite::GetNameFormat(),
		$user,
		true,
		false
	);

	$href = str_replace('#user_id#', $user['ID'], $pathToUser);

	$resultItem = [
		'id' => $arParams['userField']['FIELD_NAME'] . '_' . $i++,
		'name' => HtmlFilter::encode($name),
		'userId' => $user['ID'],
		'href' => HtmlFilter::encode($href),
		'workPosition' => HtmlFilter::encode($user['WORK_POSITION']),
		'personalPhoto' => false
	];

	if(isset($user['PERSONAL_PHOTO']))
	{
		$imageFile = \CFile::GetFileArray($user['PERSONAL_PHOTO']);
		if($imageFile !== false)
		{
			$tmpFile = \CFile::ResizeImageGet(
				$imageFile,
				['width' => 60, 'height' => 60],
				BX_RESIZE_IMAGE_EXACT
			);
			$resultItem['personalPhoto'] = $tmpFile['src'];
		}
	}

	$results[] = $resultItem;
}

$arResult['value'] = $results;

/**
 * @var EmployeeUfComponent $component
 */
$component = $this->getComponent();

if($component->isMobileMode())
{
	Asset::getInstance()->addJs(
		'/bitrix/js/mobile/userfield/mobile_field.js'
	);
	Asset::getInstance()->addJs(
		'/bitrix/components/bitrix/intranet.field.employee/templates/main.view/mobile.js'
	);
}
