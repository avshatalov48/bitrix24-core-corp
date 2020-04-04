<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var CBitrixComponent $this */

if ($arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_LAST_NAME'])
{
	if ($key = array_search($arParams['FILTER_NAME'].'_LAST_NAME', $arResult['FILTER_PARAMS'], true))
	{
		unset($arResult['FILTER_PARAMS'][$key]);
	}
}
if ($arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_LAST_NAME_RANGE'])
{
	if ($key = array_search($arParams['FILTER_NAME'].'_LAST_NAME_RANGE', $arResult['FILTER_PARAMS'], true))
	{
		unset($arResult['FILTER_PARAMS'][$key]);
	}
}

$arParams['LIST_URL'] .= strrpos($arParams['LIST_URL'], '?') === false ? '?' : '&';
$arExtraVars      = array('current_view'   => $arParams['CURRENT_VIEW'],
                          'current_filter' => $arParams['CURRENT_FILTER']);
$arExtraVarsExcel = array('current_view'   => 'table',
                          'excel'          => 'yes',
                          'current_filter' => $arParams['CURRENT_FILTER']);

$currentLang   = '';
$langs         = array_keys($arResult['ALPHABET']);
$bMultipleLang = count($langs) > 1;
$clearFilterLink = $arParams['LIST_URL'] . http_build_query(array(
	'set_filter_' . $arParams['FILTER_NAME']=> 'Y',
));
?>

<span onclick="BX('excelUserExport').href='<?= $clearFilterLink . GetFilterParams($arResult['FILTER_PARAMS'], true, $arExtraVarsExcel)?>'"><a href="<?=$clearFilterLink . GetFilterParams($arResult['FILTER_PARAMS'], true, $arExtraVars)?>"><?= GetMessage('ISS_TPL_APLH_CLEAR_FILTER') ?></a></span>&nbsp;|
<? if($bMultipleLang): ?>
	<script>
		var bx_alph_current_lang = null;
		function BXToggleAlphabet(lang)
		{
			if (null != bx_alph_current_lang)
			{
				BX('bx_alphabet_' + bx_alph_current_lang).style.display = 'none';
				BX('bx_alph_select_' + bx_alph_current_lang).className = '';
			}

			bx_alph_current_lang = lang;

			BX('bx_alphabet_' + bx_alph_current_lang).style.display = 'inline';
			BX('bx_alph_select_' + bx_alph_current_lang).className = 'bx-current';
		}
	</script>
	<span id="bx_alphabet_selector">
		<? foreach($langs as $lang): ?>
			<a href="javascript:void(0)" onclick="BXToggleAlphabet('<?=CUtil::JSEscape($lang)?>')" id="bx_alph_select_<?=htmlspecialcharsbx($lang)?>"><?=htmlspecialcharsbx($lang)?></a>&nbsp;
		<? endforeach; ?>
	</span>
<? endif; ?>
<? foreach ($arResult['ALPHABET'] as $lang => $arMess): ?>
	<span id="bx_alphabet_<?=htmlspecialcharsbx($lang)?>"<?=$bMultipleLang ? ' style="display: none;"' : '';?>>
	<?
		$filterLinkLetterRange = $arParams['LIST_URL'] . http_build_query(array(
			'set_filter_' . $arParams['FILTER_NAME']      => 'Y',
			$arParams['FILTER_NAME'] . '_LAST_NAME_RANGE' => !empty($arMess['ISS_TPL_APLH_ALL']) ? $arMess['ISS_TPL_APLH_ALL'] : '',
		));

		$isRange = false;
		if($arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_LAST_NAME_RANGE'] == $arMess['ISS_TPL_APLH_ALL'])
		{
			$currentLang = $lang;
			$isRange     = true;
		}
		?>
		<span onclick="BX('excelUserExport').href='<?= $filterLinkLetterRange . GetFilterParams($arResult['FILTER_PARAMS'], true, $arExtraVarsExcel); ?>'"><a href="<?= $filterLinkLetterRange . GetFilterParams($arResult['FILTER_PARAMS'], true, $arExtraVars); ?>"><?= ($isRange)? '<u>' : '' ?>   <?= $arMess['ISS_TPL_APLH_ALL']?><?= ($isRange)? '</u>' : '' ?> </a></span>&nbsp;|
		<?
		$alph       = $arMess['ISS_TPL_ALPH'];
		$lengthAlph = strlen($alph);
		for ($i = 0; $i < $lengthAlph; $i++)
		{
			$symbol   = substr($alph, $i, 1);
			$bCurrent = $arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_LAST_NAME'] == $symbol.'%';
			if ($bCurrent && !$currentLang)
			{
				$currentLang = $lang;
			}
			$filterLinkLetter = $arParams['LIST_URL'] . http_build_query(array(
				'set_filter_' . $arParams['FILTER_NAME']=> 'Y',
				$arParams['FILTER_NAME'] . '_LAST_NAME' => $symbol.'%',
			));
			?>
			<span onclick="BX('excelUserExport').href='<?= $filterLinkLetter . GetFilterParams($arResult['FILTER_PARAMS'], true, $arExtraVarsExcel); ?>'"><a href="<?= $filterLinkLetter . GetFilterParams($arResult['FILTER_PARAMS'], true, $arExtraVars); ?>"><?=$bCurrent ? '<u>' : ''?><?=$symbol?><?=$bCurrent ? '</u>' : ''?></a></span>&nbsp;
		<?
		}
		?>
	</span>
<? endforeach; ?>
<? if ($bMultipleLang): ?>
	<script type="text/javascript">
		BXToggleAlphabet('<?=CUtil::JSEscape(!$currentLang? $langs[0] : $currentLang)?>');
	</script>
<? endif; ?>