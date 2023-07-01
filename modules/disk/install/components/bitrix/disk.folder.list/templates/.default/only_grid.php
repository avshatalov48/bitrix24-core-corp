<?php
use Bitrix\Disk\Internals\Grid\FolderListOptions;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
?>
<div class="bx-disk-interface-filelist <?= $arResult['IS_RUNNING_FILTER']? 'disk-running-filter' : '' ?>">
	<?
	$generateEmptyBlock = 'function() {
			return BX.create("div", {
				props: {
					className: "disk-folder-list-no-data-inner"
				},
				children: [
					BX.create("div", {
						props: {
							className: "disk-folder-list-no-data-inner-message"
						},
						text: BX.message("DISK_FOLDER_LIST_EMPTY_BLOCK_MESSAGE")
					}),
					BX.create("div", {
						props: {
							className: "disk-folder-list-no-data-inner-variable"
						},
						children: [
							BX.create("div", {
								props: {
									id: "disk-folder-list-no-data-upload-file",								
									className: "disk-folder-list-no-data-inner-create-file"
								},
								text: BX.message("DISK_FOLDER_LIST_EMPTY_BLOCK_CREATE_FILE"),
								events: {
									mouseenter: function(){
										var button = BX("disk-folder-list-no-data-upload-file");
										if(button && !BX.hasClass(button, "disk-folder-list-no-data-disabled"))
										{
											BX.onCustomEvent(window, "onDiskUploadPopupShow", [BX("disk-folder-list-no-data-upload-file")]);
										}
									},
									mouseleave: function(){
										BX.onCustomEvent(window, "onDiskUploadPopupClose", [BX("disk-folder-list-no-data-upload-file")]);
									}
								}
							}),
							BX.create("div", {
								props: {
									id: "disk-folder-list-no-data-create-folder",
									className: "disk-folder-list-no-data-inner-create-folder"
								},
								text: BX.message("DISK_FOLDER_LIST_EMPTY_BLOCK_CREATE_FOLDER"),
								events: {
									click: function(){
										var button = BX("disk-folder-list-no-data-create-folder");
										if(button && !BX.hasClass(button, "disk-folder-list-no-data-disabled"))
										{
											BX.Disk["FolderListClass_' . $component->getComponentId() . '"].createFolder();
										}										
									}
								}
							})
						]
					})
				]
			})
		}'
	;

	$APPLICATION->IncludeComponent(
		'bitrix:main.ui.grid',
		'',
		array(
			'TOP_ACTION_PANEL_RENDER_TO' => '#disk-folder-list-toolbar',
			'TILE_GRID_MODE' => $arResult['GRID']['MODE'] === FolderListOptions::VIEW_MODE_TILE,
			'TILE_SIZE' => $arResult['GRID']['VIEW_SIZE'],
			'TILE_GRID_ITEMS' => $arResult['TILE_ITEMS'] ?? null,
			'JS_CLASS_TILE_GRID_ITEM' => 'BX.Disk.TileGrid.Item',
			'JS_TILE_GRID_GENERATOR_EMPTY_BLOCK' => (empty($arResult['IS_TRASH_MODE']) && !$arResult['STORAGE']['BLOCK_ADD_BUTTONS'])? $generateEmptyBlock : null,
			'MODE' => $arResult['GRID']['MODE'],
			'GRID_ID' => $arResult['GRID']['ID'],
			'HEADERS' => $arResult['GRID']['HEADERS'],
			'SORT' => $arResult['GRID']['SORT'],
			'SORT_VARS' => $arResult['GRID']['SORT_VARS'],
			'ROWS' => $arResult['GRID']['ROWS'],

			'AJAX_MODE' => 'Y',
			'AJAX_OPTION_JUMP' => 'N',
			'AJAX_OPTION_STYLE' => 'Y',
			'AJAX_OPTION_HISTORY' => 'N',

			'SHOW_CHECK_ALL_CHECKBOXES' => true,
			'SHOW_ROW_CHECKBOXES' => true,
			'SHOW_ROW_ACTIONS_MENU' => true,
			'SHOW_GRID_SETTINGS_MENU' => true,
			'SHOW_NAVIGATION_PANEL' => true,
			'SHOW_PAGINATION' => true,
			'SHOW_SELECTED_COUNTER' => true,
			'SHOW_TOTAL_COUNTER' => false,
			'SHOW_PAGESIZE' => true,
			'SHOW_ACTION_PANEL' => false,
			'ALLOW_CONTEXT_MENU' => true,

			'ACTION_PANEL' => $arResult['GRID']['ACTION_PANEL'],
			'NAV_OBJECT' => $arResult['GRID']['NAV_OBJECT'],
			'~NAV_PARAMS' => array(
				'SHOW_COUNT' => 'N',
				'SHOW_ALWAYS' => false,
			),

			'EDITABLE' => !$arResult['GRID']['ONLY_READ_ACTIONS'],
			'ALLOW_EDIT' => true,
		),
		$component
	);
	?>
</div>