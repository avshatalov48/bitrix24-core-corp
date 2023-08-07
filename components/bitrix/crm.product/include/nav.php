<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!isset($arResult['INTERNAL']) || !$arResult['INTERNAL'])
{
	if (isset($arResult['PRODUCT_ID']))
	{
        $listUrl = CComponentEngine::MakePathFromTemplate(
            $arParams['PATH_TO_PRODUCT_LIST'],
            array('section_id' => isset($arParams['SECTION_ID']) ? $arParams['SECTION_ID'] : 0));

		$GLOBALS['APPLICATION']->AddChainItem(GetMessage('CRM_PRODUCT_NAV_TITLE_LIST'), $listUrl);
		if ($arResult['PRODUCT_ID'] > 0)
		{
			$isCopyMode = (isset($arResult['IS_COPY_MODE'])
				&& ($arResult['IS_COPY_MODE'] === 'Y' || $arResult['IS_COPY_MODE'] === true));
			$GLOBALS['APPLICATION']->SetTitle(
				GetMessage(
					'CRM_PRODUCT_NAV_TITLE_'.($isCopyMode ? 'COPY' : 'EDIT'),
					array('#NAME#' => $arResult['PRODUCT']['NAME'])
				)
			);
		}
		else
		{
			$GLOBALS['APPLICATION']->SetTitle(GetMessage('CRM_PRODUCT_NAV_TITLE_ADD'));
		}
	}
	else
	{
		$GLOBALS['APPLICATION']->SetTitle(GetMessage('CRM_PRODUCT_NAV_TITLE_LIST'));
	}
}
?>