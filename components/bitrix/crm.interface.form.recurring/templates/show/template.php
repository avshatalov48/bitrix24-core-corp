<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<div class="crm-recur-options-field-fn crm-recur-options-field-ok">
	<?
	if (LANGUAGE_ID == 'ru')
	{
		echo $arResult['HINT'];
	}
	else
	{
		echo $arResult['NEXT_EXECUTION_HINT'];
	}
	?>
</div>