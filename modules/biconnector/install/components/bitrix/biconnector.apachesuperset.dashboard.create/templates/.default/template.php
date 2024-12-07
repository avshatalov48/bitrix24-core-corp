<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 * @var CMain $APPLICATION
 */

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\UI\Toolbar\Facade\Toolbar;

Loader::includeModule('biconnector');

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

Loader::includeModule('ui');
Extension::load([
	'biconnector.apache-superset-analytics',
	'biconnector.dashboard-parameters-selector',
	'ui.forms',
	'ui.hint',
]);

Toolbar::deleteFavoriteStar();
$APPLICATION->setTitle($arResult['TITLE']);

?>

<div class="dashboard-create-container">
	<form id='dashboard-create-form' name='dashboard-create-form'></form>
	<?php
	$buttons = [
		[
			'TYPE' => 'save',
			'ONCLICK' => 'BX.BIConnector.SupersetDashboardCreateManager.Instance.onClickSave(); return false;',
			'ID' => 'dashboard-button-save'
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
</div>

<script>
	BX.ready(() => {
		BX.message(<?= Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);
		BX.BIConnector.SupersetDashboardCreateManager.Instance =
			new BX.BIConnector.SupersetDashboardCreateManager(<?= Json::encode($arResult['SETTINGS']) ?>);
	});
</script>
