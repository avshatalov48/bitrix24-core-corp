<?php

use Bitrix\BIConnector\Superset\Grid\UnusedElementsGrid;
use Bitrix\Main\Grid;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var CMain $APPLICATION
 * @var array $arResult
 */

Extension::load([
	'ui.dialogs.messagebox',
	'ui.hint',
]);

?>

<div id="biconnector-unused-elements-grid">
<?php

/** @var UnusedElementsGrid $grid */
$grid = $arResult['GRID'];

$params = Grid\Component\ComponentParams::get(
	$grid,
	[
		'CURRENT_PAGE' => $grid->getPagination()?->getCurrentPage(),
		'STUB' => $arResult['GRID_STUB'],
	],
);

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	$params,
);
?>
</div>

<script>
	BX.ready(() => {
		BX.message(<?= Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);
		BX.BIConnector.UnusedElementsGridManager.Instance = new BX.BIConnector.UnusedElementsGridManager(<?= Json::encode([
			'gridId' => $grid?->getId(),
		])?>);
	});
</script>
