<?if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();
$APPLICATION->AddHeadScript("/bitrix/components/bitrix/mobile.file.upload/templates/.default/script_attached.js");

$controlName = $arParams['INPUT_NAME'];
$controlNameFull = $controlName . (($arParams['MULTIPLE'] == 'Y') ? '[]' : '');

?><script>
	BX.message({
		MFUControlNameFull: '<?=CUtil::JSEscape($controlNameFull)?>',
		MFULoadingTitle1: '<?=CUtil::JSEscape(GetMessage("MFU_LOADING_TITLE_1"))?>',
		MFULoadingTitle2: '<?=CUtil::JSEscape(GetMessage("MFU_LOADING_TITLE_2"))?>'
	});
</script>
<span id="mfu_file_container"><?

	$varKey = (
		isset($arParams["POST_ID"]) 
		&& intval($arParams["POST_ID"]) > 0 
			? "MFU_UPLOADED_IMAGES_".$GLOBALS["USER"]->GetId()."_".intval($arParams["POST_ID"]) 
			: "MFU_UPLOADED_IMAGES_".$GLOBALS["USER"]->GetId()
	);
	if (is_array($_SESSION[$varKey]) && count($_SESSION[$varKey]))
	{
		foreach($_SESSION[$varKey] as $file_id)
		{
			?><input type="hidden" id="mfu_file_id_<?=$file_id?>" name="<?=$controlNameFull?>" value="<?=$file_id?>" /><?
		}
	}

	$varKey = (
		isset($arParams["POST_ID"]) 
		&& intval($arParams["POST_ID"]) > 0 
			? "MFU_UPLOADED_FILES_".$GLOBALS["USER"]->GetId()."_".intval($arParams["POST_ID"]) 
			: "MFU_UPLOADED_FILES_".$GLOBALS["USER"]->GetId()
	);
	if (is_array($_SESSION[$varKey]) && count($_SESSION[$varKey]))
	{
		foreach($_SESSION[$varKey] as $file_id)
		{
			?><input type="hidden" id="mfu_file_id_<?=$file_id?>" name="<?=$controlNameFull?>" value="<?=$file_id?>" /><?
		}
	}
	else
	{
		$varKey = (
			isset($arParams["POST_ID"]) 
			&& intval($arParams["POST_ID"]) > 0 
				? "MFU_UPLOADED_DOCS_".$GLOBALS["USER"]->GetId()."_".intval($arParams["POST_ID"]) 
				: "MFU_UPLOADED_DOCS_".$GLOBALS["USER"]->GetId()
		);
		if (
			$arResult["diskEnabled"]
			&& is_array($arResult["arAttachedObject"]) 
			&& !empty($arResult["arAttachedObject"])
		)
		{
			foreach($arResult["arAttachedObject"] as $arVal)
			{
				?><input type="hidden" id="mfu_disk_id_<?=$arVal["DISK_FILE_ID"]?>" name="<?=$controlNameFull?>" value="<?=$arVal["ID"]?>" /><?
			}
		}
		elseif (is_array($_SESSION[$varKey]) && count($_SESSION[$varKey]))
		{
			foreach($_SESSION[$varKey] as $element_id)
			{
				if ($arResult["diskEnabled"])
				{
					?><input type="hidden" id="mfu_disk_id_<?=$element_id?>" name="<?=$controlNameFull?>" value="<?=(!isset($arParams["POST_ID"]) || intval($arParams["POST_ID"]) <= 0 ? "n" : "").$element_id?>" /><?
				}
				else
				{
					?><input type="hidden" id="mfu_element_id_<?=$element_id?>" name="<?=$controlNameFull?>" value="<?=$element_id?>" /><?
				}
			}
		}
	}	
?></span>

