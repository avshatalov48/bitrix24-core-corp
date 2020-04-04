<? use Bitrix\Main\Localization\Loc;

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

UI\Extension::load([
	'ui.tooltip',
	'ui.viewer',
	'disk.document',
	'disk.viewer.actions',
]);

if (empty($arResult['IMAGES']) && empty($arResult['FILES']) && empty($arResult['DELETED_FILES']))
	return;
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/disk/css/legacy_uf_common.css');

$this->IncludeLangFile("show.php");
include_once(str_replace(array("\\", "//"), "/", __DIR__."/messages.php"));
?><div id="disk-attach-block-<?=$arResult['UID']?>" class="feed-com-files diskuf-files-entity"><?
	if (!empty($arResult['IMAGES']))
	{
		$jsIds = "";

		?><div class="feed-com-files">
			<div class="feed-com-files-title"><?=GetMessage("WDUF_PHOTO")?></div>
			<div class="feed-com-files-cont"><?
				foreach($arResult['IMAGES'] as $id => $file)
				{
					?><span class="feed-com-files-photo feed-com-files-photo-load" id="disk-attach-<?=$file['ID']?>"<?
						?> style="width:<?=$file["THUMB"]["width"]?>px;height:<?=$file["THUMB"]["height"]?>px;"><?

						$id = "disk-attach-image-".$file['ID'];
						if (
							isset($arParams["LAZYLOAD"]) 
							&& $arParams["LAZYLOAD"] == "Y"
						)
						{
							$jsIds .= $jsIds !== "" ? ', "'.$id.'"' : '"'.$id.'"';
						}

						?><img id="<?=$id?>" onload="this.parentNode.className='feed-com-files-photo';" <?
						if (
							isset($arParams["LAZYLOAD"]) 
							&& $arParams["LAZYLOAD"] == "Y"
						)
						{
							?> src="<?=\Bitrix\Disk\Ui\LazyLoad::getBase64Stub()?>" <?
							?> data-thumb-src="<?=$file["THUMB"]["src"] ?>"<?
						}
						else
						{
							?> src="<?=$file["THUMB"]["src"] ?>" <?
						}
						?> width="<?=$file["THUMB"]["width"]?>"<?
						?> height="<?=$file["THUMB"]["height"]?>"<?
						?> border="0"<?
						?> alt="<?=htmlspecialcharsbx($file["NAME"])?>"<?
						?> <?=$file['ATTRIBUTES_FOR_VIEWER']?> <?
						?> bx-attach-file-id="<?=$file['FILE_ID']?>"<?
						if ($file['XML_ID']): ?> bx-attach-xml-id="<?=$file['XML_ID']?>"<?endif;
						if (!empty($file["ORIGINAL"]))
						{
							?> data-bx-full="<?=$file["ORIGINAL"]["src"]?>"<?
							?> data-bx-full-width="<?=$file["ORIGINAL"]["width"]?>" <?
							?> data-bx-full-height="<?=$file["ORIGINAL"]["height"]?>"<?
							?> data-bx-full-size="<?=$file["SIZE"]?>"<?
						}
						?> /><?
					?>
					<? if($file['IS_LOCKED']) { ?>
						<div class='disk-locked-document-block-icon-small-image'></div>
					<? } ?>
					</span><?
				}
			?></div>
		</div><?

		if (strlen($jsIds) > 0)
		{
			?><script>BX.LazyLoad.registerImages([<?=$jsIds?>], null, {dataSrcName: "thumbSrc"});</script><?
		}
	}

	if (!empty($arResult['FILES']) || !empty($arResult['DELETED_FILES']))
	{
		?>
		<div class="feed-com-files">
			<div class="feed-com-files-title"><?=GetMessage('WDUF_FILES')?></div>
			<div class="feed-com-files-cont"><?

		$className = 'feed-com-file-wrap'.(count($arResult['FILES']) >= 5 ? ' feed-com-file-wrap-fullwidth' : '');
		foreach($arResult['FILES'] as $file)
		{
			$tooltipUserId = (
				$file['IS_LOCKED']
					? $file['CREATED_BY']
					: ''
			);
			?><div class="<?=$className?>">
				<span id="lock-anchor-created-<?= $file['ID'] ?>-<?= $component->getComponentId() ?>" bx-tooltip-user-id="<?=$tooltipUserId?>" class="feed-con-file-icon feed-file-icon-<?=htmlspecialcharsbx($file['EXTENSION'])?> js-disk-locked-document-tooltip">
				<? if($file['IS_LOCKED']) { ?>
					<div class="disk-locked-document-block-icon-small-file"></div>
				<? } ?>
				</span>
				<span class="feed-com-file-name-wrap">
					<a <?= ($file['FROM_EXTERNAL_SYSTEM'] && $file['CAN_UPDATE'])? 'style="color:#d9930a;"' : '' ?> target="_blank" href="<?=htmlspecialcharsbx($file['DOWNLOAD_URL'])?>"<?
						?> id="disk-attach-<?=$file['ID']?>"<?
						?> class="feed-com-file-name" <?
						?> title="<?=htmlspecialcharsbx($file['NAME'])?>" <?
						?> bx-attach-file-id="<?=$file['FILE_ID']?>"<?
						if ($file['XML_ID']): ?> bx-attach-xml-id="<?=$file['XML_ID']?>"<?endif;
						if ($file['TYPE_FILE']): ?> bx-attach-file-type="<?=$file['TYPE_FILE']?>"<?endif;
						?> data-bx-baseElementId="disk-attach-<?=$file['ID']?>" <?=
							$file['ATTRIBUTES_FOR_VIEWER']
						?> alt="<?=htmlspecialcharsbx($file['NAME'])?>"<?
					?>><?=htmlspecialcharsbx($file['NAME'])?><?
					?></a><?
					?><span class="feed-com-file-size"><?=$file['SIZE']?></span><?
					?><script type="text/javascript">
						BX.namespace("BX.Disk.Files");
						BX.Disk.Files['<?= $file['ID'] ?>'] = [
							{text : BX.message('JS_CORE_VIEWER_VIEW_ELEMENT'), className : "bx-viewer-popup-item item-view", href : "#", onclick: function(e){
								top.BX.UI.Viewer.Instance.openByNode(BX("disk-attach-<?=$file['ID']?>"));
								BX.PopupMenu.currentItem.popupWindow.close();
								return e.preventDefault();
							}},
							<? if($file['EDITABLE'] && $file['CAN_UPDATE'] && (!$file['IS_LOCKED'] || $file['IS_LOCKED_BY_SELF']) && !$arParams['DISABLE_LOCAL_EDIT']){ ?>
							{text : BX.message('JS_CORE_VIEWER_EDIT'), className : "bx-viewer-popup-item item-edit", href : "#", onclick: function(e){
								top.BX.UI.Viewer.Instance.runActionByNode(BX("disk-attach-<?=$file['ID']?>"), 'edit', {
								modalWindow: BX.Disk.openBlankDocumentPopup()
							});
								BX.PopupMenu.currentItem.popupWindow.close();
								return e.preventDefault();
							}},
							<? } ?>
							<? if(!$arParams['DISABLE_LOCAL_EDIT']){ ?>
							{text : BX.message('JS_CORE_VIEWER_SAVE_TO_OWN_FILES'), className : "bx-viewer-popup-item item-b24", href : "#", onclick: function(e){
								top.BX.UI.Viewer.Instance.runActionByNode(BX("disk-attach-<?=$file['ID']?>"), 'copyToMe');
								BX.PopupMenu.currentItem.popupWindow.close();
								return e.preventDefault();
							}},
							<? } ?>
							<? if($file['FROM_EXTERNAL_SYSTEM'] && $file['CAN_UPDATE'] && (!$file['IS_LOCKED'] || $file['IS_LOCKED_BY_SELF'])){ ?>
							{text : '<?= GetMessageJS('DISK_UF_FILE_RUN_FILE_IMPORT') ?>', className : "bx-viewer-popup-item item-toload", href : "#", onclick: function(e){
								top.BX.Disk.UF.runImport({id: <?= $file['ID'] ?>, name: '<?= CUtil::JSEscape($file['NAME']) ?>'});
								BX.PopupMenu.currentItem.popupWindow.close();
								return e.preventDefault();
							}},
							<? } ?>
							{text : BX.message('JS_CORE_VIEWER_DOWNLOAD_TO_PC'), className : "bx-viewer-popup-item item-download", href : "<?=$file["DOWNLOAD_URL"]?>", onclick: function(e){BX.PopupMenu.currentItem.popupWindow.close();}}
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
					if($file['EDITABLE'] && $file['CAN_UPDATE'] && (!$file['IS_LOCKED'] || $file['IS_LOCKED_BY_SELF']) && !$arParams['DISABLE_LOCAL_EDIT']) {
						?><a class="feed-con-file-changes-link" href="#" onclick="top.BX.UI.Viewer.Instance.runActionByNode(BX('disk-attach-<?=$file['ID']?>'), 'edit', {
							modalWindow: BX.Disk.openBlankDocumentPopup()
						}); return false;"><?= GetMessage('WDUF_FILE_EDIT') ?></a><?
					}
					?><span class="feed-con-file-changes-link feed-con-file-changes-link-more" onclick="return DiskActionFileMenu('<?= $file['ID'] ?>', this, BX.Disk.Files['<?= $file['ID'] ?>']); return false;"><?= GetMessage('WDUF_MORE_ACTIONS') ?></span>
				</span>
			</div><?      
		} 
		foreach($arResult['DELETED_FILES'] as $file)
		{
			?><div class="<?=$className?>">
				<span id="lock-anchor-created-<?= $file['ID'] ?>-<?= $component->getComponentId() ?>" bx-tooltip-user-id="<?=$file['CREATED_BY']?>" class="feed-con-file-icon feed-file-icon-<?=htmlspecialcharsbx($file['EXTENSION'])?>">
				</span>
				<span class="feed-com-file-name-wrap">
					<span <?
						?> id="disk-attach-<?=$file['ID']?>"<?
						?> class="feed-com-file-deleted-name" <?
						?> title="<?=htmlspecialcharsbx($file['NAME'])?>" <?
					?>><?=htmlspecialcharsbx($file['NAME'])?><?
					?></span><?
					?><span class="feed-com-file-size"><?=$file['SIZE']?></span>
					<span class="feed-con-file-text-notice" href="#"><?= GetMessage('DISK_UF_FILE_IS_DELETED') ?></span><?
					if($file['CAN_RESTORE'] && $file['TRASHCAN_URL']) {
						?><a class="feed-con-file-changes-link" href="<?= $file['TRASHCAN_URL'] ?>"><?= GetMessage('DISK_UF_FILE_RESTORE') ?></a><?
					} ?>
				</span>
			</div><?
		}
		?></div>
		</div>
		<?
	}

	if(
		$arResult['ENABLED_MOD_ZIP'] &&
		!empty($arResult['ATTACHED_IDS']) &&
		count($arResult['ATTACHED_IDS']) > 1
	)
	{
		?>
		<div class="disk-uf-file-download-archive">
			<a href="<?=$arResult['DOWNLOAD_ARCHIVE_URL']?>" class="disk-uf-file-download-archive-text"><?=GetMessage('WDUF_FILE_DOWNLOAD_ARCHIVE')?></a>
			<span class="disk-uf-file-download-archive-size">(<?=CFile::FormatSize($arResult['COMMON_SIZE'])?>)</span>
		</div>
		<?
	}
?>
</div>
<? if($arParams['DISABLE_LOCAL_EDIT']){ ?>
<script type="text/javascript">
	BX.Disk.Document.Local.Instance.disable();
	if(!BX.message('disk_document_service'))
	{
		BX.message({disk_document_service: 'g'});
	}
</script>
<? } ?>