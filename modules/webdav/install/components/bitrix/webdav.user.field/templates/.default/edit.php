<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

CJSCore::Init(array('wdfiledialog', 'ajax', 'dd'));
//$APPLICATION->AddHeadScript("/bitrix/js/webdav/selectfiledialog.js");
CJSCore::Init(array("core", "ajax", "uploader", "canvas"));
$addClass = ((strpos($_SERVER['HTTP_USER_AGENT'], 'Mac OS') !== false) ? 'wduf-filemacos' : '');
$mess = GetMessage('WD_FILE_LOADING');
$thumb = <<<HTML
<td class="files-name">
	<span class="files-text">
		<span class="f-wrap">
			#name#
			<span class="wd-files-icon feed-file-icon-#ext#"></span>
		</span>
	</span>
</td>
<td class="files-size">#size#</td>
<td class="files-storage">
	<span>{$mess}</span>
	<span class="feed-add-post-loading-wrap">
		<span class="feed-add-post-loading">
			<span class="feed-add-post-loading-cancel del-but" id="wdu#id#TerminateButton"></span>
		</span>
		<span class="feed-add-post-load-indicator" id="wdu#id#Progressbar" style="width:5%;">
			<span class="feed-add-post-load-number" id="wdu#id#ProgressbarText">5%</span>
		</span>
	</span>
</td>
HTML;
$previewImg = <<<HTML
<span class="wd-files-icon files-preview-wrap">
	<span class="files-preview-border">
		<span class="files-preview-alignment">
			<img class="files-preview" src="#url_preview#" data-bx-width="#width#" data-bx-height="#height#" data-bx-document="#url_get#" />
		</span>
	</span>
</span>
HTML;
$thumb = preg_replace("/[\n\t]+/", "", $thumb);
$previewImg =  preg_replace("/[\n\t]+/", "", $previewImg);

$sValues = '[]';
$arValue = $arParams['PARAMS']['arUserField']['VALUE'];

$path = str_replace(array("\\", "//"), "/", dirname(__FILE__)."/functions.php");
include_once($path);
?>
<script type="text/javascript">
	BX.message({
		'wd_service_edit_doc_default': '<?= CUtil::JSEscape(CWebDavTools::getServiceEditDocForCurrentUser()) ?>',
		WD_FILE_LOADING : "<?=GetMessageJS('WD_FILE_LOADING')?>",
		WD_FILE_EXISTS : "<?=GetMessage('WD_FILE_EXISTS')?>",
		WD_FILE_UPLOAD_ERROR : '<?=GetMessageJS('WD_FILE_UPLOAD_ERROR')?>',
		WD_ACCESS_DENIED : "<?=GetMessageJS('WD_ACCESS_DENIED')?>",
		'selectfiledialog.js' : '<?=CUtil::GetAdditionalFileURL('/bitrix/js/webdav/selectfiledialog.js')?>',
		WD_TMPLT_THUMB : '<?=CUtil::JSEscape($thumb)?>',
		WD_TMPLT_PREVIEW_IMG : '<?=CUtil::JSEscape($previewImg)?>'
	});
</script>
<?

if (is_array($arValue) && !empty($arValue))
{
	if (!(count($arValue) == 1 && array_key_exists(0, $arValue) && empty($arValue[0])))
		$sValues = 'BX.findChildren(BX("wduf-selectdialog-'.$arResult['UID'].'"), {"className" : "wd-inline-file"}, true)';
}
?>
		<?if(empty($arResult['ELEMENTS']))
		{
			?><a href="javascript:void(0);" <?
				?>id="wduf-selectdialogswitcher-<?=$arResult['UID']?>" <?
				?>class="wduf-selectdialog-switcher" <?
				?>onclick="BX.onCustomEvent(this.parentNode, 'WDLoadFormController')"><?
					?><span><?=GetMessage("WDUF_UPLOAD_DOCUMENT")?></span></a><?
		}?>
		<div id="wduf-selectdialog-<?=$arResult['UID']?>" class="wduf-selectdialog">
			<div class="wduf-files-block"<?if(!empty($arResult['ELEMENTS'])){?> style="display:block;"<?}?>>
				<div class="wduf-label">
					<?=GetMessage("WDUF_ATTACHMENTS")?>
					<span class="wduf-label-icon"></span>
				</div>
				<div class="wduf-placeholder">
					<table cellspacing="0" class="files-list">
						<tbody class="wduf-placeholder-tbody">
