<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$APPLICATION->IncludeComponent(
	'bitrix:menu',
    'left',
	//'left_vertical',
    //'desktop',
	[
		"ROOT_MENU_TYPE" => file_exists($_SERVER['DOCUMENT_ROOT'] . SITE_DIR . '.superleft.menu_ext.php') ? 'superleft' : 'top',
		"MENU_CACHE_TYPE" => "Y",
		"MENU_CACHE_TIME" => "604800",
		"MENU_CACHE_USE_GROUPS" => "N",
		"MENU_CACHE_USE_USERS" => "Y",
		"CACHE_SELECTED_ITEMS" => "N",
		"MENU_CACHE_GET_VARS" => [],
		"MAX_LEVEL" => "1",
		"USE_EXT" => "Y",
		"DELAY" => "N",
		"ALLOW_MULTI_SELECT" => "N",
		"ADD_ADMIN_PANEL_BUTTONS" => "N",
	],
	$this->getComponent()
);
