<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (is_array($arResult['SECTIONS']) && count($arResult['SECTIONS']) > 0):
	?><div class="bx-user-sections-layout"><?
	foreach ($arResult['SECTIONS'] as $arSection):
		?><div><?if ($arSection['URL']):?><a href="<?echo $arSection['URL']?>"><?endif;?><?echo htmlspecialcharsbx($arSection['NAME'])?><?if ($arSection['URL']):?></a><?endif;?></div><?
	endforeach;
	?></div><?
else:
	echo GetMessage('SONET_HEAD_USER_NOT_FOUND');	
endif;
?>