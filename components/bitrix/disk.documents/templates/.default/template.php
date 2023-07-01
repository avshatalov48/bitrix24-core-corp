<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
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
	'disk.viewer.actions',
	'disk.document',
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
$APPLICATION->setTitle(Loc::getMessage('DISK_DOCUMENTS_PAGE_TITLE'));
$bodyClass = $APPLICATION->getPageProperty('BodyClass', false);
$APPLICATION->setPageProperty('BodyClass', trim(sprintf('%s %s', $bodyClass, $bodyClasses)));

include(__DIR__ . '/toolbar.php');
include(__DIR__ . '/buttons.php');
include(__DIR__ . '/grid_views.php');

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	array_merge(array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => ($arResult['GRID_VIEW']['MODE'] === Disk\Internals\Grid\FolderListOptions::VIEW_MODE_TILE ? [] : array_map(
			function($row) use ($arResult, $arParams)
			{
				//region Data for name field
				$urlManager = Disk\Driver::getInstance()->getUrlManager();
				/* @var Disk\File $file */
				$file = $row['object'];

				$nameSpecialChars = htmlspecialcharsbx($row['NAME']);

				$lockedBy = null;
				$inlineStyleLockIcon = 'style="display:none;"';

				if (Disk\Configuration::isEnabledObjectLock() && $file->getLock())
				{
					$lockedBy = $file->getLock()->getCreatedBy();
					$inlineStyleLockIcon = '';
				}
				$iconClass = Disk\Ui\Icon::getIconClassByObject($file, $row['IS_SHARED']);
//endregion
				$arResult['justCounter'] = $arResult['justCounter'] + 1;

				$createdByAvatar = Uri::urnEncode($row['CREATED_BY']['AVATAR']);
				$updatedByAvatar = Uri::urnEncode($row['UPDATED_BY']['AVATAR']);

				return [
					'id' => $row['ID'],
					'data' => [
						'ID' => $row['ID'],
						'NAME' => $row['NAME'],
						'FILE_SIZE' => $row['FILE_SIZE'],
						'CREATED_BY' => $row['CREATED_BY']['ID'],
						'UPDATED_BY' => $row['UPDATED_BY']['ID'],
						'FILE_CONTENT_TYPE' => $file->getExtra()->get('FILE_CONTENT_TYPE'),
					],
					'columnClasses' =>[
						'EXTERNAL_LINK' => 'main-grid-cell-external-link',
					],
					'columns' => array(
						'ID' => $row['ID'],
						'NAME' => <<<HTML
	<table class="bx-disk-object-name"><tr>
		<td style="width: 45px;">
			<div data-object-id="{$row['ID']}" class="bx-file-icon-container-small {$iconClass}">
				<div id="lock-anchor-created-{$row['ID']}" {$inlineStyleLockIcon} class="js-lock-icon js-disk-locked-document-tooltip disk-locked-document-block-icon-small-list disk-locked-document-block-icon-small-folder" data-lock-created-by="{$lockedBy}"></div>
			</div>
		</td>
		<td>
			<span class="bx-disk-folder-title" style='cursor: pointer;' id="disk_obj_{$row['ID']}" {$row['ATTRIBUTES']}>
				{$nameSpecialChars}
			</span>
		</td>
	</tr></table>
HTML
					,
						'FILE_SIZE' => \CFile::formatSize($row['FILE_SIZE']),
						'CREATED_BY' => <<<HTML
<div class="bx-disk-user-link disk-documents-grid-user">
	<span class="bx-disk-fileinfo-owner-avatar disk-documents-grid-user-avatar" style="background-image: url('{$createdByAvatar}');"></span>
	<a class="disk-documents-grid-user-link" target='_blank' href="{$row['CREATED_BY']['URL']}">{$row['CREATED_BY']['NAME']}</a>
</div>
HTML
					,
						'UPDATED_BY' => <<<HTML
<div class="bx-disk-user-link disk-documents-grid-user">
	<span class="bx-disk-fileinfo-owner-avatar disk-documents-grid-user-avatar" style="background-image: url('{$updatedByAvatar}');"></span>
	<a class="disk-documents-grid-user-link" target='_blank' href="{$row['UPDATED_BY']['URL']}">{$row['UPDATED_BY']['NAME']}</a>
</div>
HTML
					,
						'ACTIVITY_TIME' => $row['ACTIVITY_TIME'],
						'CREATE_TIME' => $row['CREATE_TIME'],
						'UPDATE_TIME' => $row['UPDATE_TIME'],
						'SHARED' => <<<HTML
<div class="bx-disk-sharing" id="bx-disk-user-shared-{$row['ID']}">
	<script>BX.ready(function(){
		BX.Disk.Documents.showShared({$row['ID']}, BX('bx-disk-user-shared-{$row['ID']}'));
	});</script>
</div>
HTML
					,
						'EXTERNAL_LINK' => <<<HTML
<div class="bx-disk-external-link" id="bx-disk-external-link-{$row['ID']}">
	<div class=" disk-control-external-link disk-control-external-link-skeleton--active">
		<div class="disk-control-external-link-btn">
			<span class="ui-switcher ui-switcher-off">
				<span class="ui-switcher-cursor"></span>
				<span class="ui-switcher-disabled"></span>
			</span>
		</div>
		<div class="disk-control-external-link-main">
			<div class="disk-control-external-link-skeleton"></div>
		</div>
	</div>
	<script>BX.ready(function(){
		BX.Disk.Documents.showExternalLink({$row['ID']}, BX('bx-disk-external-link-{$row['ID']}'));
	});</script>
</div>
HTML
					,
					),
					'actions' => [[
						'id' => 'loader',
						'html' => '<svg class="disk-documents-circular" viewBox="25 25 50 50">
									   <circle class="disk-documents-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
									   <circle class="disk-documents-inner-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
									</svg>',
					]],
					'attrs' => [],
				];
		}, $arResult['ITEMS'])),

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
				return [
					'id' => $row['ID'],
					'name' => $row['NAME'],
					'isFile' => true,
					'link' => '',
					'isDraggable' => false,
					'isDroppable' => false,
					'formattedSize' => $row['FILE_SIZE'],
					'actions' => [[
						'id' => 'loader',
						'text' => Loc::getMessage('DISK_DOCUMENTS_ACT_LOADING')
					]],
					'attributes' => $row['ATTRIBUTES']->toDataSet(),
					'image' => null
				];
			}, $arResult['ITEMS']),
			'JS_CLASS_TILE_GRID_ITEM' => 'BX.Disk.TileGrid.Item',
			'JS_TILE_GRID_GENERATOR_EMPTY_BLOCK' => 'BX.Disk.Documents.TileGridEmptyBlockGenerator',
		] : []) + (!$arResult['IS_FILTER_SET'] && empty($arResult['ITEMS']) ? [
			'STUB' =>
				'<div class="disk-documents-grid-empty-block">'.
					'<div class="ui-icon ui-icon-common-info disk-documents-grid-empty-icon"><i></i></div>'.
					'<div class="main-grid-empty-block-title">'.
						Loc::getMessage('DISK_DOCUMENTS_GRID_STUB_TITLE').'</div>'.
					'<div class="main-grid-empty-block-description">'.
						Loc::getMessage('DISK_DOCUMENTS_GRID_STUB_DESCRIPTION').'</div>'.
				'</div>'
		] : [])
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);
unset($arResult['justCounter']);
?>
	</div>
</div>
