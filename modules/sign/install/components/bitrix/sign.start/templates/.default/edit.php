<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \CMain $APPLICATION */
/** @var array $arParams */
?>

<?php
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:sign.editor',
		'POPUP_COMPONENT_PARAMS' => [
			'DOC_ID' => $arParams['VAR_DOC_ID']
		],
		'PLAIN_VIEW' => true,
		'USE_BACKGROUND_CONTENT' => false,
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => false
	],
	$this->getComponent()
);
