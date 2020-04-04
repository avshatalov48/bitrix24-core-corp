<?
IncludeModuleLangFile(__FILE__);

class CRatingsComponentsWiki
{
	function OnAddRatingVote($id, $arParams)
	{
		if ($arParams['ENTITY_TYPE_ID'] == 'IBLOCK_ELEMENT')
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->ClearByTag('wiki_'.intval($arParams['ENTITY_ID']));

			return true;
		}
		return false;
	}

	function OnCancelRatingVote($id, $arParams)
	{
		return CRatingsComponentsWiki::OnAddRatingVote($id, $arParams);
	}
	
	function BeforeIndex($arParams)
	{
		if (
			$arParams['PARAM1'] == 'wiki' 
			&& intval($arParams['PARAM2']) > 0 
			&& intval($arParams['ITEM_ID']) > 0
		)
		{
			$arParams['ENTITY_TYPE_ID'] = 'IBLOCK_ELEMENT';
			$arParams['ENTITY_ID'] = intval($arParams['ITEM_ID']);
			return $arParams;
		}
	}
}
?>