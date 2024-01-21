<?php
namespace Bitrix\Crm\Component\ControlPanel;

use CCrmOwnerType;

final class ControlPanelMenuMapper
{
	public const CONTROL_PANEL_CODE_NAME = 'crm_control_panel_menu';
	public const MENU_ID_CRM_CLIENT = 'crm_clients';
	public const MENU_ID_CRM_CATALOGUE = 'crm_catalogue';
	public const MENU_ID_CRM_INTEGRATIONS = 'crm_integrations';
	public const MENU_ID_CRM_SALES = 'crm_sales';
	public const MENU_ID_CRM_SETTINGS = 'crm_settings';
	public const MENU_ID_CRM_BI = 'crm_bi';
	public const MENU_ID_CRM_ANALYTICS = 'crm_analytics';
	public const MENU_ID_CRM_STORE_CONTRACTORS = 'menu_crm_store_contractors';
	public const MENU_ID_DYNAMIC_LIST = 'dynamic_list';
	public const MENU_ID_CRM_SIGN_COUNTERPARTY = 'menu_crm_counterparty';
	public const MENU_ID_CRM_SIGN_COUNTERPARTY_CONTACTS = 'menu_crm_counterparty_contacts';
	public const MENU_ID_CRM_STORE_CONTRACTORS_COMPANIES = 'menu_crm_store_contractors_companies';
	public const MENU_ID_CRM_STORE_CONTRACTORS_CONTACTS = 'menu_crm_store_contractors_contacts';
	public const MENU_ID_CRM_CONTACT_CENTER = 'menu_contact_center';
	public const MENU_ID_CRM_CONTACT = 'menu_crm_contact';
	public const MENU_ID_CRM_COMPANY = 'menu_crm_company';
	// The hierarchy map is not complete and contains only tabs that are used in mCRM. Will be filled as needed.
	// 3rd nesting level button MENU_ID example:
	// 'crm_control_panel_menu_crm_clients:menu_crm_counterparty:menu_crm_counterparty_contacts'

	// child => parent
	private const INVERTED_MENU_ID_HIERARCHY = [
		'menu_crm_catalog' => self::MENU_ID_CRM_CATALOGUE,
		'menu_crm_store_docs' => self::MENU_ID_CRM_CATALOGUE,
		'menu_crm_contact' => self::MENU_ID_CRM_CLIENT,
		'menu_crm_company' => self::MENU_ID_CRM_CLIENT,
		self::MENU_ID_CRM_CONTACT_CENTER => self::MENU_ID_CRM_CLIENT,
		'menu_crm_smart_invoice' => self::MENU_ID_CRM_SALES,
		'menu_crm_invoice' => self::MENU_ID_CRM_SALES,
		'menu_crm_quote' => self::MENU_ID_CRM_SALES,
		'menu-sale-center' => self::MENU_ID_CRM_SALES,
		'menu_terminal' => self::MENU_ID_CRM_SALES,
	];

