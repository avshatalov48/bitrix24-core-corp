<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

global $APPLICATION;
if (!isset($arResult['INTERNAL']) || !$arResult['INTERNAL'])
{
	$defaultTitle = GetMessage('CRM_CONTACT_NAV_TITLE_LIST_SHORT');
	if(empty($defaultTitle))
	{
		$defaultTitle = GetMessage('CRM_CONTACT_NAV_TITLE_LIST');
	}

	if(isset($arResult['CRM_CUSTOM_PAGE_TITLE']))
	{
		$APPLICATION->SetTitle($arResult['CRM_CUSTOM_PAGE_TITLE']);
	}
	elseif(isset($arResult['ELEMENT']['ID']))
	{
		$APPLICATION->AddChainItem($defaultTitle, $arParams['PATH_TO_CONTACT_LIST']);

		if(isset($arResult['ELEMENT']['~FORMATTED_NAME']))
		{
			$APPLICATION->SetTitle(GetMessage('CRM_CONTACT_NAV_TITLE_EDIT', array('#NAME#' => $arResult['ELEMENT']['~FORMATTED_NAME'])));
		}
		elseif (isset($arResult['ELEMENT']['ID']))
		{
			$APPLICATION->SetTitle(
				GetMessage(
					'CRM_CONTACT_NAV_TITLE_EDIT',
					array(
						'#NAME#' => CCrmContact::PrepareFormattedName(
							array(
								'HONORIFIC' => isset($arResult['ELEMENT']['~HONORIFIC']) ? $arResult['ELEMENT']['~HONORIFIC'] : '',
								'NAME' => isset($arResult['ELEMENT']['~NAME']) ? $arResult['ELEMENT']['~NAME'] : '',
								'LAST_NAME' => isset($arResult['ELEMENT']['~LAST_NAME']) ? $arResult['ELEMENT']['~LAST_NAME'] : '',
								'SECOND_NAME' => isset($arResult['ELEMENT']['~SECOND_NAME']) ? $arResult['ELEMENT']['~SECOND_NAME'] : ''
							)
						)
					)
				)
			);
		}
		else
		{
			$APPLICATION->SetTitle(GetMessage('CRM_CONTACT_NAV_TITLE_ADD'));
		}
	}
	else
	{
		$APPLICATION->SetTitle($defaultTitle);
	}
}
?>