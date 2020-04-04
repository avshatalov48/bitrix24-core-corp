<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$formName = 'FILTER_'.$arParams['FILTER_NAME'].'_adv';
?>
<?if ($arResult['CURRENT_USER']['DEPARTMENT_TOP']):?>
<script type="text/javascript">
function BXChangeFilterTop_adv(ob)
{
	if (ob.checked) 
	{
		var obFld = document.forms['<?=$formName?>']['<?=$arParams['FILTER_NAME']?>_UF_DEPARTMENT<?=$arParams['FILTER_DEPARTMENT_SINGLE'] == 'Y' ? '' : '[]'?>'];
		if (obFld)
			obFld.value = <?=intval($arResult['CURRENT_USER']['DEPARTMENT_TOP'])?>;
		
	}
}
</script>
<?endif;?>
<form name="<?=$formName?>" action="<?=$arParams['LIST_URL']?>" class="bx-selector-form filter-form">
<input type="hidden" name="current_filter" value="adv" />
<?
if ($arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_LAST_NAME']):
?>
<input type="hidden" name="<?=$arParams['FILTER_NAME']?>_LAST_NAME" value="<?=htmlspecialcharsbx($arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_LAST_NAME'])?>" />
<?
endif;
?>
<table class="bx-selector-table filter-table">
<tbody>
<?
if (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite() || $arParams["EXTRANET_TYPE"] == "employees"):
?>
<tr>
	<td class="bx-filter-caption"><?echo GetMessage('INTR_ISS_PARAM_DEPARTMENT')?>: </td>
	<td>
<?if ($arResult['CURRENT_USER']['DEPARTMENT_TOP']):?>
		<input type="checkbox" id="only_mine_office" onclick="BXChangeFilterTop_adv(this)" <?echo $arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_UF_DEPARTMENT'] == $arResult['CURRENT_USER']['DEPARTMENT_TOP'] || $arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_UF_DEPARTMENT'] == array($arResult['CURRENT_USER']['DEPARTMENT_TOP']) ? 'checked="checked"' : ''?> />
		<label for="only_mine_office"><?echo GetMessage('INTR_ISS_PARAM_DEPARTMENT_MINE')?></label><br />
<?endif;?>
<?
	CIntranetUtils::ShowDepartmentFilter($arResult['UF_DEPARTMENT_field'], $arResult['bVarsFromForm']);
?>
</td>
</tr><tr>
<?
endif; 
?>
	<td class="bx-filter-caption"><?echo GetMessage('INTR_ISS_PARAM_POST')?>: </td>
	<td><input type="text" name="<?=$arParams['FILTER_NAME']?>_POST" value="<?=$arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_POST']?>" /></td>
</tr>
<?
if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite() && $arParams["EXTRANET_TYPE"] != "employees"):
?>
<tr>
	<td class="bx-filter-caption"><?echo GetMessage('INTR_ISS_PARAM_WORK_COMPANY')?>: </td>
	<td><input type="text" name="<?=$arParams['FILTER_NAME']?>_COMPANY" value="<?=$arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_COMPANY']?>" /></td>
</tr>
<?
endif;
?><tr>
	<td class="bx-filter-caption"><?echo GetMessage('INTR_ISS_PARAM_FIO')?>: </td>
	<td><input type="text" name="<?=$arParams['FILTER_NAME']?>_FIO" value="<?=$arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_FIO']?>" /></td>
</tr>
<tr>
	<td class="bx-filter-caption"><?echo GetMessage('INTR_ISS_PARAM_EMAIL')?>: </td>
	<td><input type="text" name="<?=$arParams['FILTER_NAME']?>_EMAIL" value="<?=$arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_EMAIL']?>" /></td>
</tr>
<tr>
	<td class="bx-filter-caption"><?echo GetMessage('INTR_ISS_PARAM_PHONE_INNER')?>: </td>
	<td><input type="text" name="<?=$arParams['FILTER_NAME']?>_UF_PHONE_INNER" value="<?=$arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_UF_PHONE_INNER']?>" /></td>
</tr>
<tr>
	<td class="bx-filter-caption"><?echo GetMessage('INTR_ISS_PARAM_KEYWORDS')?>: </td>
	<td><input type="text" name="<?=$arParams['FILTER_NAME']?>_KEYWORDS" value="<?=$arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_KEYWORDS']?>" /></td>
</tr>
</tbody>
<tfoot>
<tr>
	<td colspan="2">
		<input type="hidden" name="set_filter_<?=$arParams['FILTER_NAME']?>" value="Y" /> 
		<input type="submit" name="set_filter_<?=$arParams['FILTER_NAME']?>" value="<?echo GetMessage('INTR_ISS_BUTTON_SUBMIT')?>" class="bx-submit-btn" /> 
		<input type="submit" name="del_filter_<?=$arParams['FILTER_NAME']?>" value="<?echo GetMessage('INTR_ISS_BUTTON_CANCEL')?>" class="bx-reset-btn" />
	</td>
</tr>
</tfoot>
</table>
</form>