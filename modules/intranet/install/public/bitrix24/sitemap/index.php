<?
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$GLOBALS['APPLICATION']->includeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:menu',
		'POPUP_COMPONENT_TEMPLATE_NAME' => 'map',
		'POPUP_COMPONENT_PARAMS' => [
			'ROOT_MENU_TYPE' => 'superleft',
			'CHILD_MENU_TYPE' => 'left',
			'MENU_TYPES' => ['superleft', 'left', 'sub'],
			'MENU_CACHE_TYPE' => 'Y',
			'MENU_CACHE_TIME' => '0',
			'MENU_CACHE_USE_GROUPS' => 'N',
			'MENU_CACHE_USE_USERS' => 'Y',
			'CACHE_SELECTED_ITEMS' => 'N',
			'MENU_CACHE_GET_VARS' => array(),
			'MAX_LEVEL' => '3',
			'USE_EXT' => 'Y',
			'DELAY' => 'N',
			'ALLOW_MULTI_SELECT' => 'N'
		],
		'USE_PADDING' => false,
	]
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
