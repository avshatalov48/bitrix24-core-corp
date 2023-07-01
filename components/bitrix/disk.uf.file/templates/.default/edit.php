<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
$this->IncludeLangFile("edit.php");
use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;
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

Main\UI\Extension::load([
	'file_dialog',
	'ajax',
	'dd',
	'core',
	'uploader',
	'canvas',
	'disk_external_loader',
	'ui.tooltip',
	'ui.viewer',
	'disk.document',
	'disk.viewer.actions',
	'ui.progressround',
	'ui.icons',
	'ui.ears',
	'popup',
	'ui.draganddrop.draggable',
	'ui.fonts.opensans',
]);
Main\Page\Asset::getInstance()->addCss('/bitrix/js/disk/css/legacy_uf_common.css');
//Main\Page\Asset::getInstance()->addJs($templateFolder.'/edit.js');
$hideSelectDialog = isset($arParams['PARAMS']['HIDE_SELECT_DIALOG']) ?
	$arParams['PARAMS']['HIDE_SELECT_DIALOG'] == 'Y' : false;

if (empty($arResult['FILES']) || $hideSelectDialog)
{
	?>
	<a href="javascript:void(0);" id="diskuf-selectdialog-<?=$arResult['UID']?>-switcher" class="diskuf-selectdialog-switcher" onclick="BX.onCustomEvent(this.parentNode, 'DiskLoadFormController', ['show']); return false;">
		<span><?=GetMessage("WDUF_UPLOAD_DOCUMENT")?></span>
	</a><?php
}
?>
<div id="diskuf-selectdialog-<?=$arResult['UID']?>" class="disk-file-control" style="display: none;">
	<input type="hidden" name="<?=htmlspecialcharsbx($arResult['controlName'])?>" value="">
	<div class="disk-file-thumb-box" data-bx-role="placeholder"><?$files = array_map(function($file) use ($arResult) {
			?><input name="<?= CUtil::JSEscape($arResult['controlName'])?>" data-bx-role="reserve-item" type="hidden" value="<?=$file["ID"]?>" /><?
			return $file;
		}, $arResult['FILES']);?></div>
	<div class="disk-file-control-panel" data-bx-role="control-panel">
		<div class="disk-file-control-panel-file-wrap" data-bx-role="control-panel-main-actions">
			<input type="file" name="<?=htmlspecialcharsbx($arResult['controlName'])?>" <?=($arParams['arUserField']['MULTIPLE'] == 'Y' ? " multiple='multiple'" : "")?> id="diskuf-input-<?=$arResult['UID']?>" style="display: none;">
			<label for="diskuf-input-<?=$arResult['UID']?>" class="disk-file-control-panel-card-box disk-file-control-panel-card-file">
				<div class="disk-file-control-panel-card disk-file-control-panel-card-icon--upload">
					<div class="disk-file-control-panel-card-content">
						<div class="disk-file-control-panel-card-icon"></div>
						<div class="disk-file-control-panel-card-btn"></div>
						<div class="disk-file-control-panel-card-name"><?=Loc::getMessage('WDUF_UPLOAD')?></div>
					</div>
				</div>
			</label>
			<div class="disk-file-control-panel-card-box disk-file-control-panel-card-file" data-bx-role="file-local-controller">
				<div class="disk-file-control-panel-card disk-file-control-panel-card-icon--b24">
					<div class="disk-file-control-panel-card-content">
						<div class="disk-file-control-panel-card-icon"></div>
						<div class="disk-file-control-panel-card-btn"></div>
						<div class="disk-file-control-panel-card-name"><?=Loc::getMessage('WDUF_MY_DISK')?></div>
					</div>
				</div>
			</div>
			<div class="disk-file-control-panel-card-divider"></div>
			<?php
