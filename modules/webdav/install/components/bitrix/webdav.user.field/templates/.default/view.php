<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

if (empty($arResult['IMAGES']) && empty($arResult['FILES']))
	return;
WDUFLoadStyle();
?><script>BX.message({'WDUF_FILE_TITLE_REV_HISTORY':'<?=GetMessageJS("WDUF_FILE_TITLE_REV_HISTORY")?>'});</script><?
if (sizeof($arResult['IMAGES']) > 0)
{
?><div class="feed-com-files">
	<div class="feed-com-files-title"><?=GetMessage("WDUF_PHOTO")?></div>
	<div class="feed-com-files-cont"><?
		foreach($arResult['IMAGES'] as $id => $arWDFile)
		{
		?><span class="feed-com-files-photo feed-com-files-photo-load" id="wdif-doc-<?=$arWDFile['ID']?>"<?
			?> style="width:<?=$arWDFile["thumb"]["width"]?>px;height:<?=$arWDFile["thumb"]["height"]?>px;"><?
			?><img onload="this.parentNode.className='feed-com-files-photo';" <?
			?> src="<?=$arWDFile["thumb"]["src"]?>" <?
			?> width="<?=$arWDFile["thumb"]["width"]?>"<?
			?> height="<?=$arWDFile["thumb"]["height"]?>"<?
			?> border="0"<?
			?> alt="<?=htmlspecialcharsbx($arWDFile["NAME"])?>"<?
			?> data-bx-viewer="image"<?
			?> data-bx-title="<?=htmlspecialcharsbx($arWDFile["NAME"])?>"<?
			?> data-bx-src="<?=$arWDFile["basic"]["src"] ?>"<?
			?> data-bx-download="<?=$arWDFile["VIEW"] . '?&ncc=1&force_download=1'?>"<?
			?> data-bx-document="<?=$arWDFile["EDIT"] ?>"<?
			?> data-bx-width="<?=$arWDFile["basic"]["width"]?>"<?
			?> data-bx-height="<?=$arWDFile["basic"]["height"]?>"<?
			if (!empty($arWDFile["original"])) {
				?> data-bx-full="<?=$arWDFile["original"]["src"]?>"<?
				?> data-bx-full-width="<?=$arWDFile["original"]["width"]?>" <?
				?> data-bx-full-height="<?=$arWDFile["original"]["height"]?>"<?
				?> data-bx-full-size="<?=$arWDFile["SIZE"]?>"<? }
			?> /><?
		?></span><?
		}
	?></div>
</div><?
}

