<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (empty($arResult['VALUES']))
	return false;
	
$fistTd = true;
$lastComplexName = "";
?>
<table cellspacing="0" cellpadding="0" border="0" class="bx-edit-table">
<?foreach($arResult['VALUES'] as $arValue):
	
?>
<tr>
	<td class="bx-field-name" <?=($fistTd? 'style="background:none"': '')?>><?=($lastComplexName != $arValue['COMPLEX_NAME']? $arValue['COMPLEX_NAME'].':': '')?></td>
	<td class="bx-field-value" <?=($fistTd? 'style="background:none"': '')?>><?=$arValue['TEMPLATE']?></td>
</tr>
<?
	$lastComplexName = $arValue['COMPLEX_NAME'];
	$fistTd = false;
endforeach;
?>
</table>