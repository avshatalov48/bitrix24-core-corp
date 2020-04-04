<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var \Bitrix\Disk\Internals\BaseComponent $component */
/** @var \CDiskVolumeComponent $component */

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

include_once('head.php');

if (!empty($arResult['ERROR_MESSAGE']))
{
	return;
}


?>
<div class="bx-disk-volume-structure-path">
	<? if ($arResult['ADMIN_MODE']): ?>
		<a href="<?= $component->getActionUrl(array('action' => $component::ACTION_DISKS)) ?>" class="disk-volume-list-back"><?= Loc::getMessage('DISK_VOLUME_LIST_BACK'); ?></a>
	<? endif ?>

	<? if (count($arResult['BREAD_CRUMB']) > 0): ?>
		<div id="bx-disk-volume-bread-crumb" class="bx-disk-volume-bread-crumbs">
			<? foreach ($arResult['BREAD_CRUMB'] as $item): ?>
				<span class="bx-disk-volume-bread-crumbs-item-container">
					<a href="<?= $item['LINK'] ?>" class="bx-disk-volume-bread-crumbs-item-link" data-storageId="<?=$item['STORAGE_ID']?>" data-folderId="<?=$item['FOLDER_ID']?>" data-filterId="<?=$item['FILTER_ID']?>" data-collected="<?=$item['COLLECTED']?>">
						<?= $item['NAME'] ?>
					</a>
				</span>
			<? endforeach; ?>
		</div>
	<? endif ?>
</div>
<?



?>
<div id="disk-volume-disk-grid-<?= $component->getComponentId() ?>">
	<div class="disk-volume-border"></div>
	<?


	$APPLICATION->IncludeComponent(
		'bitrix:main.ui.grid',
		'',
		array(
			'GRID_ID' => $arResult['GRID_ID'],
			'HEADERS' => $arResult['HEADERS'],
			'SORT' => isset($arResult['SORT']) ? $arResult['SORT'] : array(),
			'SORT_VARS' => isset($arResult['SORT_VARS']) ? $arResult['SORT_VARS'] : array(),
			'ROWS' => isset($arResult['GRID_DATA']) ? $arResult['GRID_DATA'] : array(),

			'AJAX_MODE' => 'Y',
			'AJAX_OPTION_JUMP' => 'N',
			'AJAX_OPTION_STYLE' => 'N',
			'AJAX_OPTION_HISTORY' => 'N',

			'ALLOW_COLUMNS_SORT' => true,
			'ALLOW_ROWS_SORT' => false,
			'ALLOW_COLUMNS_RESIZE' => true,
			'ALLOW_HORIZONTAL_SCROLL' => true,
			'ALLOW_SORT' => true,
			'ALLOW_PIN_HEADER' => true,
			'SHOW_ACTION_PANEL' => true,
			'ACTION_PANEL' => $arResult['GROUP_ACTIONS'],

			'SHOW_CHECK_ALL_CHECKBOXES' => true,
			'SHOW_ROW_CHECKBOXES' => true,
			'SHOW_ROW_ACTIONS_MENU' => true,
			'SHOW_GRID_SETTINGS_MENU' => true,
			'SHOW_NAVIGATION_PANEL' => true,
			'SHOW_PAGINATION' => isset($arResult['GRID_DATA']) && ($arResult['ROWS_COUNT'] > count($arResult['GRID_DATA'])),
			'SHOW_SELECTED_COUNTER' => true,
			'SHOW_TOTAL_COUNTER' => true,
			'SHOW_PAGESIZE' => true,

			'ENABLE_COLLAPSIBLE_ROWS' => false,

			'SHOW_MORE_BUTTON' => false,
			'NAV_OBJECT' => $arResult['NAV_OBJECT'],
			'TOTAL_ROWS_COUNT' => $arResult['ROWS_COUNT'],
			'CURRENT_PAGE' => $arResult['CURRENT_PAGE'],
			'DEFAULT_PAGE_SIZE' => 20,
			'PAGE_SIZES' => array(
				array('NAME' => '20', 'VALUE' => '20'),
				array('NAME' => '50', 'VALUE' => '50'),
				array('NAME' => '100', 'VALUE' => '100'),
			),
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);


	?>
</div>

<script type="text/javascript">
	BX(function () {

		var goToFolder = function(event){
			event.stopPropagation();
			if(BX.data(this,'collected') !== '1')
			{
				event.preventDefault();
				var storageId = parseInt(BX.data(this, 'storageId'));
				var folderId = parseInt(BX.data(this, 'folderId'));
				var filterId = parseInt(BX.data(this, 'filterId'));
				if(storageId > 0 && folderId > 0)
				{
					BX.Disk.showActionModal({
						text: BX.message('DISK_VOLUME_PERFORMING_MEASURE_DATA'),
						showLoaderIcon: true,
						autoHide: false
					});
					BX.Disk.measureManager.callAction({
						action: '<?= $component::ACTION_MEASURE_FOLDER ?>',
						storageId: storageId,
						folderId: folderId,
						filterId: filterId
					});
					return BX.PreventDefault(event);
				}
			}
			return true;
		};

		BX.bindDelegate(
			BX('disk-volume-disk-grid-<?= $component->getComponentId() ?>'),
			'click',
			{
				tagName: 'A',
				className: 'disk-volume-folder-link'
			},
			goToFolder
		);

		BX.bindDelegate(
			BX('bx-disk-volume-bread-crumb'),
			'click',
			{
				tagName: 'A',
				className: 'bx-disk-volume-bread-crumbs-item-link'
			},
			goToFolder
		);

		// hints
		//BX.addCustomEvent(window, "Grid::updated", BX.proxy(BX.Disk.measureManager.initGridHeadHints, BX.Disk.measureManager));
		//BX.Disk.measureManager.initGridHeadHints();

	});
</script>
