<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

$menuId = $request->get('menuId');
$siteId = $request->get('siteId');
$mode = ($request->get('create') == 'Y') ? 'CREATE' : 'LIST';
$afterCreate = '/kb/wiki/#site_show#/view/#landing_edit#/';

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:landing.binding.menu',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'TYPE' => 'KNOWLEDGE',
			'MODE' => $mode,
			'MENU_ID' => $menuId,
			'SITE_ID' => $siteId,
			'PATH_AFTER_CREATE' => $afterCreate
		],
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => 'Y'
	]
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