$handlersManager = \Bitrix\Disk\Driver::getInstance()->getDocumentHandlersManager();
if (array_filter($handlersManager->getHandlersForImport(), function($handler){
	return $handler instanceof \Bitrix\Disk\Document\GoogleHandler;
}))
{
	?>
			<div class="disk-file-control-panel-card-box disk-file-control-panel-card-file" data-bx-role="file-external-controller" data-bx-doc-handler="gdrive">
				<div class="disk-file-control-panel-card disk-file-control-panel-card-icon--google-docs">
					<div class="disk-file-control-panel-card-content">
						<div class="disk-file-control-panel-card-icon"></div>
						<div class="disk-file-control-panel-card-btn"></div>
						<div class="disk-file-control-panel-card-name"><?=Loc::getMessage('DISK_UF_FILE_CLOUD_IMPORT_TITLE_SERVICE_GDRIVE')?></div>
					</div>
				</div>
			</div>
		<?php
}
?>
			<div class="disk-file-control-panel-card-box disk-file-control-panel-card-file" data-bx-role="file-external-controller" data-bx-doc-handler="office365">
				<div class="disk-file-control-panel-card disk-file-control-panel-card-icon--office365">
					<div class="disk-file-control-panel-card-content">
						<div class="disk-file-control-panel-card-icon"></div>
						<div class="disk-file-control-panel-card-btn"></div>
						<div class="disk-file-control-panel-card-name"><?=Loc::getMessage('DISK_UF_FILE_CLOUD_IMPORT_TITLE_SERVICE_OFFICE365')?></div>
					</div>
				</div>
			</div>
			<div class="disk-file-control-panel-card-box disk-file-control-panel-card-file" data-bx-role="file-external-controller" data-bx-doc-handler="dropbox">
				<div class="disk-file-control-panel-card disk-file-control-panel-card-icon--dropbox">
					<div class="disk-file-control-panel-card-content">
						<div class="disk-file-control-panel-card-icon"></div>
						<div class="disk-file-control-panel-card-btn"></div>
						<div class="disk-file-control-panel-card-name"><?=Loc::getMessage('DISK_UF_FILE_CLOUD_IMPORT_TITLE_SERVICE_DROPBOX')?></div>
					</div>
				</div>
			</div>
		</div>
		<div class="disk-file-control-panel-btn-upload-box">
			<label for="diskuf-input-<?=$arResult['UID']?>" class="disk-file-control-panel-btn-upload"><?=Loc::getMessage("WDUF_DND_AREA_TITLE")?></label>
			<div data-bx-role="setting" class="disk-file-control-panel-btn-settings"></div>
		</div>
		<? if(!isset($arParams['PARAMS']['HIDE_CHECKBOX_ALLOW_EDIT']) || $arParams['PARAMS']['HIDE_CHECKBOX_ALLOW_EDIT'] !== 'Y') { ?>
			<input name="<?= $arResult['INPUT_NAME_OBJECT_ALLOW_EDIT'] ?>" style="display: none;" data-bx-role="settings-allow-edit" <?= (empty($arResult['SHARE_EDIT_ON_OBJECT_UF'])? '' : 'checked="checked"') ?> value="1" type="checkbox">
		<? }
		$templateView = $arParams['TEMPLATE_VIEW'];
		if (empty($arParams['arUserField']['ENTITY_VALUE_ID']))
		{
			$settings = \CUserOptions::getOption('disk', 'disk.uf.file');
			if (!is_array($settings))
			{
				$settings = ['template_view' => '.default'];
			}

			if (isset($settings['template_view_for_'.$arParams['arUserField']['ENTITY_ID']]))
			{
				$settings = ['template_view' => $settings['template_view_for_'.$arParams['arUserField']['ENTITY_ID']]];
			}
			elseif (isset($arParams['arUserField']['SETTINGS']['TEMPLATE_VIEW']))
			{
				$settings['template_view'] = $arParams['arUserField']['SETTINGS']['TEMPLATE_VIEW'];
			}
			else if ($arParams['arUserField']['ENTITY_ID'] === 'BLOG_POST') //TODO remove these strings after disk 21.700.0. Only for hotfix 21.600.100
			{
				$settings['template_view'] = 'grid';
			}
			$templateView = $settings['template_view'] ?? null;
		}
		?>
		<input name="<?= $arResult['INPUT_NAME_TEMPLATE_VIEW'] ?>" value="gallery" type="hidden" />
		<input name="<?= $arResult['INPUT_NAME_TEMPLATE_VIEW'] ?>" style="display: none;" <?
			?>data-bx-role="settings-allow-grid" value="grid" type="checkbox" <?
			?>data-bx-save="<?=empty($arParams['arUserField']['ENTITY_VALUE_ID']) ? 'Y' : 'N'?>" <?
			?>data-bx-name="template_view_for_<?=htmlspecialcharsbx($arParams['arUserField']['ENTITY_ID'])?>" <?
			?><?=$templateView === 'grid' ? 'checked' : ''?>/>
	</div>
