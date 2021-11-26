<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
//IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/bitrix24/tasks/config/permissions/index.php");
//$APPLICATION->SetTitle(GetMessage("PAGE_TITLE"));

global $USER;

if (
	!\Bitrix\Main\Loader::includeModule('tasks')
	|| !class_exists('\Bitrix\Tasks\Access\ActionDictionary')
)
{
	exit();
}

\Bitrix\Main\Loader::includeModule('socialnetwork');

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:tasks.config.permissions',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [],
		'USE_UI_TOOLBAR' => 'Y',
		'USE_PADDING' => false,
		'PLAIN_VIEW' => false,
		'PAGE_MODE' => false,
		'PAGE_MODE_OFF_BACK_URL' => "/company/personal/user/".$USER->getId()."/tasks/"
	]
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>

