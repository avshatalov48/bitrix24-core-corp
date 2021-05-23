<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

$GLOBALS['APPLICATION']->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$models = array();
foreach($arResult['ITEMS'] as &$item):
	$model = array(
		'OWNER_ID' => $item['OWNER_ID'],
		'OWNER_TYPE_NAME' => CCrmOwnerType::ResolveName($item['OWNER_TYPE_ID']),
		'TITLE' => $item['TITLE'],
		'DESCRIPTION' => $item['DESCRIPTION'],
		'IMAGE_URL' => $item['IMAGE_URL'],
		'COMMUNICATIONS' => $item['COMMUNICATIONS'],
	);
	$model['ID'] = $model['OWNER_TYPE_NAME'].'_'.$model['OWNER_ID'];

	$models[] = $model;
	unset($model);
endforeach;
unset($item);

echo CUtil::PhpToJSObject(
	array(
		'DATA' => array(
			'MODELS' => $models,
			'SHOW_SEARCH_PANEL' => $arResult['SHOW_SEARCH_PANEL'],
			'SEARCH_PLACEHOLDER' => $arResult['SEARCH_PLACEHOLDER'],
			'SEARCH_PAGE_URL' => $arResult["SEARCH_PAGE_URL"]
		)
	)
);
die();
