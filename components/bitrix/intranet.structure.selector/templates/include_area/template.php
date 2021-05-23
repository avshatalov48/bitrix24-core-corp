<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>
<form name="FILTER_include_<?=$arParams['FILTER_NAME']?>" action="<?=$arParams['LIST_URL']?>" class="bx-selector-form filter-form">
<div class="bx-selector-include filter-table">

	<div class="bx-selector-field-caption"><?echo GetMessage('INTR_ISS_TPL_FIO')?>:</div>
	<div class="bx-selector-field"><input type="text" name="<?=$arParams['FILTER_NAME']?>_FIO" /></div>

	<div class="bx-selector-field-caption"><?echo GetMessage('INTR_ISS_TPL_DEPARTMENT')?>:</div>
	<div class="bx-selector-field">
<?
	CIntranetUtils::ShowDepartmentFilter($arResult['UF_DEPARTMENT_field'], false);
?>
	</div>

	<div class="bx-selector-buttons"><input type="hidden" name="set_filter_<?=$arParams['FILTER_NAME']?>" value="Y" /><input type="submit" name="set_filter_<?=$arParams['FILTER_NAME']?>" value="<?echo GetMessage('INTR_ISS_TPL_SUBMIT')?>" class="bx-submit-btn" /></div>

</div>
</form>