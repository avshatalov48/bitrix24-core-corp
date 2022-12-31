<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if ($arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_LAST_NAME'])
{
	if ($key = array_search($arParams['FILTER_NAME'].'_LAST_NAME', $arResult['FILTER_PARAMS'], true))
	{
		unset($arResult['FILTER_PARAMS'][$key]);
	}
}

$arParams['LIST_URL'] .= mb_strpos($arParams['LIST_URL'], '?') === false ? '?' : '&';
$arExtraVars = array('show_user' => $arParams['show_user']);

$current_lang = '';
$bMultipleLang = count($arResult['ALPHABET']) > 1;

foreach ($arResult['ALPHABET'] as $lang => $arMess)
{
?>
<div id="employee-ABC" class="employee-ABC-popup" style="display:none;">
<?
	$alph = $arMess['ISS_TPL_ALPH'];
$alph_len = mb_strlen($alph);
	for ($i = 0; $i < $alph_len; $i++)
	{
		$symbol = mb_substr($alph, $i, 1);
		$bCurrent = $arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_LAST_NAME'] == $symbol.'%';
		if ($bCurrent && !$current_lang)
			$current_lang = $lang;
?><a class="employee-ABC-letter" href="<?=$arParams['LIST_URL']?>set_filter_<?=$arParams['FILTER_NAME']?>=Y&<?=$arParams['FILTER_NAME']?>_LAST_NAME=<?=urlencode($symbol.'%')?><?=GetFilterParams($arResult['FILTER_PARAMS'], true, $arExtraVars)?>"><?=$bCurrent ? '<b>' : ''?><?=$symbol?><?=$bCurrent ? '</b>' : ''?></a><?
	}
?><a class="employee-ABC-letter" href="<?=$arParams['LIST_URL']?>set_filter_<?=$arParams['FILTER_NAME']?>=Y<?=GetFilterParams($arResult['FILTER_PARAMS'], true, $arExtraVars)?>"><?echo $arMess['ISS_TPL_APLH_ALL']?></a>
</div>
<?
}
?>