<?
if (!empty($arResult['CAN_CREATE_FILE_BY_CLOUD']))
{
?>	<div class="disk-file-control-panel" data-bx-role="document-area" style="display: none;">
		<div class="disk-file-control-panel-doc-wrap">
			<div class="disk-file-control-panel-card-box" data-bx-role="handler" data-bx-handler="docx">
				<div class="disk-file-control-panel-card disk-file-control-panel-card--doc">
					<div class="disk-file-control-panel-card-icon"></div>
					<div class="disk-file-control-panel-card-btn"></div>
					<div class="disk-file-control-panel-card-name"><?=GetMessage('WDUF_CREATE_DOCX')?></div>
				</div>
			</div>
			<div class="disk-file-control-panel-card-box" data-bx-role="handler" data-bx-handler="xlsx">
				<div class="disk-file-control-panel-card disk-file-control-panel-card--xls">
					<div class="disk-file-control-panel-card-icon"></div>
					<div class="disk-file-control-panel-card-btn"></div>
					<div class="disk-file-control-panel-card-name"><?=GetMessage('WDUF_CREATE_XLSX')?></div>
				</div>
			</div>
			<div class="disk-file-control-panel-card-box" data-bx-role="handler" data-bx-handler="pptx">
				<div class="disk-file-control-panel-card disk-file-control-panel-card--ppt">
					<div class="disk-file-control-panel-card-icon"></div>
					<div class="disk-file-control-panel-card-btn"></div>
					<div class="disk-file-control-panel-card-name"><?=GetMessage('WDUF_CREATE_PPTX')?></div>
				</div>
			</div>
		</div>
	</div>
<?
}
?>
<script type="text/javascript">
BX.ready(function(){
	BX.message(<?=CUtil::phpToJsObject(Loc::loadLanguageFile(__FILE__))?>);
<? if($arParams['DISABLE_LOCAL_EDIT']){ ?>
	BX.Disk.Document.Local.Instance.disable();
	<? } ?>
	BX.Disk.UF.Options.set({
		urlUpload: '<?= CUtil::JSUrlEscape($arResult['UPLOAD_FILE_URL']) ?>',
		documentHandlers: <?= Main\Web\Json::encode($arResult['DOCUMENT_HANDLERS']) ?>
	});
	new BX.Disk.UF.Form({
			container: BX('diskuf-selectdialog-<?=$arResult['UID']?>'),
			eventObject: BX('diskuf-selectdialog-<?=$arResult['UID']?>').parentNode,
			id: '<?=$arResult['UID']?>',
			fieldName: '<?= CUtil::JSEscape($arResult['controlName'])?>',
			input: BX('diskuf-input-<?=$arResult['UID']?>')<?php
			if (isset($arParams['PARAMS']['PARSER_PARAMS']) && is_array($arParams['PARAMS']['PARSER_PARAMS']))
			{
			?>,
			parserParams: <?= CUtil::PhpToJSObject(array_change_key_case($arParams['PARAMS']['PARSER_PARAMS'], CASE_LOWER))?><?php
			}
			?>
		},
		<?=CUtil::PhpToJSObject($arResult['FILES'])?>
	);
});
BX.message({
	DISK_FOLDER_TOOLBAR_LABEL_LOCAL_BDISK_EDIT: '<?= CUtil::JSEscape(\Bitrix\Disk\Document\LocalDocumentController::getName()) ?>',
	DISK_UF_CONTROLLER_TRANSFORMATION_UPGRADE_POPUP_CONTENT: '<?=CUtil::JSEscape(GetMessage('DISK_UF_CONTROLLER_TRANSFORMATION_UPGRADE_POPUP_CONTENT'));?>',
	DISK_UF_CONTROLLER_TRANSFORMATION_UPGRADE_POPUP_TITLE: '<?=CUtil::JSEscape(GetMessage('DISK_UF_CONTROLLER_TRANSFORMATION_UPGRADE_POPUP_TITLE'));?>',
	DISK_CREATE_BLANK_URL : '<?= CUtil::JSUrlEscape($arResult['CREATE_BLANK_URL']) ?>',
	DISK_RENAME_FILE_URL : '<?= CUtil::JSUrlEscape($arResult['RENAME_FILE_URL']) ?>',
	DISK_THUMB_WIDTH : '<?=\Bitrix\Disk\Uf\Controller::$previewParams["width"]?>',
	DISK_THUMB_HEIGHT : '<?=\Bitrix\Disk\Uf\Controller::$previewParams["height"]?>',
	wd_service_edit_doc_default: "<?= CUtil::JSEscape($arResult['CLOUD_DOCUMENT']['DEFAULT_SERVICE']) ?>"
});
</script>
</div>
<?php
if(\Bitrix\Disk\Integration\Bitrix24Manager::isEnabled() && \Bitrix\Disk\Integration\Bitrix24Manager::isLicensePaid())
{
	\Bitrix\Disk\Integration\Bitrix24Manager::initLicenseInfoPopupJS('disk_transformation_video_limit');
}
