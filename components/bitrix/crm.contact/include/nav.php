<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Service\Container;

global $APPLICATION;

if (!isset($arResult['INTERNAL']) || !$arResult['INTERNAL'])
{
	$defaultTitle = GetMessage('CRM_CONTACT_NAV_TITLE_LIST_SHORT');
	if (empty($defaultTitle))
	{
		$defaultTitle = GetMessage('CRM_CONTACT_NAV_TITLE_LIST');
	}

	if (isset($arResult['CATEGORY_ID']) && $arResult['CATEGORY_ID'] > 0)
	{
		$category = Container::getInstance()
			->getFactory(CCrmOwnerType::Contact)
			->getCategory($arResult['CATEGORY_ID'])
		;

		if (isset($category))
		{
			$defaultTitle = htmlspecialcharsbx($category->getName());
		}
	}

	if (isset($arResult['CRM_CUSTOM_PAGE_TITLE']))
	{
		$APPLICATION->SetTitle($arResult['CRM_CUSTOM_PAGE_TITLE']);
	}
	elseif (isset($arResult['ELEMENT']['ID']))
	{
		$APPLICATION->AddChainItem($defaultTitle, $arParams['PATH_TO_CONTACT_LIST']);

		if (isset($arResult['ELEMENT']['~FORMATTED_NAME']))
		{
			$APPLICATION->SetTitle(
				GetMessage(
					'CRM_CONTACT_NAV_TITLE_EDIT',
					['#NAME#' => $arResult['ELEMENT']['~FORMATTED_NAME']]
				)
			);
		}
		elseif (isset($arResult['ELEMENT']['ID']))
		{
			$APPLICATION->SetTitle(
				GetMessage(
					'CRM_CONTACT_NAV_TITLE_EDIT',
					[
						'#NAME#' => CCrmContact::PrepareFormattedName(
							[
								'HONORIFIC' => $arResult['ELEMENT']['~HONORIFIC'] ?? '',
								'NAME' => $arResult['ELEMENT']['~NAME'] ?? '',
								'LAST_NAME' => $arResult['ELEMENT']['~LAST_NAME'] ?? '',
								'SECOND_NAME' => $arResult['ELEMENT']['~SECOND_NAME'] ?? ''
							]
						)
					]
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
