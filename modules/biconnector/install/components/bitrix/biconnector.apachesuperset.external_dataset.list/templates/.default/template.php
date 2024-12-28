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

?>

<div id="biconnector-external-dataset-grid">
	<?php

	$grid = $arResult['GRID'];

	$APPLICATION->IncludeComponent(
		'bitrix:main.ui.grid',
		'',
		\Bitrix\Main\Grid\Component\ComponentParams::get(
			$grid,
			[
				'CURRENT_PAGE' => $grid->getPagination()?->getCurrentPage(),
				'STUB' => $arResult['GRID_STUB'],
			])
	);
	?>
</div>

<script>
	BX.ready(() => {
		BX.message(<?= Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);
		BX.BIConnector.ExternalDatasetManager.Instance = new BX.BIConnector.ExternalDatasetManager(<?= Json::encode([
			'gridId' => $grid?->getId(),
		])?>);
	});
</script>
