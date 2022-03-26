<?php

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$cmpParams = [
	'TASK_ID' => isset($_REQUEST['task_id']) ? (int)$_REQUEST['task_id'] : 0,
	'PROJECT_ID' => isset($_REQUEST['project_id']) ? (int)$_REQUEST['project_id'] : 0,
	'VIEW_TYPE' => isset($_REQUEST['view']) ? (string)$_REQUEST['view'] : null,
	'SET_TITLE' => 'Y',
];

if ($_REQUEST['IFRAME'] == 'Y' && $_REQUEST['IFRAME_TYPE'] == 'SIDE_SLIDER')
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		array(
			'POPUP_COMPONENT_NAME' => 'bitrix:tasks.automation',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => $cmpParams,
			'USE_BACKGROUND_CONTENT' => false,
			'POPUP_COMPONENT_USE_BITRIX24_THEME' => 'Y',
			'DEFAULT_THEME_ID' => 'light:robots',
			'USE_PADDING' => false,
		)
	);
}
else
{
	$APPLICATION->IncludeComponent('bitrix:tasks.automation', '', $cmpParams);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
