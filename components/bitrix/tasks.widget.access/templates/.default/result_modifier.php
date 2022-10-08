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

$data = [];
foreach ($arParams['DATA'] as $item)
{
	if (!array_key_exists($item['TASK_ID'], $arResult['DATA']['LEVELS']))
	{
		// unknown level
		continue;
	}

	/** @var Access $item */
	$rule = $item->export();
	$rule['TITLE'] = $arResult['DATA']['LEVELS'][$item['TASK_ID']]['TITLE'];
	$rule['MEMBER_ID'] = $item->getGroupId();
	$rule['MEMBER_TYPE'] = $item->getGroupPrefix();
	$rule['DISPLAY'] = 'Unknown';
	$rule['VALUE'] = Util::hashCode(rand(100, 999).rand(100, 999));
	$rule['ITEM_SET_INVISIBLE'] = '';

	switch ($rule['MEMBER_TYPE'])
	{
		case 'U':
			if (array_key_exists($item->getGroupId(), $arResult['AUX_DATA']['USERS']))
			{
				$rule['DISPLAY'] = Util\User::formatName($arResult['AUX_DATA']['USERS'][$item->getGroupId()]);
				$rule['URL'] = str_replace('#user_id#', $rule['MEMBER_ID'], $helper->getComponent()->findParameterValue('PATH_TO_USER_PROFILE'));
			}
			break;

		case 'SG':
			if (array_key_exists($item->getGroupId(), $arResult['AUX_DATA']['GROUPS']))
			{
				$rule['DISPLAY'] = htmlspecialcharsbx($arResult['AUX_DATA']['GROUPS'][$item->getGroupId()]['NAME']);
				$rule['URL'] = str_replace('#group_id#', $rule['MEMBER_ID'], $helper->getComponent()->findParameterValue('PATH_TO_GROUP'));
			}
			break;

		case 'DR':
			if (array_key_exists($item->getGroupId(), $arResult['AUX_DATA']['DEPARTMENTS']))
			{
				$rule['DISPLAY'] = htmlspecialcharsbx($arResult['AUX_DATA']['DEPARTMENTS'][$item->getGroupId()]['NAME']);
				$rule['URL'] = str_replace('#ID#', $rule['MEMBER_ID'], $helper->getComponent()->findParameterValue('PATH_TO_COMPANY_DEPARTMENT'));
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