	private const MENU_ID_MAP = [
		[
			'Id' => 'LEAD',
			'EntityTypeId' => CCrmOwnerType::Lead,
			'MenuId' => 'menu_crm_lead',
		],
		[
			'Id' => 'DEAL',
			'EntityTypeId' => CCrmOwnerType::Deal,
			'MenuId' => 'menu_crm_deal',
		],
		[
			'Id' => 'CATALOG',
			'MenuId' => 'menu_crm_catalog',
		],
		[
			'Id' => 'STORE_DOCUMENTS',
			'MenuId' => 'menu_crm_store_docs',
		],
		[
			'Id' => 'CONTACT',
			'EntityTypeId' => CCrmOwnerType::Contact,
			'MenuId' => 'menu_crm_contact',
		],
		[
			'Id' => 'COMPANY',
			'EntityTypeId' => CCrmOwnerType::Company,
			'MenuId' => 'menu_crm_company',
		],
		[
			'Id' => 'SMART_INVOICE',
			'EntityTypeId' => CCrmOwnerType::SmartInvoice,
			'MenuId' => 'menu_crm_smart_invoice',
		],
		[
			'Id' => 'INVOICE',
			'EntityTypeId' => CCrmOwnerType::Invoice,
			'MenuId' => 'menu_crm_invoice',
		],
		[
			'Id' => 'QUOTE',
			'EntityTypeId' => CCrmOwnerType::Quote,
			'MenuId' => 'menu_crm_quote',
		],
		[
			'Id' => 'SALES_CENTER',
			'MenuId' => 'menu-sale-center',
		],
		[
			'Id' => 'TERMINAL',
			'MenuId' => 'menu_terminal',
		],
		[
			'Id' => 'ANALYTICS_SALES_FUNNEL',
		],
		[
			'Id' => 'ANALYTICS_MANAGERS',
		],
		[
			'Id' => 'ANALYTICS_DIALOGS',
		],
		[
			'Id' => 'ANALYTICS_CALLS',
		],
		[
			'Id' => 'CRM_TRACKING',
			'MenuId' => 'menu_crm_tracking',
		],
		[
			'Id' => 'REPORT',
			'MenuId' => 'menu_crm_report',
		],
		[
			'Id' => 'ANALYTICS_BI',
		],
		[
			'Id' => 'TELEPHONY',
			'MenuId' => 'menu_telephony',
		],
		[
			'Id' => 'MAIL',
		],
		[
			'Id' => 'MESSENGERS',
		],
		[
			'Id' => 'SITEBUTTON',
			'MenuId' => 'menu_crm_button',
		],
		[
			'Id' => 'WEBFORM',
			'MenuId' => 'menu_crm_webform',
		],
		[
			'Id' => 'CONTACT_CENTER',
			'MenuId' => self::MENU_ID_CRM_CONTACT_CENTER,
		],
		[
			'Id' => 'MARKETPLACE',
			'MenuId' => 'menu_crm_marketplace',
		],
		[
			'Id' => 'MARKETPLACE_CRM_MIGRATION',
		],
		[
			'Id' => 'ONEC',
		],
		[
			'Id' => 'MARKETPLACE_CRM_SOLUTIONS',
		],
		[
			'Id' => 'DEVOPS',
			'MenuId' => 'menu_devops',
		],
		[
			'Id' => 'ANALYTICS',
			'MenuId' => 'menu_crm_analytics',
		],
		[
			'Id' => 'SETTINGS',
			'MenuId' => 'menu_crm_configs',
		],
		[
			'Id' => 'MY_COMPANY',
		],
		[
			'Id' => 'PERMISSIONS',
		],
		[
			'Id' => 'CRM_PERMISSIONS',
		],
		[
			'Id' => 'CATALOG_PERMISSIONS',
		],
		[
			'Id' => 'RECYCLE_BIN',
			'MenuId' => 'menu_crm_recycle_bin',
		],
		[
			'Id' => 'DEAL_FUNNEL',
			'MenuId' => 'menu_crm_funel',
		],
		[
			'Id' => 'EVENT',
			'MenuId' => 'menu_crm_event',
		],
		[
			'Id' => 'MY_ACTIVITY',
			'MenuId' => 'menu_crm_activity',
		],
		[
			'Id' => 'SALES_CENTER_DELIVERY',
		],
		[
			'Id' => 'SALES_CENTER_PAYMENT',
		],
		[
			'Id' => 'DYNAMIC_LIST',
			'MenuId' => 'dynamic_menu',
		],
		[
			'Id' => 'PRODUCT',
			'MenuId' => 'menu_crm_catalog',
		],
		[
			'Id' => 'ORDER',
			'MenuId' => 'menu_crm_order',
		],
		[
			'Id' => 'REPORT',
			'MenuId' => 'menu_crm_report',
		],
		[
			'Id' => 'START',
			'MenuId' => 'menu_crm_start',
		],
		[
			'Id' => 'STREAM',
			'MenuId' => 'menu_crm_stream',
		],
	];

	public static function getParentMenuId(string $menuId, ?bool $withCRMMenuCodeNamePrefix = false): ?string
	{
		if (!empty($menuId) && !empty(self::INVERTED_MENU_ID_HIERARCHY[$menuId]))
		{
			if ($withCRMMenuCodeNamePrefix)
			{
				return self::CONTROL_PANEL_CODE_NAME . '_' . self::INVERTED_MENU_ID_HIERARCHY[$menuId];
			}

			return self::INVERTED_MENU_ID_HIERARCHY[$menuId];
		}

		return null;
	}

	public static function getParentDynamicMenuId(int $entityTypeId, ?bool $withCRMMenuCodeNamePrefix = false): ?string
	{
		if (isset($entityTypeId) && CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			if ($withCRMMenuCodeNamePrefix)
			{
				return self::CONTROL_PANEL_CODE_NAME . '_' . self::MENU_ID_DYNAMIC_LIST;
			}

			return self::MENU_ID_DYNAMIC_LIST;
		}

		return null;
	}

	public static function getCrmTabMenuIdByEntityTypeId(
		int $entityTypeId,
		?bool $withCRMMenuCodeNamePrefix = false,
		?bool $withParentTabMenuId = false
	): ?string
	{
		$result = '';
		if ($withCRMMenuCodeNamePrefix)
		{
			$result = self::CONTROL_PANEL_CODE_NAME . '_';
		}
		if (CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			$result .= self::MENU_ID_DYNAMIC_LIST . ':dynamic_' . $entityTypeId;

			return $result;
		}
		foreach (self::MENU_ID_MAP as $item)
		{
			if (isset($item['EntityTypeId']) && $item['EntityTypeId'] === $entityTypeId)
			{
				if ($withParentTabMenuId)
				{
					$parentTabMenuId = self::getParentMenuId($item['MenuId']);
					if (isset($parentTabMenuId))
					{
						$result .= $parentTabMenuId . ':';
					}
				}
				$result .= $item['MenuId'];

				return $result;
			}
		}

		return null;
	}

	public static function getCrmTabMenuIdById(
		string $id,
		?bool $withCRMMenuCodeNamePrefix = false,
		?bool $withParentTabMenuId = false
	): ?string
	{
		if (!empty($id))
		{
			$result = '';
			if ($withCRMMenuCodeNamePrefix)
			{
				$result = self::CONTROL_PANEL_CODE_NAME . '_';
			}
			foreach (self::MENU_ID_MAP as $item)
			{
				if ($item['Id'] === $id)
				{
					if ($withParentTabMenuId)
					{
						$parentTabMenuId = self::getParentMenuId($item['MenuId']);
						if (isset($parentTabMenuId))
						{
							$result .= $parentTabMenuId . ':';
						}
					}
					$result .= $item['MenuId'];

					return $result;
				}
			}
		}

		return null;
	}
}