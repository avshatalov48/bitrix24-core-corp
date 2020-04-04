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

		<?
		/*
		// filter-preset-buttons
		BX.bindDelegate(
			BX('disk-volume-filter-preset-buttons'),
			'click',
			{
				tagName: 'A',
				className: 'main-buttons-item-link'
			},
			function(event){
				event.preventDefault();
				event.stopPropagation();

				var target = event.srcElement || event.target;
				target = BX.findParent(target, {className: 'disk-volume-menu-right-item'}, true, false);
				var presetId = BX.data(target, 'id');

				var link = BX.findChild(target, {"tag": 'a'});
				if(link)
				{
					var sefLink = link.getAttribute('href');
				}

				BX.Filter.Utils.getByClass(BX('disk-volume-filter-preset-buttons'), 'disk-volume-menu-right-item', true).forEach(function(node) {
					BX.removeClass(node, 'main-buttons-item-active');
				}, this);

				BX.addClass(target, 'main-buttons-item-active');

				var filter = BX.Main.filterManager.getById("<?= $arResult['FILTER_ID'] ?>");
				var search = filter.getSearch();
				var presets = filter.getPreset();

				search.clearInput();
				search.removePreset();
				presets.deactivateAllPresets();
				presets.resetPreset(true);
				search.hideClearButton();
				search.adjustPlaceholder();

				presets.applyPreset(presetId);
				presets.activatePreset(presetId);
				filter.applyFilter(true,true);

				if(sefLink){
					if(typeof(window.history.pushState) === "function"){
						window.history.pushState({path:sefLink},'',sefLink);
					}
				}

				return BX.PreventDefault(event);
			}
		);
		*/
		?>

		BX.bindDelegate(
			BX('disk-volume-disk-grid-<?= $component->getComponentId() ?>'),
			'click',
			{
				tagName: 'A',
				className: 'disk-volume-storage-link'
			},
			function(event){
				if(BX.data(this,'collected') !== '1')
				{
					var storageId = parseInt(BX.data(this, 'storageId'));
					var filterId = parseInt(BX.data(this, 'filterId'));
					if(storageId > 0)
					{
						BX.Disk.showActionModal({
							text: BX.message('DISK_VOLUME_PERFORMING_MEASURE_DATA'),
							showLoaderIcon: true,
							autoHide: false
						});
						BX.Disk.measureManager.callAction({
							action: '<?= $component::ACTION_MEASURE_STORAGE ?>',
							storageId: storageId,
							filterId: filterId
						});
						event.stopPropagation();
						event.preventDefault();
						return true;
					}
				}
				return true;
			}
		);

		<?
		/*
		// hints
		BX.addCustomEvent(window, "Grid::updated", BX.proxy(BX.Disk.measureManager.initGridHeadHints, BX.Disk.measureManager));
		BX.Disk.measureManager.initGridHeadHints();
		*/
		?>

		var onBeforeApplyFilter = function ()
		{
			var filter = BX.Main.filterManager.getById("<?= $arResult['FILTER_ID'] ?>");
			var presets = filter.getPreset();
			var currentPresetId = presets.getCurrentPresetId();

			BX.Filter.Utils.getByClass(BX('disk-volume-filter-preset-buttons'), 'disk-volume-menu-right-item', true).forEach(function(node)
			{
				var presetId = BX.data(node, 'id');
				if (presetId === currentPresetId)
				{
					BX.addClass(node, 'main-buttons-item-active');
				}
				else
				{
					BX.removeClass(node, 'main-buttons-item-active');
				}
			}, this);
		};
		BX.addCustomEvent('BX.Main.Filter:beforeApply', onBeforeApplyFilter);

	});
</script>
