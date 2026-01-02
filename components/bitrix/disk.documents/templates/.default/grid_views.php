<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

/** @var array $arResult */

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

$gridOptions = new Main\Grid\Options($arResult['GRID_ID']);

$byColumn = key($arResult['SORT']);
$direction = reset($arResult['SORT']);
$inverseDirection = mb_strtolower($direction) === 'desc'? 'asc' : 'desc';
$isTile = $arResult['GRID_VIEW']['MODE'] === 'tile';
$isBigTile = $arResult['GRID_VIEW']['VIEW_SIZE'] === 'xl';

Extension::load(['ui.fonts.opensans', 'ui.navigationpanel', 'ui.counterpanel']);

$isBitrix24Template = (SITE_TEMPLATE_ID === 'bitrix24');

$isBitrix24Template && $this->setViewTarget('below_pagetitle');

$activeButtonId = match (true) {
	!$isTile => 'list',
	$isTile && !$isBigTile => 'smallTile',
	$isTile && $isBigTile => 'tile',
};

$switcherId = 'disk-documents-grid-view-switcher-target';
?>
<div class="disk-documents-grid-view-switcher">
	<div class="ui-actions-bar">
		<div class="ui-actions-bar__panel" id="<?= $switcherId?>">
		</div>
	</div>
</div>
<?php
$isBitrix24Template && $this->endViewTarget();
?>
<script>
	BX.ready(function() {
		new BX.Disk.Documents.GridSwitcher({
			targetId: '<?= $switcherId?>',
			activeButtonId: '<?= $activeButtonId?>',
		}).init();
	});
</script>
