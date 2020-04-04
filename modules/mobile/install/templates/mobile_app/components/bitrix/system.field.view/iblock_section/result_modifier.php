<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (is_array($arResult['VALUE']) && count($arResult['VALUE']) > 0)
{
	if(!CModule::IncludeModule("iblock"))
		return;

	if (array_key_exists("inChain", $arParams) && $arParams["inChain"] == "Y")
	{

		$bMultiHead = false;
		$rsSections = CIBlockSection::GetList(
			array($by=>$order), 
			array(
				"IBLOCK_ID" => $arParams["arUserField"]["SETTINGS"]["IBLOCK_ID"],
				"GLOBAL_ACTIVE" => "Y",
				"DEPTH_LEVEL" => 1
			),
			false,
			array("ID")
		);
		if ($rsSections->Fetch() && $rsSections->Fetch())
			$bMultiHead = true;

		foreach($arResult['VALUE'] as $sectionID)
		{
			$iDepth = 1;
			$rsPath = GetIBlockSectionPath($arParams["arUserField"]["SETTINGS"]["IBLOCK_ID"], $sectionID);
			while($arPath = $rsPath->GetNext())
			{
				if ($bMultiHead || $iDepth > 1)
					$arResult["CHAIN"][$sectionID][] = $arPath;
				$iDepth++;
			}
		}

	}

	$arValue = array();
	$dbRes = CIBlockSection::GetList(array('left_margin' => 'asc'), array('ID' => $arResult['VALUE']), false);
	while ($arRes = $dbRes->GetNext())
	{
		$arValue[$arRes['ID']] = $arRes['NAME'];
	}
	$arResult['VALUE'] = $arValue;

}

?>