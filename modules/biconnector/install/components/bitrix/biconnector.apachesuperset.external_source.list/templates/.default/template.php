<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

/**
 * @var CMain $APPLICATION
 * @var array $arResult
 */

Extension::load([
	'biconnector.dataset-import',
	'ui.dialogs.messagebox',
	'ui.hint',
	'ui.buttons',
	'ui.icons',
	'ui.icon-set.actions',
	'ui.alerts',
]);

if (!empty($arResult['ERROR_MESSAGE']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.info.error',
		'',
		[
			'TITLE' => $arResult['ERROR_MESSAGE'],
		]
	);

	return;
}

if ($arResult['ENABLED_TRACKING_SOURCE_DATASET_INFO'])
{
?>
	<div class='ui-alert ui-alert-primary ui-alert-icon-info'>
		<span class='ui-alert-message'><?= Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_GRID_TRACKING_SOURCE_DATASET_INFO') ?></span>
	</div>
<?php
}
?>

<div id="biconnector-external-source-grid">
	<?php

	$grid = $arResult['GRID'];

	$APPLICATION->IncludeComponent(
		'bitrix:main.ui.grid',
		'',
		\Bitrix\Main\Grid\Component\ComponentParams::get(
			$grid,
			[
				'CURRENT_PAGE' => $grid->getPagination()?->getCurrentPage(),
				'STUB' => $arResult['GRID_STUB']
			])
	);
	?>
</div>

<script>
	BX.ready(() => {
		BX.message(<?= Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);
		BX.BIConnector.ExternalSourceManager.Instance = new BX.BIConnector.ExternalSourceManager(<?= Json::encode([
			'gridId' => $grid?->getId(),
		])?>);
	});
</script>