if (sizeof($arResult['FILES']) > 0)
{
?>
<script type="text/javascript">
	BX.message({
		'wd_desktop_disk_is_installed': '<?= (bool)CWebDavTools::isDesktopDiskInstall() ?>'
	});
</script>

<div id="wdif-block-<?=$arResult['UID']?>" class="feed-com-files wduf-files-entity">
	<div class="feed-com-files-title"><?= !empty($arResult['IS_HISTORY_DOC'])? GetMessage('WDUF_HISTORY_FILE', array('#NUMBER#' => (empty($arResult['THROUGH_VERSION']) || $arResult['THROUGH_VERSION'] <= 0)? '' : $arResult['THROUGH_VERSION'])) : GetMessage('WDUF_FILES')?></div>
	<div class="feed-com-files-cont"><?
		foreach ($arResult['FILES'] as $id => $arWDFile)
		{
			if(!empty($arResult['allowExtDocServices']) && CWebDavTools::allowPreviewFile($arWDFile["EXTENSION"], $arWDFile["FILE"]['FILE_SIZE']))
			{
				?>
				<div class="feed-com-file-wrap">
					<span class="feed-con-file-icon feed-file-icon-<?=htmlspecialcharsbx($arWDFile['EXTENSION'])?>"></span>
					<span class="feed-com-file-name-wrap">
						<a target="_blank" href="<?=htmlspecialcharsbx($arWDFile['PATH'])?>"<?
							?> id="wdif-doc-<?=$arWDFile['ID'] . (!empty($arResult['HISTORY_DOC']['v'])? '-' . $arResult['HISTORY_DOC']['v'] : '')?>"<?
							?> class="feed-com-file-name" <?
							?> title="<?=htmlspecialcharsbx($arWDFile['NAVCHAIN'])?>" <?
							?> data-bx-viewer="iframe"<?
							?> data-bx-title="<?=htmlspecialcharsbx($arWDFile["NAME"])?>"<?
							?> data-bx-baseElementId="wdif-doc-<?=$arWDFile['ID']?>"<?
							?> data-bx-document="<?=$arWDFile["EDIT"] ?>"<?
								if(!empty($arResult['IS_HISTORY_DOC'])){
									if(CWebDavEditDocGoogle::isEditable($arWDFile['NAME'])){
							?> data-bx-edit="<?=$arWDFile["VIEW"] . '?editIn=' . CWebDavLogOnlineEditBase::DEFAULT_SERVICE_NAME . '&start=1' . (!empty($arResult['HISTORY_DOC']['v'])? '&v=' . $arResult['HISTORY_DOC']['v'] : '&f=' . $arWDFile['FILE']['ID'])?>"<?
									}
							?> data-bx-src="<?=$arWDFile["VIEW"] . '?showInViewer=1' . (!empty($arResult['HISTORY_DOC']['v'])? '&v=' . $arResult['HISTORY_DOC']['v'] : '&f=' . $arWDFile['FILE']['ID'])  ?>"<?
								}
								else {
									if(!empty($arWDFile['EDITABLE']) && CWebDavEditDocGoogle::isEditable($arWDFile['NAME'])){
							?> data-bx-edit="<?=$arWDFile["VIEW"] . '?editIn=' . CWebDavLogOnlineEditBase::DEFAULT_SERVICE_NAME . '&start=1'?>"<?
							?> data-bx-isFromUserLib="<?= (empty($arWDFile['IN_PERSONAL_LIB'])? '' : 1)?>"<?
							?> data-bx-externalId="<?= (empty($arWDFile['EXTERNAL_ID'])? '' : $arWDFile['EXTERNAL_ID'])?>"<?
							?> data-bx-relativePath="<?= (empty($arWDFile['RELATIVE_PATH'])? '' : $arWDFile['RELATIVE_PATH'])?>"<?
									}
									elseif(!empty($arWDFile['EDITABLE'])){
							?> data-bx-isFromUserLib="<?= (empty($arWDFile['IN_PERSONAL_LIB'])? '' : 1)?>"<?
							?> data-bx-externalId="<?= (empty($arWDFile['EXTERNAL_ID'])? '' : $arWDFile['EXTERNAL_ID'])?>"<?
							?> data-bx-relativePath="<?= (empty($arWDFile['RELATIVE_PATH'])? '' : $arWDFile['RELATIVE_PATH'])?>"<?
									}
							?> data-bx-src="<?=$arWDFile["VIEW"] . '?showInViewer=1' ?>"<?
								}
							?> data-bx-askConvert="<?=CWebDavEditDocGoogle::isNeedConvertExtension($arWDFile['NAME'])?>"<?
							?> data-bx-download="<?=$arWDFile["PATH"]?>"<?
							?> data-bx-version="<?=(empty($arResult['IS_HISTORY_DOC'])? 0: $arWDFile['THROUGH_VERSION'])?>"<?
							?> data-bx-idToPost="<?=$arResult['ID_TO_POST']?>"<?
							?> data-bx-urlToPost="<?=htmlspecialcharsbx($arResult['URL_TO_POST'])?>"<?
							?> data-bx-history="<?=$arWDFile["VIEW"] . '?history=1' ?>"<?
							?> data-bx-historyPage="<?=$arWDFile["HISTORY"] ?>"<?
							?> alt="<?=htmlspecialcharsbx($arWDFile['NAME'])?>"<?
						?>><?=htmlspecialcharsbx($arWDFile['NAME'])?><?
						?></a>
						<span class="feed-com-file-size"><?=$arWDFile['SIZE']?></span>
						<script type="text/javascript">
							var WDpreButtons_<?= $arWDFile['ID'] . (!empty($arResult['HISTORY_DOC']['v'])? '_' . $arResult['HISTORY_DOC']['v'] : '') ?> = [
								{text : BX.message('JS_CORE_VIEWER_VIEW_ELEMENT'), className : "bx-viewer-popup-item item-view", href : "#", onclick: function(e){
									BX.fireEvent(BX.findPreviousSibling(BX(this.bindElement), function(node){ return BX.type.isElementNode(node) && (node.getAttribute('data-bx-viewer'));}, true), 'click');
									BX.PopupMenu.currentItem.popupWindow.close();
									return BX.PreventDefault(e);
								}},
								<? if(!empty($arWDFile['EDITABLE']) && CWebDavEditDocGoogle::isEditable($arWDFile['NAME'])){ ?>
								{text : BX.message('JS_CORE_VIEWER_EDIT'), className : "bx-viewer-popup-item item-edit", href : "#", onclick: function(e){
									BX.fireEvent(BX.findPreviousSibling(BX(this.bindElement), function(node){ return BX.type.isElementNode(node) && (node.getAttribute('data-bx-viewer'));}, true), 'click');top.BX.CViewer.objNowInShow.runActionByCurrentElement('forceEdit', {obElementViewer: top.BX.CViewer.objNowInShow});
									BX.PopupMenu.currentItem.popupWindow.close();
									return BX.PreventDefault(e);
								}},
								<? } ?>
								{text : BX.message('JS_CORE_VIEWER_SAVE_TO_OWN_FILES'), className : "bx-viewer-popup-item item-b24", href : "#", onclick: function(e){
									top.BX.CViewer.getWindowCopyToDisk({link: "<?=CUtil::JSUrlEscape(CHTTP::urlAddParams($arWDFile['PATH'], array('toWDController' => 1, 'saveToDisk' => 1)))?>", selfViewer: false, showEdit: <?= (CWebDavEditDocGoogle::isEditable($arWDFile['NAME'])? 'true' : 'false')  ?>, title: "<?= CUtil::JSEscape($arWDFile["NAME"]) ?>"});
									BX.PopupMenu.currentItem.popupWindow.close();
									return BX.PreventDefault(e);
								}},
								{text : BX.message('JS_CORE_VIEWER_DOWNLOAD_TO_PC'), className : "bx-viewer-popup-item item-download", href : "<?=$arWDFile["PATH"]?>", onclick: function(e){BX.PopupMenu.currentItem.popupWindow.close();}},
								<? if(!empty($arWDFile['EDITABLE']) && CWebDavEditDocGoogle::isEditable($arWDFile['NAME'])){ ?>
								{text : BX.message('JS_CORE_VIEWER_HISTORY_ELEMENT'), className : "bx-viewer-popup-item item-history", href : "#", onclick: function(e){BX.PopupMenu.currentItem.popupWindow.close(); showWebdavHistoryPopup('<?= $arWDFile["VIEW"]; ?>?history=1', 'wdif-doc-<?=$arWDFile['ID']?>');return BX.PreventDefault(e);}}
								<? } ?>
							];
						</script>

						<? if(CWebDavEditDocGoogle::isEditable($arWDFile['NAME'])){ ?>
							<script type="text/javascript">
								BX.message({
									'wd_service_edit_doc_default': '<?= CUtil::JSEscape(CWebDavTools::getServiceEditDocForCurrentUser()) ?>',
									'wd_gender_current_user': '<?= CWebDavTools::getUserGenderByCurrentUser(); ?>'
								});
							</script>
						<? } ?>
						<? if(!empty($arWDFile['EDITABLE']) && empty($arResult['IS_HISTORY_DOC']) && CWebDavEditDocGoogle::isEditable($arWDFile['NAME'])){ ?>
							<a class="feed-con-file-changes-link" href="#" onclick="BX.fireEvent(BX.findPreviousSibling(BX(this), function(node){ return BX.type.isElementNode(node) && (node.getAttribute('data-bx-viewer'));}, true), 'click');top.BX.CViewer.objNowInShow.runActionByCurrentElement('forceEdit', {obElementViewer: top.BX.CViewer.objNowInShow}); return false;"><?= GetMessage('WDUF_FILE_EDIT') ?></a>
						<? } ?>
						<span class="feed-con-file-changes-link feed-con-file-changes-link-more" onclick="return WDActionFileMenu('<?= $arWDFile['ID'] . (!empty($arResult['HISTORY_DOC']['v'])? '-' . $arResult['HISTORY_DOC']['v'] : '') ?>', this, WDpreButtons_<?= $arWDFile['ID'] . (!empty($arResult['HISTORY_DOC']['v'])? '_' . $arResult['HISTORY_DOC']['v'] : '') ?>); return false;"><?= GetMessage('WDUF_MORE_ACTIONS') ?></span>
					</span>
				</div>

				<?
			}
			elseif(!empty($arResult['allowExtDocServices']) && CWebDavEditDocGoogle::isEditable($arWDFile['NAME']))
			{
				?>
				<div class="feed-com-file-wrap">
					<span class="feed-con-file-icon feed-file-icon-<?=htmlspecialcharsbx($arWDFile['EXTENSION'])?>"></span>
					<span class="feed-com-file-name-wrap">
						<a target="_blank" href="<?=htmlspecialcharsbx($arWDFile['PATH'])?>"<?
							?> id="wdif-doc-<?=$arWDFile['ID'] . (!empty($arResult['HISTORY_DOC']['v'])? '-' . $arResult['HISTORY_DOC']['v'] : '')?>"<?
							?> class="feed-com-file-name" <?
							?> title="<?=htmlspecialcharsbx($arWDFile['NAVCHAIN'])?>" <?
							?> data-bx-viewer="onlyedit"<?
							?> data-bx-title="<?=htmlspecialcharsbx($arWDFile["NAME"])?>"<?
							?> data-bx-baseElementId="wdif-doc-<?=$arWDFile['ID']?>"<?
							?> data-bx-document="<?=$arWDFile["EDIT"] ?>"<?
								if(!empty($arResult['IS_HISTORY_DOC'])){
							?> data-bx-edit="<?=$arWDFile["VIEW"] . '?editIn=' . CWebDavLogOnlineEditBase::DEFAULT_SERVICE_NAME . '&start=1' . (!empty($arResult['HISTORY_DOC']['v'])? '&v=' . $arResult['HISTORY_DOC']['v'] : '&f=' . $arWDFile['FILE']['ID'])?>"<?
							?> data-bx-src="<?=$arWDFile["VIEW"] . '?showInViewer=1' . (!empty($arResult['HISTORY_DOC']['v'])? '&v=' . $arResult['HISTORY_DOC']['v'] : '&f=' . $arWDFile['FILE']['ID'])  ?>"<?
								}
								else {
									if(!empty($arWDFile['EDITABLE'])){
							?> data-bx-edit="<?=$arWDFile["VIEW"] . '?editIn=' . CWebDavLogOnlineEditBase::DEFAULT_SERVICE_NAME . '&start=1'?>"<?
							?> data-bx-isFromUserLib="<?= (empty($arWDFile['IN_PERSONAL_LIB'])? '' : 1)?>"<?
							?> data-bx-externalId="<?= (empty($arWDFile['EXTERNAL_ID'])? '' : $arWDFile['EXTERNAL_ID'])?>"<?
							?> data-bx-relativePath="<?= (empty($arWDFile['RELATIVE_PATH'])? '' : $arWDFile['RELATIVE_PATH'])?>"<?
									}
							?> data-bx-src="<?=$arWDFile["VIEW"] . '?showInViewer=1' ?>"<?
								}
							?> data-bx-askConvert="<?=CWebDavEditDocGoogle::isNeedConvertExtension($arWDFile['NAME'])?>"<?
							?> data-bx-download="<?=$arWDFile["PATH"]?>"<?
							?> data-bx-version="<?=empty($arResult['IS_HISTORY_DOC'])?0:$arWDFile['THROUGH_VERSION']?>"<?
							?> data-bx-idToPost="<?=$arResult['ID_TO_POST']?>"<?
							?> data-bx-urlToPost="<?=htmlspecialcharsbx($arResult['URL_TO_POST'])?>"<?
							?> data-bx-history="<?=$arWDFile["VIEW"] . '?history=1' ?>"<?
							?> data-bx-historyPage="<?=$arWDFile["HISTORY"] ?>"<?
							?> data-bx-size="<?=htmlspecialcharsbx(CFile::FormatSize($arWDFile["FILE"]['FILE_SIZE']))?>"<?
							?> data-bx-owner="<?=htmlspecialcharsbx($arWDFile["CREATED_BY_FORMATTED"])?>"<?
							?> data-bx-dateModify="<?=htmlspecialcharsbx($arWDFile['FILE']["TIMESTAMP_X"])?>"<?
							?> data-bx-tooBigSizeMsg="1"<?
							?> alt="<?=htmlspecialcharsbx($arWDFile['NAME'])?>"<?
						?>><?=htmlspecialcharsbx($arWDFile['NAME'])?><?
						?></a>
						<span class="feed-com-file-size"><?=$arWDFile['SIZE']?></span>
						<script type="text/javascript">
							var WDpreButtons_<?= $arWDFile['ID'] . (!empty($arResult['HISTORY_DOC']['v'])? '_' . $arResult['HISTORY_DOC']['v'] : '') ?> = [
								{text : BX.message('JS_CORE_VIEWER_VIEW_ELEMENT'), className : "bx-viewer-popup-item item-view", href : "#", onclick: function(e){
									BX.fireEvent(BX.findPreviousSibling(BX(this.bindElement), function(node){ return BX.type.isElementNode(node) && (node.getAttribute('data-bx-viewer'));}, true), 'click');
									BX.PopupMenu.currentItem.popupWindow.close();
									return BX.PreventDefault(e);
								}},
								<? if(!empty($arWDFile['EDITABLE']) && CWebDavEditDocGoogle::isEditable($arWDFile['NAME'])){ ?>
								{text : BX.message('JS_CORE_VIEWER_EDIT'), className : "bx-viewer-popup-item item-edit", href : "#", onclick: function(e){
									BX.fireEvent(BX.findPreviousSibling(BX(this.bindElement), function(node){ return BX.type.isElementNode(node) && (node.getAttribute('data-bx-viewer'));}, true), 'click');top.BX.CViewer.objNowInShow.runActionByCurrentElement('forceEdit', {obElementViewer: top.BX.CViewer.objNowInShow});
									return BX.PreventDefault(e);
								}},
								<? } ?>
								{text : BX.message('JS_CORE_VIEWER_SAVE_TO_OWN_FILES'), className : "bx-viewer-popup-item item-b24", href : "#", onclick: function(e){
									top.BX.CViewer.getWindowCopyToDisk({link: "<?=CUtil::JSUrlEscape(CHTTP::urlAddParams($arWDFile['PATH'], array('toWDController' => 1, 'saveToDisk' => 1)))?>", selfViewer: false, showEdit: <?= (CWebDavEditDocGoogle::isEditable($arWDFile['NAME'])? 'true' : 'false')  ?>, title: "<?= CUtil::JSEscape($arWDFile["NAME"]) ?>"});
									BX.PopupMenu.currentItem.popupWindow.close();
									return BX.PreventDefault(e);
								}},
								{text : BX.message('JS_CORE_VIEWER_DOWNLOAD_TO_PC'), className : "bx-viewer-popup-item item-download", href : "<?=$arWDFile["PATH"]?>", onclick: function(e){BX.PopupMenu.currentItem.popupWindow.close();}},
								<? if(!empty($arWDFile['EDITABLE']) && CWebDavEditDocGoogle::isEditable($arWDFile['NAME'])){ ?>
								{text : BX.message('JS_CORE_VIEWER_HISTORY_ELEMENT'), className : "bx-viewer-popup-item item-history", href : "#", onclick: function(e){BX.PopupMenu.currentItem.popupWindow.close(); showWebdavHistoryPopup('<?= $arWDFile["VIEW"]; ?>?history=1', 'wdif-doc-<?=$arWDFile['ID']?>');return BX.PreventDefault(e);}}
								<? } ?>
							];
						</script>

						<? if(CWebDavEditDocGoogle::isEditable($arWDFile['NAME'])){ ?>
							<script type="text/javascript">
								BX.message({
									'wd_service_edit_doc_default': '<?= CUtil::JSEscape(CWebDavTools::getServiceEditDocForCurrentUser()) ?>',
									'wd_gender_current_user': '<?= CWebDavTools::getUserGenderByCurrentUser(); ?>'
								});
							</script>
						<? } ?>
						<? if(!empty($arWDFile['EDITABLE']) && empty($arResult['IS_HISTORY_DOC'])  && CWebDavEditDocGoogle::isEditable($arWDFile['NAME'])){ ?>
							<a class="feed-con-file-changes-link" href="#" onclick="BX.fireEvent(BX.findPreviousSibling(BX(this), function(node){ return BX.type.isElementNode(node) && (node.getAttribute('data-bx-viewer'));}, true), 'click');top.BX.CViewer.objNowInShow.runActionByCurrentElement('forceEdit', {obElementViewer: top.BX.CViewer.objNowInShow}); return false;"><?= GetMessage('WDUF_FILE_EDIT') ?></a>
						<? } ?>
						<span class="feed-con-file-changes-link feed-con-file-changes-link-more" onclick="return WDActionFileMenu('<?= $arWDFile['ID'] . (!empty($arResult['HISTORY_DOC']['v'])? '-' . $arResult['HISTORY_DOC']['v'] : '') ?>', this, WDpreButtons_<?= $arWDFile['ID'] . (!empty($arResult['HISTORY_DOC']['v'])? '_' . $arResult['HISTORY_DOC']['v'] : '') ?>); return false;"><?= GetMessage('WDUF_MORE_ACTIONS') ?></span>
					</span>
				</div>

				<?
			}
			else
			{
				?>
				<div class="feed-com-file-wrap">
					<span class="feed-con-file-icon feed-file-icon-<?=htmlspecialcharsbx($arWDFile['EXTENSION'])?>"></span>
					<span class="feed-com-file-name-wrap">
						<a target="_blank" href="<?=htmlspecialcharsbx($arWDFile['PATH'])?>"<?
							?>id="wdif-doc-<?=$arWDFile['ID']?>"<?
							?>class="feed-com-file-name" href="#" <?
							?> data-bx-viewer="unknown"<?
							?> data-bx-title="<?=htmlspecialcharsbx($arWDFile["NAME"])?>"<?
							?> data-bx-src="<?=$arWDFile["PATH"]?>"<?
									if(!empty($arWDFile['EDITABLE'])){
							?> data-bx-isFromUserLib="<?= (empty($arWDFile['IN_PERSONAL_LIB'])? '' : 1)?>"<?
							?> data-bx-externalId="<?= (empty($arWDFile['EXTERNAL_ID'])? '' : $arWDFile['EXTERNAL_ID'])?>"<?
							?> data-bx-relativePath="<?= (empty($arWDFile['RELATIVE_PATH'])? '' : $arWDFile['RELATIVE_PATH'])?>"<?
									}
							?> data-bx-download="<?=$arWDFile["PATH"]?>"<?
							?> data-bx-document="<?=$arWDFile["EDIT"] ?>"<?
							?> data-bx-size="<?=htmlspecialcharsbx(CFile::FormatSize($arWDFile["FILE"]['FILE_SIZE']))?>"<?
							?> data-bx-owner="<?=htmlspecialcharsbx($arWDFile["CREATED_BY_FORMATTED"])?>"<?
							?> data-bx-dateModify="<?=htmlspecialcharsbx($arWDFile['FILE']["TIMESTAMP_X"])?>"<?
							?> data-bx-tooBigSizeMsg="<?= !empty($arResult['allowExtDocServices']) && CWebDavTools::allowPreviewFile($arWDFile["EXTENSION"], $arWDFile["FILE"]['FILE_SIZE'], false) ?>"<?
							?> alt="<?=htmlspecialcharsbx($arWDFile['NAME'])?>"<?
						?>><?=htmlspecialcharsbx($arWDFile['NAME'])?><?
						?></a>
						<span class="feed-com-file-size"><?=$arWDFile['SIZE']?></span>
						<script type="text/javascript">
							var WDpreButtons_<?= $arWDFile['ID'] ?> = [
								{text : BX.message('JS_CORE_VIEWER_VIEW_ELEMENT'), className : "bx-viewer-popup-item item-view", href : "#", onclick: function(e){
									BX.fireEvent(BX.findPreviousSibling(BX(this.bindElement), function(node){ return BX.type.isElementNode(node) && (node.getAttribute('data-bx-viewer'));}, true), 'click');
									BX.PopupMenu.currentItem.popupWindow.close();
									return BX.PreventDefault(e);
								}},
								{text : BX.message('JS_CORE_VIEWER_SAVE_TO_OWN_FILES'), className : "bx-viewer-popup-item item-b24", href : "#", onclick: function(e){
									top.BX.CViewer.getWindowCopyToDisk({link: "<?=CUtil::JSUrlEscape(CHTTP::urlAddParams($arWDFile['PATH'], array('toWDController' => 1, 'saveToDisk' => 1)))?>", selfViewer: false, showEdit: <?= (CWebDavEditDocGoogle::isEditable($arWDFile['NAME'])? 'true' : 'false')  ?>, title: "<?= CUtil::JSEscape($arWDFile["NAME"]) ?>"});
									BX.PopupMenu.currentItem.popupWindow.close();
									return BX.PreventDefault(e);
								}},
								{text : BX.message('JS_CORE_VIEWER_DOWNLOAD_TO_PC'), className : "bx-viewer-popup-item item-download", href : "<?=$arWDFile["PATH"]?>", onclick: function(e){BX.PopupMenu.currentItem.popupWindow.close();}}
							];
						</script>
						<span class="feed-con-file-changes-link feed-con-file-changes-link-more" onclick="return WDActionFileMenu('<?= $arWDFile['ID'] ?>', this, WDpreButtons_<?= $arWDFile['ID'] ?>); return false;"><?= GetMessage('WDUF_MORE_ACTIONS') ?></span>
					</span>
				</div>
				<?
			}
		}
		?>
	</div>
</div>
<?
}
?>