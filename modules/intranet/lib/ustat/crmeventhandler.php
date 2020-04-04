<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2013 Bitrix
 */

namespace Bitrix\Intranet\UStat;

class CrmEventHandler
{
	const SECTION = 'CRM';

	const TITLE = 'INTRANET_USTAT_SECTION_CRM_NAME';

	public static function getTitle()
	{
		IncludeModuleLangFile(__FILE__);

		return GetMessage(static::TITLE);
	}

	public static function registerListeners()
	{
		RegisterModuleDependences("crm", "OnAfterCrmContactAdd", "intranet", "\\".__CLASS__, "onAfterCrmContactAddEvent");
		RegisterModuleDependences("crm", "OnAfterCrmCompanyAdd", "intranet", "\\".__CLASS__, "onAfterCrmCompanyAddEvent");
		RegisterModuleDependences("crm", "OnAfterCrmLeadAdd", "intranet", "\\".__CLASS__, "onAfterCrmLeadAddEvent");
		RegisterModuleDependences("crm", "OnAfterCrmDealAdd", "intranet", "\\".__CLASS__, "onAfterCrmDealAddEvent");
		RegisterModuleDependences("crm", "OnAfterCrmAddEvent", "intranet", "\\".__CLASS__, "onAfterCrmAddEventEvent");
		RegisterModuleDependences("sale", "OnOrderAdd", "intranet", "\\".__CLASS__, "onOrderAddEvent");
		RegisterModuleDependences("sale", "OnOrderUpdate", "intranet", "\\".__CLASS__, "onOrderUpdateEvent");
		RegisterModuleDependences("catalog", "OnProductAdd", "intranet", "\\".__CLASS__, "onProductAddEvent");
		RegisterModuleDependences("catalog", "OnProductUpdate", "intranet", "\\".__CLASS__, "onProductUpdateEvent");
	}

	public static function unregisterListeners()
	{
		UnRegisterModuleDependences("crm", "OnAfterCrmContactAdd", "intranet", "\\".__CLASS__, "onAfterCrmContactAddEvent");
		UnRegisterModuleDependences("crm", "OnAfterCrmCompanyAdd", "intranet", "\\".__CLASS__, "onAfterCrmCompanyAddEvent");
		UnRegisterModuleDependences("crm", "OnAfterCrmLeadAdd", "intranet", "\\".__CLASS__, "onAfterCrmLeadAddEvent");
		UnRegisterModuleDependences("crm", "OnAfterCrmDealAdd", "intranet", "\\".__CLASS__, "onAfterCrmDealAddEvent");
		UnRegisterModuleDependences("crm", "OnAfterCrmAddEvent", "intranet", "\\".__CLASS__, "onAfterCrmAddEventEvent");
		UnRegisterModuleDependences("sale", "OnOrderAdd", "intranet", "\\".__CLASS__, "onOrderAddEvent");
		UnRegisterModuleDependences("sale", "OnOrderUpdate", "intranet", "\\".__CLASS__, "onOrderUpdateEvent");
		UnRegisterModuleDependences("catalog", "OnProductAdd", "intranet", "\\".__CLASS__, "onProductAddEvent");
		UnRegisterModuleDependences("catalog", "OnProductUpdate", "intranet", "\\".__CLASS__, "onProductUpdateEvent");
	}

	public static function onAfterCrmContactAddEvent($arFields)
	{
		UStat::incrementCounter(static::SECTION);
	}

	public static function onAfterCrmCompanyAddEvent($arFields)
	{
		UStat::incrementCounter(static::SECTION);
	}

	public static function onAfterCrmLeadAddEvent($arFields)
	{
		UStat::incrementCounter(static::SECTION);
	}

	public static function onAfterCrmDealAddEvent($arFields)
	{
		UStat::incrementCounter(static::SECTION);
	}

	public static function onAfterCrmAddEventEvent($ID, $arFields)
	{
		if (!empty($arFields['EVENT_TYPE']) && $arFields['EVENT_TYPE'] != \CCrmEvent::TYPE_EXPORT)
		{
			UStat::incrementCounter(static::SECTION);
		}
	}

	public static function onOrderAddEvent($ID, $arFields)
	{
		UStat::incrementCounter(static::SECTION);
	}

	public static function onOrderUpdateEvent($ID, $arFields)
	{
		UStat::incrementCounter(static::SECTION);
	}

	public static function onProductAddEvent($ID, $arFields)
	{
		UStat::incrementCounter(static::SECTION);
	}

	public static function onProductUpdateEvent($ID, $arFields)
	{
		UStat::incrementCounter(static::SECTION);
	}
}