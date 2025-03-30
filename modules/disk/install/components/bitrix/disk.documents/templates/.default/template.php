<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @var CBitrixComponent $component */
/** @var CBitrixComponentTemplate $this */

use \Bitrix\Disk;
use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

CJSCore::Init(array(
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.viewer',
	'ui.ears',
	'ui.tilegrid',
	'ui.info-helper',
	'ui.dialogs.messagebox',
	'disk.users',
	'disk.external-link',
	'disk.sharing-legacy-popup',
	'disk.viewer.document-item',
	'disk.viewer.board-item',
	'disk.viewer.actions',
	'disk.document',
	'ui.tour',
	'main.core',
	'spotlight',
));
?>
<div id='bx-disk-container posr' class='bx-disk-container'>
	<div class="bx-disk-interface-filelist">
<script>
	BX.ready(function() {
		BX.message(<?=CUtil::phpToJsObject(Loc::loadLanguageFile(__FILE__))?>);
	})
</script><?php
Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/disk.interface.grid/templates/.default/bitrix/main.interface.grid/.default/style.css');
Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/disk.interface.toolbar/templates/.default/style.css');
Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/disk.folder.list/templates/.default/style.css');

$bodyClasses = 'pagetitle-toolbar-field-view no-hidden no-all-paddings tasks-pagetitle-view';
if ($arResult['GRID_VIEW']['MODE'] === Disk\Internals\Grid\FolderListOptions::VIEW_MODE_TILE)
{
	$bodyClasses .= ' no-background';
	Main\Page\Asset::getInstance()->addJs('/bitrix/components/bitrix/disk.documents/templates/.default/grid-tile-item.js');
}

$title = $arResult['VARIANT'] == Disk\Type\DocumentGridVariant::FlipchartList
	? Loc::getMessage('DISK_DOCUMENTS_PAGE_TITLE_BOARDS')
	: Loc::getMessage('DISK_DOCUMENTS_PAGE_TITLE');

$APPLICATION->setTitle($title);
$bodyClass = $APPLICATION->getPageProperty('BodyClass', false);
$APPLICATION->setPageProperty('BodyClass', trim(sprintf('%s %s', $bodyClass, $bodyClasses)));

include(__DIR__ . '/toolbar.php');
include(__DIR__ . '/buttons.php');
include(__DIR__ . '/grid_views.php');
include(__DIR__ . '/boards-guide.php');

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	array_merge(array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => (
			$arResult['GRID_VIEW']['MODE'] === Disk\Internals\Grid\FolderListOptions::VIEW_MODE_TILE
				? []
				: $arResult['CLASS_COMPONENT']->formatRows($arResult['ITEMS'])
		),

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
		'ALLOW_CONTEXT_MENU' => true,
		'SHOW_ACTION_PANEL' => false,
		'ACTION_PANEL' => null,

		'SHOW_CHECK_ALL_CHECKBOXES' => false,
		'SHOW_ROW_CHECKBOXES' => false,
		'SHOW_ROW_ACTIONS_MENU' => true,
		'SHOW_GRID_SETTINGS_MENU' => true,
		'SHOW_NAVIGATION_PANEL' => true,
		'SHOW_PAGINATION' => true,
		'SHOW_SELECTED_COUNTER' => false,
		'SHOW_TOTAL_COUNTER' => false,
		'SHOW_PAGESIZE' => true,

		'ENABLE_COLLAPSIBLE_ROWS' => false,

		'SHOW_MORE_BUTTON' => true,
		'NAV_OBJECT' => $arResult['NAV_OBJECT'],
		'TOTAL_ROWS_COUNT' => $arResult['ROWS_COUNT'],
		'CURRENT_PAGE' => $arResult['CURRENT_PAGE'],
		'NAV_PARAM_NAME' => $arResult['NAV_OBJECT']->getId(),
		'ENABLE_NEXT_PAGE' => $arResult['ENABLE_NEXT_PAGE'],
		'DEFAULT_PAGE_SIZE' => 50,
		), ( $arResult['GRID_VIEW']['MODE'] === Disk\Internals\Grid\FolderListOptions::VIEW_MODE_TILE
			? [
			'TILE_GRID_MODE' => true,
			'TILE_SIZE' => $arResult['GRID_VIEW']['VIEW_SIZE'],
			'TILE_GRID_ITEMS' => array_map(function ($row) {
				$link = '';
				$actions = [
					[
						'id' => 'loader',
						'text' => Loc::getMessage('DISK_DOCUMENTS_ACT_LOADING')
					]
				];

				return [
					'id' => $row['ID'],
					'name' => $row['NAME'],
					'isFile' => true,
					'link' => $link,
					'isDraggable' => false,
					'isDroppable' => false,
					'formattedSize' => $row['FILE_SIZE'],
					'actions' => $actions,
					'attributes' => $row['ATTRIBUTES']->toDataSet(),
					'image' => null
				];
			}, $arResult['ITEMS']),
			'JS_CLASS_TILE_GRID_ITEM' => 'BX.Disk.TileGrid.Item',
			'JS_TILE_GRID_GENERATOR_EMPTY_BLOCK' => 'BX.Disk.Documents.TileGridEmptyBlockGenerator',
		] : []) + (!$arResult['IS_FILTER_SET'] && empty($arResult['ITEMS']) ? [
			'STUB' =>
			(
				$arResult['VARIANT'] === Disk\Type\DocumentGridVariant::FlipchartList
				?

				'<div class="disk-documents-flipchart-empty-block">'.
					'<div class="disk-documents-flipchart-empty-icon"></div>'.
					'<div class="disk-documents-flipchart-empty-title">'.
						Loc::getMessage('DISK_DOCUMENTS_GRID_TILE_EMPTY_FLIPCHART').'</div>'.
					'<div class="disk-documents-flipchart-emptys-description">'.
						Loc::getMessage('DISK_DOCUMENTS_GRID_DESCRIPTION_EMPTY_FLIPCHART').'</div>'.
				'</div>'
				:
				'<div class="disk-documents-grid-empty-block">'.
					'<div class="ui-icon ui-icon-common-info disk-documents-grid-empty-icon"><i></i></div>'.
					'<div class="main-grid-empty-block-title">'.
						Loc::getMessage('DISK_DOCUMENTS_GRID_STUB_TITLE').'</div>'.
					'<div class="main-grid-empty-block-description">'.
						Loc::getMessage('DISK_DOCUMENTS_GRID_STUB_DESCRIPTION').'</div>'.
				'</div>'
			)
		] : [])
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);
unset($arResult['justCounter']);
?>
	</div>
</div>
