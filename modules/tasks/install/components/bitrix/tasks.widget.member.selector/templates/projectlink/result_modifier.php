<?
use Bitrix\Tasks\Integration\SocialNetwork;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = new \Bitrix\Tasks\UI\Component\TemplateHelper('TasksWidgetMemberSelectorProjectLink', $this, array(
	'RELATION' => array(
		'tasks',
		'tasks_integration_socialnetwork',
	),
));

$this->__component->tryParseBooleanParameter($arParams['READ_ONLY'], false);
$this->__component->tryParseStringParameter($arParams['ENTITY_ROUTE'], ''); // todo: more strict check here
$this->__component->tryParseIntegerParameter($arParams['ENTITY_ID'], true, 0);

$gPref = SocialNetwork::getGroupEntityPrefix();
$gUrl = \Bitrix\Tasks\UI::convertActionPathToBarNotation(
	$helper->getComponent()->findParameterValue('PATH_TO_GROUP'),
	array('group_id' => 'ID')
);

$group = array();
foreach($arParams['DATA'] as $i => $item)
{
	if((string) $item['NAME'] == '')
	{
		continue;
	}

	if(!array_key_exists('ENTITY_TYPE', $item))
	{
		$item['ENTITY_TYPE'] = $gPref;
	}

	$entityType = (string) $item['ENTITY_TYPE'];
	if($entityType != $gPref)
	{
		continue;
	}

	$item = SocialNetwork\Group::extractPublicData($item);
	$url = $gUrl;

	$item['entityType'] = $entityType;

	// define value
	$item['VALUE'] = $entityType.$item['ID'];

	// define display
	$display = $item['ID'];

	if($item['NAME'])
	{
		$display = $item['NAME'];
	}
	elseif($item['TITLE'])
	{
		$display = $item['TITLE'];
	}

	$item['DISPLAY'] = $display;

	// define URL
	$item['URL'] = $item['ID'] ? str_replace('{{ID}}', $item['ID'], $url) : 'javascript:void(0);';

	$group = $item;
	break;
}
$arResult['DATA'] = $group;

$arResult['JS_DATA'] = array(
	'path' => array(
		'SG' => $gUrl,
	),
	'entityId' => $arParams['ENTITY_ID'],
	'entityRoute' => $arParams['ENTITY_ROUTE']
);