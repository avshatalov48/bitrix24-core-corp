<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<div class="fields integer" id="main_<?=$arParams["arUserField"]["FIELD_NAME"]?>"><?
foreach ($arResult["VALUE"] as $res):
?><div class="fields integer">
<input type="text" name="<?=$arParams["arUserField"]["FIELD_NAME"]?>_from" value="<?=$res[0]?>" size="8" <?	
	if ($arParams["arUserField"]["EDIT_IN_LIST"]!="Y"):
		?> disabled="disabled"<?
	endif;
?> class="fields integer" /> ... 
<input type="text" name="<?=$arParams["arUserField"]["FIELD_NAME"]?>_to" value="<?=$res[1]?>" size="8" <?		
	if ($arParams["arUserField"]["EDIT_IN_LIST"]!="Y"):
		?> disabled="disabled"<?
	endif;
?> class="fields integer" /></div><?
endforeach;?>
</div>
