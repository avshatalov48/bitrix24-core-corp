<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$bWasSelect = false;
$sFieldName = $arParams["arUserField"]["FIELD_NAME"];
$bMultilple = $arParams["arUserField"]["MULTIPLE"] == "Y";
if ($bMultilple)
	$sFieldName .= '[]'; 

$listLength = isset($arParams["arUserField"]["SETTINGS"]["LIST_HEIGHT"]) ? intval($arParams["arUserField"]["SETTINGS"]["LIST_HEIGHT"]) : 1;
if($listLength <= 0)
	$listLength = 1;

if($arParams["arUserField"]["SETTINGS"]["DISPLAY"]!="CHECKBOX"):
	?><select name="<?=$sFieldName?>"<?=$listLength > 1 ? ' size="'.$listLength.'"' : ''?><?=$bMultilple ? ' multiple="multiple"' : ''?>>
	<option value=""><?=GetMessage("MAIN_NO")?></option><?
endif;

foreach ($arParams["arUserField"]["USER_TYPE"]["FIELDS"] as $key => $val)
{

	$bSelected = in_array($key, $arResult["VALUE"]) && (
		(!$bWasSelect) ||
		($arParams["arUserField"]["MULTIPLE"] == "Y")
	);
	$bWasSelect = $bWasSelect || $bSelected;

	if($arParams["arUserField"]["SETTINGS"]["DISPLAY"]=="CHECKBOX")
	{
		?><?if($arParams["arUserField"]["MULTIPLE"]=="Y"):?>
			<label><input type="checkbox" value="<?=$key?>" name="<?=$sFieldName?>"<?=($bSelected? " checked" : "")?>><?=$val?></label><br />
		<?else:?>		
			<label><input type="radio" value="<?=$key?>" name="<?=$sFieldName?>"<?=($bSelected? " checked" : "")?>><?=$val?></label><br />
		<?endif;?><?
	}
	else
	{
		?><option value="<?=$key?>"<?=$bSelected ? " selected" : ""?>><?=$val?></option><?
	}
}
if($arParams["arUserField"]["SETTINGS"]["DISPLAY"]!="CHECKBOX"):
?></select><?
endif;?>