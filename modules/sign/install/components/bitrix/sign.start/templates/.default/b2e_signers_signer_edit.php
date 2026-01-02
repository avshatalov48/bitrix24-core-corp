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
$userId = (int)($arParams['VAR_USER_ID'] ?? null);

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:sign.b2e.signers.signer.edit',
		'POPUP_COMPONENT_PARAMS' => [
			'LIST_ID' => $listId,
			'USER_ID' => $userId,
		],
		'POPUP_COMPONENT_USE_BITRIX24_THEME' => 'N',
		'USE_UI_TOOLBAR' => 'Y',
		'PAGE_MODE' => false,
		'PAGE_MODE_OFF_BACK_URL' => '/sign/b2e/signers/' . $listId . '/',
		'BUTTONS' => [
			'save' => [
				'TYPE' => 'save',
				'ID' => 'sign-b2e-signers-signer-edit-save-btn',
			],
			'cancel',
		],
	],
	$this->getComponent(),
);
