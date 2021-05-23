<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (is_array($arResult['SECTIONS']) && count($arResult['SECTIONS']) >= 0):
?>
<ul class="bx-user-sections-layout">
<?
	foreach ($arResult['SECTIONS'] as $arSection):

?>
	<li><?if ($arSection['URL']):?><a href="<?echo $arSection['URL']?>"><?endif;?><?echo htmlspecialcharsbx($arSection['NAME'])?><?if ($arSection['URL']):?></a><?endif;?></li>
<?
	endforeach;
?>
</ul>
<?
endif;
?>