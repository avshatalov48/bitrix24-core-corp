<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if ($arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_LAST_NAME'])
{
	if ($key = array_search($arParams['FILTER_NAME'].'_LAST_NAME', $arResult['FILTER_PARAMS'], true))
	{
		unset($arResult['FILTER_PARAMS'][$key]);
	}
}

$arParams['LIST_URL'] .= strpos($arParams['LIST_URL'], '?') === false ? '?' : '&';
?>
<a href="<?=$arParams['LIST_URL']?>set_filter_<?=$arParams['FILTER_NAME']?>=Y<?=GetFilterParams($arResult['FILTER_PARAMS'], true, $arExtraVars)?>"><?echo GetMessage('ISS_TPL_APLH_ALL')?></a>&nbsp;|
<?
$alph = GetMessage('ISS_TPL_ALPH');
for ($i = 0; $i < strlen($alph); $i++)
//for ($i = ord(GetMessage('ISS_TPL_APLH_FIRST')); $i <= ord(GetMessage('ISS_TPL_APLH_LAST')); $i++)
{
	$symbol = substr($alph, $i, 1);
	$bCurrent = $arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_LAST_NAME'] == $symbol.'%';
?><a href="<?=$arParams['LIST_URL']?>set_filter_<?=$arParams['FILTER_NAME']?>=Y&<?=$arParams['FILTER_NAME']?>_LAST_NAME=<?=urlencode($symbol.'%')?><?=GetFilterParams($arResult['FILTER_PARAMS'], true, $arExtraVars)?>"><?=$bCurrent ? '<b>' : ''?><?=$symbol?><?=$bCurrent ? '</b>' : ''?></a>&nbsp;<?
}
?>