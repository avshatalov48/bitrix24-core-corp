<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?><div class="fields boolean" id="main_<?=$arParams["arUserField"]["FIELD_NAME"]?>"><?
foreach ($arResult["VALUE"] as $res): 
?><div class="fields boolean"><?

?><select name="<?=$arParams["arUserField"]["FIELD_NAME"]?>">
	<option value=""<?=($res == ''? ' selected': '')?>> </option>
	<option value="1"<?=($res == '1'? ' selected': '')?>><?=GetMessage("MAIN_YES")?></option>
	<option value="0"<?=($res == '0'? ' selected': '')?>><?=GetMessage("MAIN_NO")?></option>
</select><?


?></div><?
endforeach;
?></div><?