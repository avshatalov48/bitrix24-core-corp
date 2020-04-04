<?php
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/webdav/general/ratings_components.php");

class CRatingsComponentsWebDav
{	
	function BeforeIndex($arParams)
	{ 
		if ($arParams['PARAM1'] == "library" && intval($arParams['PARAM2']) > 0 
			&& intval($arParams['ITEM_ID']) > 0)
		{
			$arParams["ENTITY_TYPE_ID"] = "IBLOCK_ELEMENT";
			$arParams["ENTITY_ID"] = intval($arParams['ITEM_ID']);
			return $arParams;
		}
	}
}