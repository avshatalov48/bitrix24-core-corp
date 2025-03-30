<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @var CBitrixComponent $component */
/** @var CBitrixComponentTemplate $this */
use \Bitrix\Main\Localization\Loc;
use \Bitrix\UI;
use \Bitrix\Disk;

$isBitrix24Template = (SITE_TEMPLATE_ID === 'bitrix24');
$isBitrix24Template && $this->setViewTarget('below_pagetitle');

if(!isset($arResult['HIDE_BUTTONS']) || !$arResult['HIDE_BUTTONS']) {
?>
<div class="disk-documents-control-panel-wrapper">
<div class="disk-documents-control-panel" id="disk-documents-control-panel">
	<div class="disk-documents-control-panel-open">
		<div class="disk-documents-control-panel-label">
			<div class="disk-documents-control-panel-label-item"><?=Loc::getMessage('DISK_DOCUMENTS_TOOLBAR_CREATE')?></div>
		</div>
		<?php
		if (\Bitrix\Main\Config\Option::get('disk', 'boards_enabled', 'N') === 'Y')
		{
		?>
			<div class="disk-documents-control-panel-card-box" onclick="BX.Disk.Documents.Toolbar.createBoard();">
				<div class="disk-documents-control-panel-card disk-documents-control-panel-card--board">
					<div class="disk-documents-control-panel-card-icon"></div>
					<div class="disk-documents-control-panel-card-btn"></div>
					<div class="disk-documents-control-panel-card-name"><?=Loc::getMessage('DISK_DOCUMENTS_TOOLBAR_CREATE_BOARD')?></div>
				</div>
			</div>
		<?php
		}
		?>
		<div class="disk-documents-control-panel-card-box" onclick="BX.Disk.Documents.Toolbar.createDocx();">
			<div class="disk-documents-control-panel-card disk-documents-control-panel-card--doc">
				<div class="disk-documents-control-panel-card-icon"></div>
				<div class="disk-documents-control-panel-card-btn"></div>
				<div class="disk-documents-control-panel-card-name"><?=Loc::getMessage('DISK_DOCUMENTS_TOOLBAR_CREATE_DOC')?></div>
			</div>
		</div>
		<div class="disk-documents-control-panel-card-box" onclick="BX.Disk.Documents.Toolbar.createXlsx();">
			<div class="disk-documents-control-panel-card disk-documents-control-panel-card--xls">
				<div class="disk-documents-control-panel-card-icon"></div>
				<div class="disk-documents-control-panel-card-btn"></div>
				<div class="disk-documents-control-panel-card-name"><?=Loc::getMessage('DISK_DOCUMENTS_TOOLBAR_CREATE_XLS')?></div>
			</div>
		</div>
		<div class="disk-documents-control-panel-card-box" onclick="BX.Disk.Documents.Toolbar.createPptx();">
			<div class="disk-documents-control-panel-card disk-documents-control-panel-card--ppt">
				<div class="disk-documents-control-panel-card-icon"></div>
				<div class="disk-documents-control-panel-card-btn"></div>
				<div class="disk-documents-control-panel-card-name"><?=Loc::getMessage('DISK_DOCUMENTS_TOOLBAR_CREATE_PPT')?></div>
			</div>
		</div>
	</div>
	<div class="disk-documents-control-panel-create">
		<div class="disk-documents-control-panel-label">
			<div class="disk-documents-control-panel-label-item"><?=Loc::getMessage('DISK_DOCUMENTS_TOOLBAR_OPEN')?></div>
		</div>
<?
if ($arResult['STORAGE'])
{
	?>
		<div class="disk-documents-control-panel-card-box">
			<div class="disk-documents-control-panel-card disk-documents-control-panel-card-icon--docs" onmouseover="BX.onCustomEvent(window, 'onDiskUploadPopupShow', [this]);">
				<div class="disk-documents-control-panel-card-icon"></div>
				<div class="disk-documents-control-panel-card-btn"></div>
				<div class="disk-documents-control-panel-card-name"><?=Loc::getMessage('DISK_DOCUMENTS_TOOLBAR_OPEN_LOCAL')?></div>
			</div>
		</div>
	<?
	$APPLICATION->IncludeComponent(
		'bitrix:disk.file.upload',
		'',
		array(
			'STORAGE' => $arResult['STORAGE'],
			'FOLDER' => $arResult['STORAGE']->getRootObject(),
			'CID' => 'DiskDocuments'
		),
		$component,
		array("HIDE_ICONS" => "Y")
	);
}
?>
		<div class="disk-documents-control-panel-card-box disk-documents-control-panel-card-box--inactive">
			<div class="disk-documents-control-panel-card disk-documents-control-panel-card-icon--desktop">
				<div class="disk-documents-control-panel-card-icon"></div>
				<div class="disk-documents-control-panel-card-btn"></div>
				<div class="disk-documents-control-panel-card-name"><?=Loc::getMessage('DISK_DOCUMENTS_TOOLBAR_OPEN_DISK')?></div>
			</div>
		</div>
		<div class="disk-documents-control-panel-card-box disk-documents-control-panel-card-box--inactive">
			<div class="disk-documents-control-panel-card disk-documents-control-panel-card-icon--google-docs">
				<div class="disk-documents-control-panel-card-icon"></div>
				<div class="disk-documents-control-panel-card-btn"></div>
				<div class="disk-documents-control-panel-card-name"><?=Loc::getMessage('DISK_DOCUMENTS_TOOLBAR_OPEN_GOOGLE_DOCS')?></div>
			</div>
		</div>
		<div class="disk-documents-control-panel-card-box disk-documents-control-panel-card-box--inactive">
			<div class="disk-documents-control-panel-card disk-documents-control-panel-card-icon--dropbox">
				<div class="disk-documents-control-panel-card-icon"></div>
				<div class="disk-documents-control-panel-card-btn"></div>
				<div class="disk-documents-control-panel-card-name"><?=Loc::getMessage('DISK_DOCUMENTS_TOOLBAR_OPEN_DROPBOX')?></div>
			</div>
		</div>
		<div class="disk-documents-control-panel-card-box disk-documents-control-panel-card-box--inactive">
			<div class="disk-documents-control-panel-card disk-documents-control-panel-card-icon--office365">
				<div class="disk-documents-control-panel-card-icon"></div>
				<div class="disk-documents-control-panel-card-btn"></div>
				<div class="disk-documents-control-panel-card-name"><?=Loc::getMessage('DISK_DOCUMENTS_TOOLBAR_OPEN_OFFICE_365')?></div>
			</div>
		</div>
	</div>
</div>
</div>
<?
}
$isBitrix24Template && $this->endViewTarget();
