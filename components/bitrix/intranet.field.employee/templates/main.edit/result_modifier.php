<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Page\Asset;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UserTable;

CJSCore::init(['uf', 'intranet_userfield_employee']);

$selectorName = $arResult['userField']['FIELD_NAME'] . \Bitrix\Main\Security\Random::getString(5);
$fieldName = $arResult['fieldName'];

$arResult['selectorName'] = $selectorName;
$arResult['selectorNameJs'] = CUtil::JSEscape($selectorName);
$arResult['fieldName'] = $fieldName;
$arResult['fieldNameJs'] = CUtil::JSEscape($fieldName);

$arResult['jsObject'] = 'BX.Intranet.UserFieldEmployee.instance(\'' . $arResult['selectorNameJs'] . '\')';

$componentValue = [];

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

$i=0;
$results = [];
while($user = $users->fetch())
{
	$componentValue['U' . $user['ID']] = 'users';

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
$arResult['componentValue'] = $componentValue;
$arResult['isMultiple'] = ($arResult['userField']['MULTIPLE'] === 'Y');

/**
 * @var EmployeeUfComponent $component
 */
$component = $this->getComponent();
if($component->isDefaultMode())
{
	Asset::getInstance()->addJs(
		'/bitrix/components/bitrix/intranet.field.employee/templates/main.edit/default.js'
	);
}
elseif($component->isMobileMode())
{
	Asset::getInstance()->addJs(
		'/bitrix/js/mobile/userfield/mobile_field.js'
	);
	Asset::getInstance()->addJs(
		'/bitrix/components/bitrix/intranet.field.employee/templates/main.view/mobile.js'
	);
}
