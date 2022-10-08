<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Disk;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Disk\Internals\Grid\FolderListOptions;

$gridOptions = new Main\Grid\Options($arResult['GRID_ID']);

$byColumn = key($arResult['SORT']);
$direction = reset($arResult['SORT']);
$inverseDirection = mb_strtolower($direction) == 'desc'? 'asc' : 'desc';
$isTile = $arResult['GRID_VIEW']['MODE'] === 'tile';
$isBigTile = $arResult['GRID_VIEW']['VIEW_SIZE'] === 'xl';

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');

$isBitrix24Template = (SITE_TEMPLATE_ID === 'bitrix24');

$isBitrix24Template && $this->setViewTarget('below_pagetitle');
?>
	<div class="disk-documents-toolbar" id="disk-folder-list-toolbar">
		<div class="disk-documents-toolbar-item">
			<div class="disk-documents-toolbar-switcher">
				<a class="disk-documents-toolbar-switcher-item<?=$isTile ? '' : ' --active'?>" href="" onclick="BX.Disk.Documents.Options.setViewList()"><?=Loc::getMessage('DISK_DOCUMENTS_GRID_VIEW_LIST')?></a>
				<a class="disk-documents-toolbar-switcher-item<?=$isTile && !$isBigTile? ' --active' : ''?>" href="" onclick="BX.Disk.Documents.Options.setViewSmallTile()"><?=Loc::getMessage('DISK_DOCUMENTS_GRID_VIEW_SMALL_TILE')?></a>
				<a class="disk-documents-toolbar-switcher-item<?=$isTile && $isBigTile? ' --active' : ''?>" href="" onclick="BX.Disk.Documents.Options.setViewBigTile()"><?=Loc::getMessage('DISK_DOCUMENTS_GRID_VIEW_TILE')?></a>
			</div>
		</div>
	</div>
<?
$isBitrix24Template && $this->endViewTarget();
