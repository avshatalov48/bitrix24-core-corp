<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arParams =& $this->__component->arParams;

$pathTask = str_replace(array('#action#', '#ACTION#'), 'view', $arParams['PATH_TO_TASKS_TASK']);
$pathTask = str_replace(array('#task_id#', '#TASK_ID#'), '#id#', $pathTask);

$this->__component->tryParseArrayParameter($arParams["COLUMNS"], array(
	'TITLE', 'DEADLINE', 'RESPONSIBLE_ID'
));

$columns = array();
foreach($arParams["COLUMNS"] as $i => $column)
{
	if((string) $column == $column)
	{
		$columns[] = array(
			'TITLE' => Loc::getMessage('TASKS_TWRS_SGRID_FIELD_'.$column),
			'SOURCE' => $column,
		);
	}
	else
	{
		$columns[] = $column;
	}
}
$arResult['COLUMNS'] = $columns;

$data = array();
foreach($arParams['DATA'] as $i => $item)
{
	$arParams['DATA'][$i]['ENTITY_TYPE'] = $item['ENTITY_TYPE'] == 'TT' ? 'TT' : 'T';
	$arParams['DATA'][$i]['URL'] = str_replace('#id#', $item['ID'], $pathTask);
	$arParams['DATA'][$i]['RESPONSIBLE_FORMATTED_NAME'] = \Bitrix\Tasks\Util\User::formatName($arParams['USERS'][$item['RESPONSIBLE_ID']], false, $arParams["NAME_TEMPLATE"]);
	$arParams['DATA'][$i]['RESPONSIBLE_URL'] = CComponentEngine::makePathFromTemplate(
		$arParams["PATH_TO_USER_PROFILE"],
		array(
			"user_id" => $item["RESPONSIBLE_ID"],
		)
	);
}