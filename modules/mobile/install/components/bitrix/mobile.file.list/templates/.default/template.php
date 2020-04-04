<?if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();
$APPLICATION->AddHeadScript("/bitrix/components/bitrix/mobile.file.list/templates/.default/script_attached.js");

if (
	is_array($arResult["FILES"])
	|| is_array($arResult["ELEMENTS"])
)
{
	?><script>
	BX.message({
		MFUDeleteConfirmTitle: '<?=CUtil::JSEscape(GetMessage("MOBILE_MFU_CONFIRM_TITLE"))?>',
		MFUDeleteConfirmMessage: '<?=CUtil::JSEscape(GetMessage("MOBILE_MFU_CONFIRM_MESSAGE"))?>',
		MFUDeleteConfirmYes: '<?=CUtil::JSEscape(GetMessage("MOBILE_MFU_CONFIRM_YES"))?>',
		MFUDeleteConfirmNo: '<?=CUtil::JSEscape(GetMessage("MOBILE_MFU_CONFIRM_NO"))?>',
		MFUSiteDir: '<?=CUtil::JSEscape(SITE_DIR)?>',
		MFUSessID: '<?=CUtil::JSEscape(bitrix_sessid())?>',
		MFUPostID: <?=(isset($arParams["POST_ID"]) && intval($arParams["POST_ID"]) > 0 ? intval($arParams["POST_ID"]) : 0)?>
	});
	</script><?
	
	if (
		(is_array($arResult["FILES"]) && count($arResult["FILES"]) > 0)
		|| (is_array($arResult["ELEMENTS"]) && count($arResult["ELEMENTS"]) > 0)
	)	
	{
		?><div id="fl-wrapper" class="fl-wrapper"><?
			foreach($arResult["FILES"] as $arFile)
			{
				?><div class="fl-block" id="mfl_item_<?=$arFile["id"]?>"><?
					?><div class="fl-delete-btn"><i data-removable-icon="true" class="fl-delete-minus"></i></div><?
					?><div class="avatar fl-avatar" style="background:<?=(strlen($arFile["src"]) > 0 ? "url('".$arFile["src"]."')" : "")?>; background-size:50px 50px;"></div><?
					?><div class="fl-delete-right-btn-wrap" data-removable-btn="true"><?
						?><?=GetMessage("MOBILE_MFU_DELETE")?><?
						?><div class="fl-delete-right-btn-block"><div class="fl-delete-right-btn"><?=GetMessage("MOBILE_MFU_DELETE")?></div></div><?
					?></div><?
					?><div class="fl-title"><?=$arFile["name"]?>&nbsp;</div>
				</div><?
			}

			foreach($arResult["ELEMENTS"] as $arElement)
			{
				?><div class="fl-block" id="mfl_element_<?=$arElement["id"]?>"><?
					?><div class="fl-delete-btn"><i data-removable-icon="true" class="fl-delete-minus"></i></div><?
					?><div class="avatar fl-avatar" style="background:<?=(strlen($arElement["src"]) > 0 ? "url('".$arElement["src"]."')" : "")?>; background-size:50px 50px;"></div><?
					?><div class="fl-delete-right-btn-wrap" data-removable-btn="true"><?
						?><?=GetMessage("MOBILE_MFU_DELETE")?><?
						?><div class="fl-delete-right-btn-block"><div class="fl-delete-right-btn"><?=GetMessage("MOBILE_MFU_DELETE")?></div></div><?
					?></div><?
					?><div class="fl-title"><?=$arElement["name"]?>&nbsp;</div>
				</div><?
			}
		?></div><?
	}
}
?>