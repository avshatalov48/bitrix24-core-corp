<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \CMain $APPLICATION */
/** @var array $arParams */
?>

<?php
$listId = (int)($arParams['VAR_LIST_ID'] ?? null);

$addSignerLink = \Bitrix\Sign\Service\Container::instance()->getUrlGeneratorService()->makeAddSignerUrl($listId);

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:sign.b2e.signers.edit',
		'POPUP_COMPONENT_PARAMS' => [
			'LIST_ID' => $listId,
		],
		'POPUP_COMPONENT_USE_BITRIX24_THEME' => 'Y',
		'USE_BACKGROUND_CONTENT' => true,
		'USE_UI_TOOLBAR' => 'Y',
		'PLAIN_VIEW' => false,
		'USE_PADDING' => true,
		'RELOAD_GRID_AFTER_SAVE' => true,
	],
	$this->getComponent()
);
