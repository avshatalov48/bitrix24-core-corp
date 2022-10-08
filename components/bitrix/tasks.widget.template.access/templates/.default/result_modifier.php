<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Tasks\Util;
use Bitrix\Tasks\Item\Task\Template\Access;

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = require(__DIR__.'/helper.php');

if ($helper->checkHasFatals())
{
	return;
}

$this->__component->tryParseStringParameter($arParams['INPUT_PREFIX'], '');

$arResult['PATHS'] = [
	'USER' => $helper->getComponent()->findParameterValue('PATH_TO_USER_PROFILE'),
	'GROUP' => $helper->getComponent()->findParameterValue('PATH_TO_GROUP'),
	'DEPARTMENT' => $helper->getComponent()->findParameterValue('PATH_TO_COMPANY_DEPARTMENT')
];

$permissions = [];
foreach ($arParams['PERMISSIONS'] as $permission)
{
	$accessCode = $permission->getAccessCode();
	if (!array_key_exists($accessCode, $permissions))
	{
		$permissions[$accessCode] = $permission;
		continue;
	}

	if ((int) $permission->getPermissionId() === \Bitrix\Tasks\Access\Permission\PermissionDictionary::TEMPLATE_FULL && (int) $permission->getValue())
	{
		$permissions[$accessCode] = $permission;
		continue;
	}
}

$data = [];
foreach ($permissions as $permission)
{
	$memberId = $permission->getMemberId();
	$memberType = $permission->getMemberPrefix();

	/* @var \Bitrix\Tasks\Access\Permission\TasksTemplatePermission $permission */
	$rule = [];
	$rule['TITLE'] = \Bitrix\Tasks\Access\Permission\PermissionDictionary::getTitle($permission->getPermissionId());
	$rule['VALUE'] = Util::hashCode(rand(100, 999).rand(100, 999));
	$rule['ID'] = $permission->getId();
	$rule['MEMBER_ID'] = $memberId;
	$rule['MEMBER_TYPE'] = $memberType;
	$rule['PERMISSION_ID'] = $permission->getPermissionId();


	switch ($memberType)
	{
		case 'U':
			if (array_key_exists($memberId, $arResult['AUX_DATA']['USERS']))
			{
				$rule['DISPLAY'] = Util\User::formatName($arResult['AUX_DATA']['USERS'][$memberId]);
				$rule['URL'] = str_replace('#user_id#', $memberId, $helper->getComponent()->findParameterValue('PATH_TO_USER_PROFILE'));
			}
			break;

		case 'SG':
			if (array_key_exists($memberId, $arResult['AUX_DATA']['GROUPS']))
			{
				$rule['DISPLAY'] = htmlspecialcharsbx($arResult['AUX_DATA']['GROUPS'][$memberId]['NAME']);
				$rule['URL'] = str_replace('#group_id#', $memberId, $helper->getComponent()->findParameterValue('PATH_TO_GROUP'));
			}
			break;

		case 'DR':
			if (array_key_exists($memberId, $arResult['AUX_DATA']['DEPARTMENTS']))
			{
				$rule['DISPLAY'] = htmlspecialcharsbx($arResult['AUX_DATA']['DEPARTMENTS'][$memberId]['NAME']);
				$rule['URL'] = str_replace('#ID#', $memberId, $helper->getComponent()->findParameterValue('PATH_TO_COMPANY_DEPARTMENT'));
			}
			break;
	}

	$data[] = $rule;
}

$arResult['JS_DATA'] = [
	'data' => $data,
	'levels' => array_map(
		function($item){
			return [
				'ID' => $item['ID'],
				'TITLE' => $item['TITLE'],
			];
		},
		$arResult['DATA']['LEVELS']
	),
	'mainDepartment' => $arResult['DATA']['MAIN_DEPARTMENT']
];