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
	$models[] = CCrmMobileHelper::PrepareActivityData($item);
endforeach;

echo CUtil::PhpToJSObject(
	array(
		'DATA' => array(
			'MODELS' => $models,
			'NEXT_PAGE_URL' => $arResult['NEXT_PAGE_URL'],
			'IS_FILTERED' => $arResult['IS_FILTERED'],
			'GRID_FILTER_ID' => $arResult['GRID_FILTER_ID'],
			'GRID_FILTER_NAME' => $arResult['GRID_FILTER_NAME']
		)
	)
);
die();
