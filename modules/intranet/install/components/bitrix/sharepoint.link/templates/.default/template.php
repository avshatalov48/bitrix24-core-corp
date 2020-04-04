<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die(); 

//echo '<pre>'; print_r($arResult); echo '</pre>';

if ($arParams['OUTPUT'] == 'N') return;
?>
<div class="bx-sp-links">
<?
foreach ($arResult['LINKS'] as $arLink):
?>
	<a href="javascript:void(0)" onclick="<?echo htmlspecialcharsbx($arLink['ONCLICK']);?>" class="bx-sp-link-<?=$arLink['TYPE']?>"><?=htmlspecialcharsex($arLink['TEXT'])?></a>
<?
endforeach;
?>
</div>
<?
if (isset($arResult['INFO'])):
?>
<div class="bx-sp-info">
	Последняя синхронизация: <?
		echo $arResult['INFO']['SYNC_DATE'] ? $arResult['INFO']['SYNC_DATE'] : 'еще не проводилась'
	?>
</div>
<?
endif;
?>