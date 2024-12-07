<?php
use Bitrix\Main\Localization\Loc;

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

use Bitrix\Main\UI;

UI\Extension::load("ui.tooltip");

\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/disk/css/legacy_uf_common.css');
include_once(str_replace(array("\\", "//"), "/", __DIR__."/messages.php"));
?>

<?
foreach($arResult['VERSIONS'] as $version)
{
	$title = Loc::getMessage('DISK_UF_VERSION_HISTORY_FILE', array('#NUMBER#' => $version['GLOBAL_CONTENT_VERSION']));
	if($arResult['ONLY_HEAD_VERSION'])
	{
		$title = Loc::getMessage('DISK_UF_HEAD_VERSION_HISTORY_FILE');
	}

	$tooltipUserId = (
		!empty($version['IS_LOCKED'])
			? $version['CREATED_BY']
			: ''
	);
?>
<div id="disk-attach-<?=$version['ID'] . $arResult['UID']?>" class="feed-com-files diskuf-files-entity">
	<div class="feed-com-files-title"><?= $title ?></div>
	<div class="feed-com-files-cont">
		<div class="feed-com-file-wrap">
				<span id="lock-anchor-created-<?= $version['ID'] ?>-<?= $component->getComponentId() ?>" bx-tooltip-user-id="<?=$tooltipUserId?>" class="feed-con-file-icon feed-file-icon-<?=htmlspecialcharsbx($version['EXTENSION'])?> js-disk-locked-document-tooltip">
				<? if($version['IS_LOCKED']) { ?>
					<div class="disk-locked-document-block-icon-small-file"></div>
				<? } ?>
				</span>
				<span class="feed-com-file-name-wrap">
					<a <?= ($version['FROM_EXTERNAL_SYSTEM'] && $version['CAN_UPDATE'])? 'style="color:#d9930a;"' : '' ?> target="_blank" href="<?=htmlspecialcharsbx($version['DOWNLOAD_URL'])?>"<?
						?> id="disk-attach-<?=$version['ID'] . '-' . $version['GLOBAL_CONTENT_VERSION'] ?>"<?
						?> class="feed-com-file-name" <?
						?> title="<?=htmlspecialcharsbx($version['NAME'])?>" <?
						?> data-bx-baseElementId="disk-attach-<?=$version['ID']?>" <?=
							$version['ATTRIBUTES_FOR_VIEWER']
						?> alt="<?=htmlspecialcharsbx($version['NAME'])?>"<?
					?>><?=htmlspecialcharsbx($version['NAME'])?><?
					?></a><?
					?><span class="feed-com-file-size"><?=$version['SIZE']?></span><?
					?><script>
						var WDpreButtons_<?= $version['ID'] . '_' . $version['GLOBAL_CONTENT_VERSION'] ?> = [
							{text : BX.message('JS_CORE_VIEWER_VIEW_ELEMENT'), className : "bx-viewer-popup-item item-view", href : "#", onclick: function(e){
								top.BX.UI.Viewer.Instance.openByNode(BX("disk-attach-<?=$version['ID'] . '-' . $version['GLOBAL_CONTENT_VERSION'] ?>"));
								BX.PopupMenu.currentItem.popupWindow.close();
								return e.preventDefault();
							}},
							<? if($version['EDITABLE'] && $version['CAN_UPDATE'] && (!$version['IS_LOCKED'] || $version['IS_LOCKED_BY_SELF'])){ ?>
							{text : BX.message('JS_CORE_VIEWER_EDIT'), className : "bx-viewer-popup-item item-edit", href : "#", onclick: function(e){
								top.BX.UI.Viewer.Instance.runActionByNode(BX("disk-attach-<?=$version['ID'] . '-' . $version['GLOBAL_CONTENT_VERSION'] ?>"), 'edit', {
								modalWindow: BX.Disk.openBlankDocumentPopup()
							});
								BX.PopupMenu.currentItem.popupWindow.close();
								return e.preventDefault();
							}},
							<? } ?>
							<? if(!$arParams['DISABLE_LOCAL_EDIT']){ ?>
							{text : BX.message('JS_CORE_VIEWER_SAVE_TO_OWN_FILES_MSGVER_1'), className : "bx-viewer-popup-item item-b24", href : "#", onclick: function(e){
								top.BX.UI.Viewer.Instance.runActionByNode(BX("disk-attach-<?=$version['ID'] . '-' . $version['GLOBAL_CONTENT_VERSION'] ?>"), 'copyToMe');
								BX.PopupMenu.currentItem.popupWindow.close();
								return e.preventDefault();
							}},
							<? } ?>
							<? if($version['FROM_EXTERNAL_SYSTEM'] && $version['CAN_UPDATE']){ ?>
							{text : '<?= GetMessageJS('DISK_UF_FILE_RUN_FILE_IMPORT') ?>', className : "bx-viewer-popup-item item-toload", href : "#", onclick: function(e){
								top.BX.Disk.UF.runImport({id: <?= $version['ID'] ?>, name: '<?= CUtil::JSEscape($version['NAME']) ?>'});
								BX.PopupMenu.currentItem.popupWindow.close();
								return e.preventDefault();
							}},
							<? } ?>
							{text : BX.message('JS_CORE_VIEWER_DOWNLOAD_TO_PC'), className : "bx-viewer-popup-item item-download", href : "<?=$version["DOWNLOAD_URL"]?>", onclick: function(e){BX.PopupMenu.currentItem.popupWindow.close();}}
							<? if(!$arParams['DISABLE_LOCAL_EDIT']){ ?>
							,
							{text : '<?= GetMessageJS('DISK_UF_FILE_SETTINGS_DOCS') ?>', className : "bx-viewer-popup-item item-setting", href : "#", onclick: function(e){
								BX.Disk.InformationPopups.openWindowForSelectDocumentService({viewInUf: true});
								BX.PopupMenu.currentItem.popupWindow.close();
								return e.preventDefault();
							}}
							<? } ?>
						];

					</script><?
					?><span class="feed-con-file-changes-link feed-con-file-changes-link-more" onclick="return DiskActionFileMenu('<?= $version['ID'] . '-' . $version['GLOBAL_CONTENT_VERSION'] ?>', this, WDpreButtons_<?= $version['ID'] . '_' . $version['GLOBAL_CONTENT_VERSION'] ?>); return false;"><?= Loc::getMessage('DISK_UF_VERSION_MORE_ACTIONS') ?></span>
				</span>
			</div>
	</div>
</div>
<? } ?>
