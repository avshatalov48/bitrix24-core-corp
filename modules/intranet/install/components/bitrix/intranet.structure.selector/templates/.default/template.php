<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$formName = 'FILTER_'.$arParams['FILTER_NAME'].'_'.rand(0, 1000);
?>
<?if ($arResult['CURRENT_USER']['DEPARTMENT_TOP']):?>
<script type="text/javascript">
function bx_ChangeFilterTop(ob)
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
<table class="bx-selector-table filter-table">
<tbody>
<tr>
	<td>
<?if ($arResult['CURRENT_USER']['DEPARTMENT_TOP']):?>
		<input type="checkbox" id="only_mine_office" onclick="bx_ChangeFilterTop(this)" />
		<label for="only_mine_office"><?echo GetMessage('INTR_ISS_TPL_DEPARTMENT_MINE')?></label><br />
<?else:?>
		<?echo GetMessage('INTR_ISS_TPL_DEPARTMENT')?>:<br />
<?endif;?>
<?
	CIntranetUtils::ShowDepartmentFilter($arResult['UF_DEPARTMENT_field'], $arResult['bVarsFromForm']);
?>
</td>
	<td><?echo GetMessage('INTR_ISS_TPL_POST')?>:<br /><input type="text" name="<?=$arParams['FILTER_NAME']?>_POST" value="<?=$arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_POST']?>" /></td>
</tr>
<tr>
	<td><?echo GetMessage('INTR_ISS_TPL_FIO')?>:<br /><input type="text" name="<?=$arParams['FILTER_NAME']?>_FIO" value="<?=$arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_FIO']?>" /></td>
	<td><?echo GetMessage('INTR_ISS_TPL_EMAIL')?>:<br /><input type="text" name="<?=$arParams['FILTER_NAME']?>_EMAIL" value="<?=$arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_EMAIL']?>" /></td>
</tr>
<tr>
	<td colspan="2"><?echo GetMessage('INTR_ISS_TPL_KEYWORDS')?>:<br /><input style="width:100%" type="text" name="<?=$arParams['FILTER_NAME']?>_KEYWORDS" value="<?=$arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_KEYWORDS']?>" /></td>
</tr>
</tbody>
<tfoot>
<tr>
	<td colspan="2">
		<input type="submit" name="set_filter_<?=$arParams['FILTER_NAME']?>" value="<?echo GetMessage('INTR_ISS_TPL_SUBMIT')?>" class="bx-submit-btn" /> 
		<input type="submit" name="del_filter_<?=$arParams['FILTER_NAME']?>" value="<?echo GetMessage('INTR_ISS_TPL_CANCEL')?>" class="bx-reset-btn" />
	</td>
</tr>
</tfoot>
</table>
</form>