<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();?>
<?$arField = $arParams['arUserField'];?>
<?$bMultiple = isset($arField['MULTIPLE']) && $arField['MULTIPLE'] == 'Y';?>

<select name="<?=$arField['FIELD_NAME']?>"<?=$bMultiple ? ' multiple="multiple"' : ''?>>
<?if(isset($arParams['bShowNotSelected']) && !is_array($arParams['bShowNotSelected']) && $arParams['bShowNotSelected'] == true)
{
	//Bugfix #24115
	?><option value=""><?=htmlspecialcharsbx(GetMessage('MAIN_NO'))?></option><?
}?>
<?$bWasSelect = false;?>
<?foreach ($arField['USER_TYPE']['FIELDS'] as $key => $val)
{
	$bSelected = (!$bWasSelect || $bMultiple) && in_array($key, $arResult['VALUE']);
	?><option value="<?=htmlspecialcharsbx($key)?>"<?= $bSelected ? ' selected' : ''?>><?=htmlspecialcharsbx($val)?></option><?
	if(!$bWasSelect && $bSelected)
	{
		$bWasSelect = true;
	}
}?>
</select>