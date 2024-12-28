<?php

/**
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\UI\Toolbar\Facade\Toolbar;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Bitrix\Main\Loader::includeModule('biconnector');

if (!empty($arResult['ERROR_MESSAGES']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.info.error',
		'',
		[
			'TITLE' => $arResult['ERROR_MESSAGES'][0],
		]
	);

	return;
}

Extension::load([
	'biconnector.dataset-import',
	'ui.buttons',
	'ui.entity-selector',
	'ui.notification',
	'ui.forms',
	'ui.layout-form',
]);

Toolbar::deleteFavoriteStar();

$APPLICATION->SetTitle($arResult['SOURCE_FIELDS']['title'] ?? Loc::getMessage('EXTERNAL_CONNECTION_NEW'));

?>

<form class="ui-form" id="connection-form"></form>

<?php
$buttons = [
	[
		'TYPE' => 'save',
		'ONCLICK' => 'BX.BIConnector.ExternalConnectionForm.Instance.onClickSave(); return false;',
		'ID' => 'connection-button-save',
		'CAPTION' => $arResult['SOURCE_FIELDS']['id'] ? null : Loc::getMessage('EXTERNAL_CONNECTION_SAVE'),
	],
	'cancel'
];
$APPLICATION->IncludeComponent(
	'bitrix:ui.button.panel',
	'',
	[
		'BUTTONS' => $buttons,
		'ALIGN' => 'center'
	],
	false
);
?>

<script>
	BX.ready(() => {
		BX.message(<?= Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);
		BX.BIConnector.ExternalConnectionForm.Instance = new BX.BIConnector.ExternalConnectionForm(<?= Json::encode([
			'sourceFields' => $arResult['SOURCE_FIELDS'],
			'fieldsConfig' => $arResult['FIELDS_CONFIG'],
			'supportedDatabases' => $arResult['SUPPORTED_DATABASES'],
			'signedParameters' => $arResult['SIGNED_PARAMETERS'],
		])?>);
	});
</script>
