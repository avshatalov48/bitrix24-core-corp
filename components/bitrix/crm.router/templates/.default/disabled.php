<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Restriction\AvailabilityManager;

/**
 * @var $arResult array
 */

$availabilityManager = AvailabilityManager::getInstance();
$sliderCode = $arResult['sliderCode'] ?? null;

if ($sliderCode === \Bitrix\Crm\Integration\Intranet\ToolsManager::CRM_SLIDER_CODE)
{
	print $availabilityManager->getCrmInaccessibilityContent();

	return;
}

if ($sliderCode === \Bitrix\Crm\Integration\Intranet\ToolsManager::DYNAMIC_SLIDER_CODE)
{
	print $availabilityManager->getDynamicInaccessibilityContent();

	return;
}

if ($sliderCode === \Bitrix\Crm\Integration\Intranet\ToolsManager::INVOICE_SLIDER_CODE)
{
	print $availabilityManager->getInvoiceInaccessibilityContent();

	return;
}

if ($sliderCode === \Bitrix\Crm\Integration\Intranet\ToolsManager::QUOTE_SLIDER_CODE)
{
	print $availabilityManager->getQuoteInaccessibilityContent();

	return;
}

print $availabilityManager->getExternalDynamicInaccessibilityContent();