<?
	if (
		isset($arResult['ELEMENTS'])
		&& !empty($arResult['ELEMENTS'])
	)
	{
		foreach ($arResult['ELEMENTS'] as $arElement) {
?>
							<tr class="wd-inline-file" id="wd-doc<?=intval($arElement['ID'])?>">
								<td class="files-name">
									<span class="files-text">
										<span class="f-wrap"><?=htmlspecialcharsEx($arElement['NAME'])?></span>
<?		if ($arElement['URL_PREVIEW'] != '') { ?>
										<span class="wd-files-icon files-preview-wrap">
											<span class="files-preview-border">
												<span class="files-preview-alignment">
													<img class="files-preview" src="<?=$arElement['URL_PREVIEW']?>" <?
														?> data-bx-width="<?=$arElement['FILE']['WIDTH']?>"<?
														?> data-bx-height="<?=$arElement['FILE']['HEIGHT']?>"<?
														?> data-bx-document="<?=$arElement['URL_GET']?>"<?
													?> />
												</span>
											</span>
										</span>
<?		} else { ?>
										<span class="wd-files-icon feed-file-icon-<?=GetFileExtension($arElement['NAME'])?>"></span>
<?		}?>
<?		if ($arElement['URL_DELETE_DROPPED'] !== '') { ?>
										<a class="file-edit" href="<?=$arElement['URL_DELETE_DROPPED']?>">edit</a>
<?		} ?>
									</span>
								</td>
								<td class="files-size"><?=$arElement["FILE_SIZE"]?></td>
								<td class="files-storage">
									<div class="files-storage-block">
<?		if ($arElement['DROPPED']) { ?>
										<span class="files-storage-text">
											<?=GetMessage("WD_SAVED_PATH")?>:
										</span>
										<a class="files-path" href="javascript:void(0);"><?=htmlspecialcharsEx($arElement['TITLE'])?></a>
										<span class="edit-stor"></span>
<?		} else { ?>
										<span class="files-placement"><?=htmlspecialcharsEx($arElement['TITLE'])?></span>
<?		} ?>
										<input id="wduf-doc<?=$arElement['ID']?>" type="hidden" name="<?=htmlspecialcharsbx($arResult['controlName'])?>" value="<?=$arElement['ID']?>" />
									</div>
								</td>
							</tr>
<?
		} // foreach
	} // if
?>
						</tbody>
					</table>
				<? if($arResult['showCheckboxToAllowEdit']){ ?>
				<div class="feed-add-post-files-activity">
					<div class="feed-add-post-files-activity-item">
						<input type="hidden" value="0" name="<?= $arResult['ufToSaveAllowEdit']['FIELD']?>">
						<input name="<?= $arResult['ufToSaveAllowEdit']['FIELD'] ?>" value="1" type="checkbox" <?=($arResult['ufToSaveAllowEdit']['VALUE'] ? "checked=\"checked\"": "")?> id="wduf-edit-rigths-doc" class="feed-add-post-files-activity-checkbox"><label class="feed-add-post-files-activity-label" for="wduf-edit-rigths-doc"><?= GetMessage('WDUF_FILE_EDIT_BY_DESTINATION_USERS'); ?></label>
					</div>
				</div>
				<? } ?>
				</div>
			</div>
			<div class="wduf-extended">
				<input type="hidden" name="<?=htmlspecialcharsbx($arResult['controlName'])?>" value="" />
				<?= WDRenderTable(!empty($arResult['allowCreateDocByExtServices']), $addClass); ?>
			</div>
			<div class="wduf-simple">
				<?= WDRenderTable(!empty($arResult['allowCreateDocByExtServices']), $addClass); ?>
			</div>
<script type="text/javascript">
<?
$userPath = COption::GetOptionString('intranet', 'path_user', '/company/personal/user/#USER_ID#/', SITE_ID);
if (!empty($userPath))
{
	$userPath = str_replace("#USER_ID#", $GLOBALS['USER']->GetID(), $userPath);
	$arPath['PATH_TO_USER'] = $userPath;
	$arPath['PATH_TO_FILES'] = $userPath.'files/lib/';
	$arPath['ELEMENT_UPLOAD_URL'] = $userPath.'files/element/upload/0/';
	$arPath['ELEMENT_SHOW_INLINE_URL'] = $userPath.'files/element/edit/#element_id#/VIEW/';
	$arPath['ELEMENT_HISTORYGET_URL'] = $userPath.'files/element/historyget/#element_id#/#element_name#';
}

$showUrl = (isset($arPath['ELEMENT_SHOW_INLINE_URL']) ? $arPath['ELEMENT_SHOW_INLINE_URL'] : "");
if (isset($arParams["THUMB_SIZE"]))
	$showUrl .= (strpos($showUrl, "?") === false ? "?" : "&")."size=100";
?>
BX.ready(function(){
	BX.WebdavUpload.add({
		JSON : <?=CUtil::PhpToJSObject($arResult['JSON'])?>,
		THUMB_SIZE : '<?=$arParams["THUMB_SIZE"]?>',
		UID : '<?=$arResult['UID']?>',
		controlName : '<?= CUtil::JSEscape($arResult['controlName'])?>',
		values	:  <?=$sValues?>,
		urlSelect : "<?=CUtil::JSEscape((isset($arPath['PATH_TO_FILES']) ? $arPath['PATH_TO_FILES'] : ''))?>",
		urlUpload : "<?=CUtil::JSEscape(isset($arPath['ELEMENT_UPLOAD_URL']) ? $arPath['ELEMENT_UPLOAD_URL'] : "")?>",
		urlShow	: "<?=CUtil::JSEscape($showUrl)?>",
		urlGet	: "<?=CUtil::JSEscape(isset($arPath["ELEMENT_HISTORYGET_URL"]) ? $arPath["ELEMENT_HISTORYGET_URL"] : "")?>"
	});
});
</script>
		</div>
