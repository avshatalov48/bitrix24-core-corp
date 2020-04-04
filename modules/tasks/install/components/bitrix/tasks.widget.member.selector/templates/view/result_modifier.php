<?
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Util\Site;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = require(dirname(__FILE__).'/helper.php');
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

$this->__component->tryParseBooleanParameter($arParams['ENABLE_SYNC'], false);
$this->__component->tryParseStringParameter($arParams['ENTITY_ROUTE'], ''); // todo: more strict check here
$this->__component->tryParseIntegerParameter($arParams['ENTITY_ID'], true, 0);
$this->__component->tryParseBooleanParameter($arParams['PUBLIC_MODE'], false);
$this->__component->tryParseStringParameter($arParams['TITLE'], '');
$this->__component->tryParseBooleanParameter($arParams['HIDE_IF_EMPTY'], true);
$this->__component->tryParseBooleanParameter($arParams['DISABLE_JS_IF_READ_ONLY'], true);
$this->__component->tryParseStringParameter($arParams['HEADER_BUTTON_LABEL_IF_READ_ONLY'], '');

$entityId = $arParams['ENTITY_ID'];
$this->__component->tryParseBooleanParameter($arParams['READ_ONLY'], false);
$this->__component->tryParseListParameter($arParams['ROLE'], array('RESPONSIBLE', 'ORIGINATOR', 'RESPONSIBLES', 'AUDITORS', 'ACCOMPLICES'));
$this->__component->tryParseArrayParameter($arParams['USER'], array());
$this->__component->tryParseStringParameter($arParams['FIELD_NAME'], $arParams['ROLE']);

$arParams["NAME_TEMPLATE"] = $helper->findParameterValue('NAME_TEMPLATE');
$this->__component->tryParseStringParameter($arParams['NAME_TEMPLATE'], Site::getUserNameFormat());
$arParams['PATH_TO_USER_PROFILE'] = $helper->findParameterValue('PATH_TO_USER_PROFILE');
$arParams["PATH_TO_USER_PROFILE"] = \Bitrix\Tasks\Integration\Socialnetwork\Task::addContextToURL($arParams['PATH_TO_USER_PROFILE'], $entityId);

$uUrl = \Bitrix\Tasks\UI::convertActionPathToBarNotation(
	$helper->findParameterValue('PATH_TO_USER_PROFILE'),
	array('user_id' => 'ID')
);
$gUrl = \Bitrix\Tasks\UI::convertActionPathToBarNotation(
	$helper->findParameterValue('PATH_TO_GROUP'),
	array('group_id' => 'ID')
);

$role = $arParams['ROLE'];

$arResult['TEMPLATE_DATA']['READ_ONLY'] = $arParams["PUBLIC_MODE"] || !$entityId || $arParams['READ_ONLY'];

if(count($arParams['DATA']))
{
	$currentUserId = User::getId();

	$formattedUsers = array();
	foreach($arParams['DATA'] as $i => $item)
	{
		$formattedUsers[$item['ID']] = $item;
	}

	foreach($formattedUsers as $i => $item)
	{
		$formattedUsers[$i] = $helper->formatUser($item, $arParams);
	}

	$arParams['DATA'] = $formattedUsers;
}

$arResult['JS_DATA'] = array(
	'nameTemplate' => empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]),
	'data' => $arParams['DATA'],
	'entityId' => $arParams['ENTITY_ID'],
	'entityRoute' => $arParams['ENTITY_ROUTE'],
	'enableSync' => $arParams['ENABLE_SYNC'],
	'role' => $role,
	'fieldName' => $arParams['FIELD_NAME'],
	'min' => $arParams['MIN'],
	'max' => is_infinite($arParams['MAX']) ? 999999 : $arParams['MAX'],
	'pathToTasks' => $arParams['PATH_TO_TASKS'],
	'user' => $helper->formatUser($arParams['USER'], $arParams),

	'path' => array(
		'SG' => $gUrl,
		'U' => $uUrl,
	),
);