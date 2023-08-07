<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}
?>
<div class="fields string" id="main_<?=$arParams["arUserField"]["FIELD_NAME"]?>"><?
foreach ($arResult["VALUE"] as $res):
?><div class="fields string"><?
	if(($arParams["arUserField"]["SETTINGS"]["ROWS"] ?? 0) < 2):
?><input type="text" name="<?=$arParams["arUserField"]["FIELD_NAME"]?>" value="<?=$res?>"<?
	if (intval($arParams["arUserField"]["SETTINGS"]["SIZE"] ?? 0) > 0):
		?> size="<?=$arParams["arUserField"]["SETTINGS"]["SIZE"]?>"<?
	endif;
	if (intval($arParams["arUserField"]["SETTINGS"]["MAX_LENGTH"] ?? 0) > 0):
		?> maxlength="<?=$arParams["arUserField"]["SETTINGS"]["MAX_LENGTH"]?>"<?
	endif;
	if (($arParams["arUserField"]["EDIT_IN_LIST"] ?? 'N') != 'Y'):
		?> disabled="disabled"<?
	endif;
?> class="fields string"><?
	else:
?><textarea class="fields string" name="<?=$arParams["arUserField"]["FIELD_NAME"]?>"<?
	?> cols="<?=$arParams["arUserField"]["SETTINGS"]["SIZE"]?>"<?
	?> rows="<?=$arParams["arUserField"]["SETTINGS"]["ROWS"]?>" <?
	if (intval($arParams["arUserField"]["SETTINGS"]["MAX_LENGTH"] ?? 0) > 0):
		?> maxlength="<?=$arParams["arUserField"]["SETTINGS"]["MAX_LENGTH"]?>"<?
	endif;
	if (($arParams["arUserField"]["EDIT_IN_LIST"] ?? 'N') != 'Y'):
		?> disabled="disabled"<?
	endif;
?>><?=$res?></textarea><?
	endif;
?></div><?
endforeach;
?></div>