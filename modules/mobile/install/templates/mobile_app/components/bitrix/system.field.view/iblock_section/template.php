<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$bFirst = true;
			 
if (array_key_exists("CHAIN", $arResult) && count($arResult["CHAIN"]) > 0)
{
	foreach ($arResult["CHAIN"] as $arSectionPath)
	{
		if (!$bFirst):
			?><br><?
		else:
			$bFirst = false;
		endif;

		$bFirstChain = true;
		foreach ($arSectionPath as $arSection)
		{
			if ($arParams['arUserField']['SETTINGS']['SECTION_URL'])
				$res = $arSection["NAME"];
			elseif (StrLen($arParams['arUserField']['PROPERTY_VALUE_LINK']) > 0)
				$res = $arSection["NAME"];
			else
				$res = $arSection["NAME"];
	
			if (!$bFirstChain):
				?> - <?
			else:
				$bFirstChain = false;
			endif;

			?><span class="fields enumeration"><?=$res?></span><?
		}
	}
}
else
{
	foreach ($arResult["VALUE"] as $ID => $res):

		if ($arParams['arUserField']['SETTINGS']['SECTION_URL'])
			$res = $res;
		elseif (StrLen($arParams['arUserField']['PROPERTY_VALUE_LINK']) > 0)
			$res = $res;
	
		if (!$bFirst):
			?>, <?
		else:
			$bFirst = false;
		endif;

		?><span class="fields enumeration"><?=$res?></span><?
	endforeach;
}
?>