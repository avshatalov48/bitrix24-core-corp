<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$formName = 'FILTER_'.$arParams['FILTER_NAME'].'_adv';
?>
<form method="GET" name="<?=$formName?>" action="<?=$arParams['LIST_URL']?>">
	<input type="hidden" name="show_user" value="<?=$arParams['show_user']; ?>" />
	<input type="hidden" name="current_filter" value="adv" />
<?if ($arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_LAST_NAME']):?>
	<input type="hidden" name="<?=$arParams['FILTER_NAME']?>_LAST_NAME" value="<?=htmlspecialcharsbx($arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_LAST_NAME'])?>" />
<?endif;?>
	<input class="bx24-top-bar-search" type="text" id="user-fio" name="<?=$arParams['FILTER_NAME']?>_FIO" value="<?=$arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_FIO']?>" />
	<input type="hidden" name="set_filter_<?=$arParams['FILTER_NAME']?>" value="Y" /> 
<?if ($GLOBALS[$arParams['FILTER_NAME'].'_FIO'] <> ''):?>
	<span class="employee-search-wrap-cancel" onclick="BX('user-fio').value = ''; var form = BX(<?='FILTER_'.$arParams['FILTER_NAME'].'_adv'?>); BX.submit(form);"></span>
<?else:?>
	<span class="bx24-top-bar-search-icon" onclick="var form = BX(<?='FILTER_'.$arParams['FILTER_NAME'].'_adv'?>); BX.submit(form);"></span>
<?endif;?>
</form>
