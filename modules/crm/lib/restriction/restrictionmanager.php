<?php

namespace Bitrix\Crm\Restriction;

use Bitrix\Crm\Activity\Provider\Visit;
use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;

class RestrictionManager
{
	const SQL_ROW_COUNT_THRESHOLD = 5000;
	/** @var bool */
	private static $isInitialized;
	/** @var Bitrix24SqlRestriction */
	private static $sqlRestriction;
	/** @var Bitrix24AccessRestriction */
	private static $conversionRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $dupControlRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $historyViewRestriction;
	/** @var Bitrix24SearchLimitRestriction  */
	private static $searchLimitRestriction;
	/** @var Bitrix24QuantityRestriction|null  */
	private static $dealCategoryLimitRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $attributeConfigRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $permissionControlRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $dealRecurringRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $invoiceRecurringRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $detailsSearchByInnRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $detailsSearchByEdrpouRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $generatorRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $webformRestriction;
	/** @var Bitrix24QuantityRestriction|null  */
	private static $webformLimitRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $imconnectorRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $callListRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $addressSearchRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $ufAccessRightsRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $diskQuotaRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $callTrackerRestriction;
	/** @var DynamicTypesLimit  */
	private static $dynamicTypesLimit;
	/** @var Bitrix24AccessRestriction|null  */
	private static $leadsRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $quotesRestriction;
	/** @var OrderRestriction|null  */
	private static $orderRestriction;
	/** @var ClientFieldsRestriction  */
	private static $clientFieldsRestriction;
	/** @var ObserversFieldRestriction[]  */
	private static $observersFieldRestrictionList;
	/** @var ActivityFieldRestriction  */
	private static $activityFieldRestriction;
	/** @var Bitrix24AccessRestriction  */
	private static $observersRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $contactExportRestriction;
	/** @var IntegrationShopRestriction  */
	private static $integrationShopRestriction;
	/** @var Bitrix24AccessRestriction */
	private static $chatInDetailsRestriction;
	/** @var Bitrix24AccessRestriction */
	private static $visitRestriction;
	/** @var WebFormResultsRestriction  */
	private static $webFormResultsRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $invoicesRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $inventoryControlIntegrationRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $inventoryControl1cRestriction;
	/** @var Bitrix24AccessRestriction|null  */
	private static $calendarSharingRestriction;
	/** @var Bitrix24AccessRestriction */
	private static $taskRestriction;

	/**
	 * @return SqlRestriction
	 */
	public static function getSqlRestriction()
	{
		self::initialize();
		return self::$sqlRestriction;
	}
	/**
	 * @return AccessRestriction
	 */
	public static function getConversionRestriction()
	{
		self::initialize();
		return self::$conversionRestriction;
	}
	/**
	 * @return AccessRestriction
	 */
	public static function getDuplicateControlRestriction()
	{
		self::initialize();
		return self::$dupControlRestriction;
	}
	/**
	 * @return AccessRestriction
	 */
	public static function getHistoryViewRestriction()
	{
		self::initialize();
		return self::$historyViewRestriction;
	}
	/**
	 * @return QuantityRestriction
	 */
	public static function getDealCategoryLimitRestriction()
	{
		self::initialize();
		return self::$dealCategoryLimitRestriction;
	}

	/**
	 * @return AccessRestriction
	 */
	public static function getAutomationRestriction()
	{
		return null;
	}

	/**
	 * @return AccessRestriction
	 */
	public static function getGeneratorRestriction()
	{
		self::initialize();
		return self::$generatorRestriction;
	}
	/**
	 * @return AccessRestriction
	 */
	public static function getWebformRestriction()
	{
		self::initialize();
		return self::$webformRestriction;
	}
	/**
	 * @return QuantityRestriction
	 */
	public static function getWebformLimitRestriction()
	{
		self::initialize();
		return self::$webformLimitRestriction;
	}
	/**
	 * @return AccessRestriction
	 */
	public static function getImconnectorRestriction()
	{
		self::initialize();
		return self::$imconnectorRestriction;
	}
	/**
	 * @return AccessRestriction
	 */
	public static function getCallListRestriction()
	{
		self::initialize();
		return self::$callListRestriction;
	}
	/**
	 * @return Bitrix24SearchLimitRestriction
	 */
	public static function getSearchLimitRestriction()
	{
		self::initialize();
		return self::$searchLimitRestriction;
	}

	/**
	 * @return AccessRestriction
	 */
	public static function getAttributeConfigRestriction()
	{
		self::initialize();
		return self::$attributeConfigRestriction;
	}

	/**
	 * @return AccessRestriction
	 */
	public static function getPermissionControlRestriction()
	{
		self::initialize();
		return self::$permissionControlRestriction;
	}

	/**
	 * @return AccessRestriction
	 */
	public static function getDealRecurringRestriction()
	{
		self::initialize();
		return self::$dealRecurringRestriction;
	}

	/**
	 * @return AccessRestriction
	 */
	public static function getInvoiceRecurringRestriction()
	{
		self::initialize();
		return self::$invoiceRecurringRestriction;
	}

	/**
	 * @return AccessRestriction
	 */
	public static function getDetailsSearchByInnRestriction()
	{
		self::initialize();
		return self::$detailsSearchByInnRestriction;
	}

	/**
	 * @return bool
	 */
	public static function isDetailsSearchByInnPermitted()
	{
		return self::getDetailsSearchByInnRestriction()->hasPermission();
	}

	/**
	 * @return Bitrix24AccessRestriction|null
	 */
	public static function getUfAccessRightsRestriction()
	{
		self::initialize();
		return self::$ufAccessRightsRestriction;
	}

	/**
	 * @return AccessRestriction
	 */
	public static function getDetailsSearchByEdrpouRestriction()
	{
		self::initialize();
		return self::$detailsSearchByEdrpouRestriction;
	}

	/**
	 * @return bool
	 */
	public static function isDetailsSearchByEdrpouPermitted()
	{
		return self::getDetailsSearchByEdrpouRestriction()->hasPermission();
	}

	/**
	 * @return AccessRestriction
	 */
	public static function getAddressSearchRestriction()
	{
		self::initializeAddressRestriction();
		return self::$addressSearchRestriction;
	}

	/**
	 * @return AccessRestriction
	 */
	public static function getDiskQuotaRestriction()
	{
		self::initializeDiskQuotaRestriction();
		return self::$diskQuotaRestriction;
	}

	/**
	 * @return AccessRestriction
	 */
	public static function getCallTrackerRestriction()
	{
		self::initialize();
		return self::$callTrackerRestriction;
	}

	/**
	 * @return bool
	 */
	public static function isCallTrackerPermitted()
	{
		return self::getCallTrackerRestriction()->hasPermission();
	}

	/**
	 * @return void
	 */
	public static function reset()
	{
		self::initialize();
		self::initializeAddressRestriction();
		self::initializeDiskQuotaRestriction();

		self::$sqlRestriction->reset();
		self::$conversionRestriction->reset();
		self::$dupControlRestriction->reset();
		self::$historyViewRestriction->reset();
		self::$searchLimitRestriction->reset();
		self::$dealCategoryLimitRestriction->reset();
		self::$attributeConfigRestriction->reset();
		self::$permissionControlRestriction->reset();
		self::$dealRecurringRestriction->reset();
		self::$invoiceRecurringRestriction->reset();
		self::$detailsSearchByInnRestriction->reset();
		self::$detailsSearchByEdrpouRestriction->reset();
		self::$generatorRestriction->reset();
		self::$webformRestriction->reset();
		self::$webformLimitRestriction->reset();
		self::$imconnectorRestriction->reset();
		self::$callListRestriction->reset();
		self::$addressSearchRestriction->reset();
		self::$ufAccessRightsRestriction->reset();
		self::$diskQuotaRestriction->reset();

		self::$sqlRestriction = null;
		self::$conversionRestriction = null;
		self::$dupControlRestriction = null;
		self::$historyViewRestriction = null;
		self::$searchLimitRestriction = null;
		self::$dealCategoryLimitRestriction = null;
		self::$attributeConfigRestriction = null;
		self::$permissionControlRestriction = null;
		self::$dealRecurringRestriction = null;
		self::$invoiceRecurringRestriction = null;
		self::$detailsSearchByInnRestriction = null;
		self::$detailsSearchByEdrpouRestriction = null;
		self::$generatorRestriction = null;
		self::$webformRestriction = null;
		self::$webformLimitRestriction = null;
		self::$imconnectorRestriction = null;
		self::$callListRestriction = null;
		self::$addressSearchRestriction = null;
		self::$ufAccessRightsRestriction = null;
		self::$diskQuotaRestriction = null;

		self::$isInitialized = false;
	}
	/**
	 * @return bool
	 */
	public static function isConversionPermitted()
	{
		return self::getConversionRestriction()->hasPermission();
	}
	/**
	 * @return bool
	 */
	public static function isDuplicateControlPermitted()
	{
		return self::getDuplicateControlRestriction()->hasPermission();
	}
	/**
	 * @return bool
	 */
	public static function isHistoryViewPermitted()
	{
		return self::getHistoryViewRestriction()->hasPermission();
	}
	/**
	 * @return int
	 */
	public static function getDealCategoryLimit()
	{
		return self::getDealCategoryLimitRestriction()->getQuantityLimit();
	}

	/**
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\LoaderException
	 */
	private static function initialize()
	{
		if(self::$isInitialized)
		{
			return;
		}

		Main\Localization\Loc::loadMessages(__FILE__);

		//region SQL
		self::$sqlRestriction = new Bitrix24SqlRestriction('crm_clr_cfg_sql');
		if(!self::$sqlRestriction->load())
		{
			//SQL Limit is Disabled by default
			self::$sqlRestriction->setRowCountThreshold(0);
		}
		//endregion
		//region Conversion
		self::$conversionRestriction = new Bitrix24AccessRestriction(
			'crm_clr_cfg_conv',
			false,
			null,
			array(
				'ID' => 'limit_crm_quote_to_deal_invoice',
				'TITLE' => GetMessage('CRM_RESTR_MGR_POPUP_TITLE'),
				'CONTENT' => GetMessage('CRM_RESTR_MGR_POPUP_CONTENT_2')
			)
		);
		if(!self::$conversionRestriction->load())
		{
			self::$conversionRestriction->permit(
				Bitrix24Manager::isFeatureEnabled('crm_entity_conversion')
			);
		}
		//endregion
		//region Duplicate Control
		self::$dupControlRestriction = new Bitrix24AccessRestriction(
			'crm_clr_cfg_dup_ctrl',
			false,
			array(
				'ID' => 'crm_duplicate_control',
				'CONTENT' => GetMessage('CRM_RESTR_MGR_DUP_CTRL_MSG_CONTENT_2')
			),
			array(
				'ID' => 'limit_crm_duplicates_search',
				'TITLE' => GetMessage('CRM_RESTR_MGR_POPUP_TITLE'),
				'CONTENT' => GetMessage('CRM_RESTR_MGR_POPUP_CONTENT_2')
			)
		);

		if(!self::$dupControlRestriction->load())
		{
			self::$dupControlRestriction->permit(
				Bitrix24Manager::isFeatureEnabled('crm_duplicate_control')
			);
		}
		//endregion
		//region History View
		self::$historyViewRestriction = new Bitrix24AccessRestriction(
			'crm_clr_cfg_hx',
			false,
			array(
				'ID' => 'crm_history_view',
				'CONTENT' => GetMessage('CRM_RESTR_MGR_HX_VIEW_MSG_CONTENT_2')
			),
			array(
				'ID' => 'limit_crm_history_view',
				'TITLE' => GetMessage('CRM_RESTR_MGR_POPUP_TITLE'),
				'CONTENT' => GetMessage('CRM_RESTR_MGR_POPUP_CONTENT_2')
			)
		);

		if(!self::$historyViewRestriction->load())
		{
			self::$historyViewRestriction->permit(
				Bitrix24Manager::isFeatureEnabled("crm_history_view")
			);
		}
		//endregion
		//region Entity Limit

		self::$searchLimitRestriction = new Bitrix24SearchLimitRestriction(
			'crm_clr_cfg_entity_search_limit',
			0
		);

		if(!self::$searchLimitRestriction->load())
		{
			$entityLimit = Bitrix24Manager::getVariable('crm_entity_search_limit');
			if(is_numeric($entityLimit))
			{
				self::$searchLimitRestriction->setQuantityLimit(
					max((int)$entityLimit, 0)
				);
			}
		}

		//endregion
		//region Deal Category Limit
		self::$dealCategoryLimitRestriction = new DealCategoryLimitRestriction();
		//endregion

		//region Attribute configurator
		self::$attributeConfigRestriction = new Bitrix24AccessRestriction(
			'crm_clr_cfg_attr_configurator',
			false,
			null,
			array(
				'ID' => 'limit_crm_field_stage_required',
				'TITLE' => GetMessage('CRM_RESTR_MGR_POPUP_TITLE'),
				'CONTENT' => GetMessage('CRM_RESTR_MGR_CONDITIONALLY_REQUIRED_FIELD_POPUP_CONTENT_2')
			)
		);

		if(!self::$attributeConfigRestriction->load())
		{
			self::$attributeConfigRestriction->permit(
				Bitrix24Manager::isFeatureEnabled('crm_attr_configurator')
			);
		}
		//endregion

		//region Permission
		self::$permissionControlRestriction = new Bitrix24AccessRestriction(
			'crm_clr_cfg_permission',
			true,
			null,
			array(
				'ID' => 'limit_crm_access_permissions',
				'TITLE' => GetMessage('CRM_RESTR_MGR_PERMISSION_CONTROL_POPUP_TITLE'),
				'CONTENT' => GetMessage('CRM_RESTR_MGR_PERMISSION_CONTROL_POPUP_CONTENT_2')
			)
		);

		if(!self::$permissionControlRestriction->load())
		{
			self::$permissionControlRestriction->permit(
				!Bitrix24Manager::isEnabled()
				|| Main\Config\Option::get('crm', 'crm_enable_permission_control', 'Y', '') === 'Y'
			);
		}
		//endregion

		//region Deal Recurring
		self::$dealRecurringRestriction = new Bitrix24AccessRestriction(
			'crm_clr_cfg_deal_recurring',
			true,
			null,
			array(
				'ID' => 'limit_crm_deal_regularly',
				'TITLE' => GetMessage('CRM_RESTR_MGR_DEAL_RECURRING_POPUP_TITLE'),
				'CONTENT' => GetMessage('CRM_RESTR_MGR_DEAL_RECURRING_POPUP_CONTENT')
			)
		);

		if(!self::$dealRecurringRestriction->load())
		{
			self::$dealRecurringRestriction->permit(
				Bitrix24Manager::isFeatureEnabled("crm_deal_recurring")
				|| Main\Config\Option::get('crm', 'recurring_deal_enabled', 'N') === 'Y'
			);
		}
		//endregion

		//region Invoice Recurring
		self::$invoiceRecurringRestriction = new Bitrix24AccessRestriction(
			'crm_clr_cfg_invoice_recurring',
			true,
			null,
			array(
				'ID' => 'limit_crm_invoice_regularly',
				'TITLE' => GetMessage('CRM_RESTR_MGR_INVOICE_RECURRING_POPUP_TITLE'),
				'CONTENT' => GetMessage('CRM_RESTR_MGR_INVOICE_RECURRING_POPUP_CONTENT')
			)
		);

		if(!self::$invoiceRecurringRestriction->load())
		{
			self::$invoiceRecurringRestriction->permit(
				Bitrix24Manager::isFeatureEnabled("crm_invoice_recurring")
			);
		}
		//endregion

		//region Details search
		self::$detailsSearchByInnRestriction = new Bitrix24AccessRestriction(
			'crm_clr_cfg_details_search_by_inn',
			true,
			null,
			array(
				'ID' => 'limit_crm_details_search_by_inn'
			)
		);

		if(!self::$detailsSearchByInnRestriction->load())
		{
			self::$detailsSearchByInnRestriction->permit(
				Bitrix24Manager::isFeatureEnabled("crm_details_search_by_inn")
			);
		}

		self::$detailsSearchByEdrpouRestriction = new Bitrix24AccessRestriction(
			'crm_clr_cfg_details_search_by_edrpou',
			true,
			null,
			array(
				'ID' => 'limit_crm_details_search_by_edrpou'
			)
		);

		if(!self::$detailsSearchByEdrpouRestriction->load())
		{
			self::$detailsSearchByEdrpouRestriction->permit(
				Bitrix24Manager::isFeatureEnabled("crm_details_search_by_edrpou")
			);
		}
		//endregion

		//region UfAccessRightRestriction
		self::$ufAccessRightsRestriction = new Bitrix24AccessRestriction(
			'crm_cfg_uf_access_rights',
			true,
			null,
			[
				'ID' => 'limit_crm_fields_visible_to_selected_users'
			]
		);

		if(!self::$ufAccessRightsRestriction->load())
		{
			self::$ufAccessRightsRestriction->permit(
				Bitrix24Manager::isFeatureEnabled('crm_uf_access_rights')
			);
		}
		//endregion

		self::$generatorRestriction = new Bitrix24AccessRestriction(
			'crm_generator', false, [],
			['ID' => 'limit_crm_marketing_sales_generator']
		);
		if (!self::$generatorRestriction->load())
		{
			self::$generatorRestriction->permit(
				Bitrix24Manager::isFeatureEnabled('sender_rc')
			);
		}

		self::$webformRestriction = new Bitrix24AccessRestriction(
			'crm_generator', false, [],
			[
				'ID' => 'limit_crm_forms_powered_by',
				'TITLE' => GetMessage('CRM_WEBFORM_EDIT_POPUP_LIMITED_TITLE'),
				'CONTENT' => GetMessage('CRM_WEBFORM_EDIT_POPUP_LIMITED_TEXT')
			]);
		self::$webformLimitRestriction = new Bitrix24QuantityRestriction(
			'crm_webform_activation',
			false,
			null,
			array(
				'ID' => 'limit_crm_sales_funnels',
				'TITLE' => GetMessage('CRM_WEBFORM_EDIT_POPUP_LIMITED_TITLE'),
				'CONTENT' => GetMessage(
					'CRM_WEBFORM_LIST_POPUP_LIMITED_TEXT',
					array('#COUNT#' => \Bitrix\Crm\WebForm\Form::getMaxActivatedFormLimit())
				)
			)
		);

		if(!self::$webformLimitRestriction->load())
		{
			self::$webformLimitRestriction->setQuantityLimit(\Bitrix\Crm\WebForm\Form::getMaxActivatedFormLimit());
		}

		self::$imconnectorRestriction = new Bitrix24AccessRestriction(
			'crm_imconnector', false, [],
			[
				'ID' => 'limit_contact_center_ol_number',
				'TITLE' => GetMessage('CRM_WEBFORM_EDIT_POPUP_LIMITED_TITLE'),
				'CONTENT' => GetMessage('CRM_BUTTON_EDIT_OPENLINE_MULTI_POPUP_LIMITED_TEXT')
			]);

		self::$callListRestriction = new Bitrix24AccessRestriction('call-list-limit-popup', false, [], ['ID' => 'limit_crm_dialer']);

		self::$callTrackerRestriction = new Bitrix24AccessRestriction(
			'crm_phone_tracker',
			false,
			[],
			[
				'ID' => 'crm_phone_tracker',
				'TITLE' => '',
				'CONTENT' => ''
			]
		);
		if(!self::$callTrackerRestriction->load())
		{
			self::$callTrackerRestriction->permit(
				Bitrix24Manager::isFeatureEnabled("crm_phone_tracker")
			);
		}

		self::$isInitialized = true;
	}

	private static function initializeAddressRestriction()
	{
		if (self::$addressSearchRestriction === null)
		{
			//region Address search
			self::$addressSearchRestriction = new Bitrix24AccessRestriction(
				'crm_address_search',
				false,
				null,
				['ID' => 'limit_crm_google_map']
			);
			if(!self::$addressSearchRestriction->load())
			{
				self::$addressSearchRestriction->permit(
					Main\Loader::includeModule('location')
					&& !\Bitrix\Location\Service\AddressService::getInstance()->isLimitReached()
				);
			}
			//endregion
		}
	}

	private static function initializeDiskQuotaRestriction()
	{
		if (self::$diskQuotaRestriction === null)
		{
			//region Disk quota
			self::$diskQuotaRestriction = new Bitrix24AccessRestriction(
				'crm_disk_quota',
				false,
				null,
				['ID' => 'limit_office_storage']
			);
			if(!self::$diskQuotaRestriction->load())
			{
				$permitted = !Main\Loader::includeModule('bitrix24') ||
					((int)Main\Config\Option::get("main", "disk_space", 0) <= 0);

				if (!$permitted)
				{
					$quota = new \CDiskQuota();
					$permitted = $quota->checkDiskQuota(['size' => 0]);
					if (!$permitted)
					{
						//@codingStandardsIgnoreStart
						self::$diskQuotaRestriction->setErrorMessage((string)$quota->LAST_ERROR);
						//@codingStandardsIgnoreEnd
					}
				}
				self::$diskQuotaRestriction->permit($permitted);
			}
			//endregion
		}
	}

	public static function getDynamicTypesLimitRestriction(): DynamicTypesLimit
	{
		if (!static::$dynamicTypesLimit)
		{
			static::$dynamicTypesLimit = new DynamicTypesLimit(
				new DynamicTypesQuantityRestriction(),
			);
		}

		return static::$dynamicTypesLimit;
	}

	final public static function getAutomatedSolutionLimitRestriction(): AutomatedSolutionLimit
	{
		static $restriction = new AutomatedSolutionLimit();

		return $restriction;
	}

	public static function getLeadsRestriction(): Bitrix24AccessRestriction
	{
		if (self::$leadsRestriction === null)
		{
			self::$leadsRestriction = new Bitrix24AccessRestriction(
				'crm_leads',
				false,
				null,
				['ID' => 'limit_crm_lead_unlimited']
			);
			if(!self::$leadsRestriction->load())
			{
				self::$leadsRestriction->permit(
					Bitrix24Manager::isFeatureEnabled('crm_leads')
				);
			}
		}

		return self::$leadsRestriction;
	}

	public static function getQuotesRestriction(): Bitrix24AccessRestriction
	{
		if (self::$quotesRestriction === null)
		{
			self::$quotesRestriction = new Bitrix24AccessRestriction(
				'crm_quotes',
				false,
				null,
				['ID' => 'limit_crm_commercial_offers']
			);
			if(!self::$quotesRestriction->load())
			{
				self::$quotesRestriction->permit(
					Bitrix24Manager::isFeatureEnabled('crm_quotes')
				);
			}
		}

		return self::$quotesRestriction;
	}

	public static function getOrderRestriction(): OrderRestriction
	{
		if (self::$orderRestriction === null)
		{
			self::$orderRestriction = new OrderRestriction(
				'shop_orders',
				false,
				null
			);
		}

		return self::$orderRestriction;
	}

	public static function getInventoryControlIntegrationRestriction(): Bitrix24AccessRestriction
	{
		if (self::$inventoryControlIntegrationRestriction === null)
		{
			self::$inventoryControlIntegrationRestriction = new Bitrix24AccessRestriction(
				'crm_inventory_management_integration',
				false,
				null,
				['ID' => 'limit_store_crm_integration']
			);
			if(!self::$inventoryControlIntegrationRestriction->load())
			{
				self::$inventoryControlIntegrationRestriction->permit(
					Bitrix24Manager::isFeatureEnabled('crm_inventory_management_integration')
				);
			}
		}

		return self::$inventoryControlIntegrationRestriction;
	}

	public static function getInventoryControl1cRestriction(): Bitrix24AccessRestriction
	{
		if (self::$inventoryControl1cRestriction === null)
		{
			self::$inventoryControl1cRestriction = new Bitrix24AccessRestriction(
				'catalog_inventory_management_1c',
				false,
				null,
				['ID' => 'limit_crm_1c_inventory_control']
			);

			if(!self::$inventoryControl1cRestriction->load())
			{
				self::$inventoryControl1cRestriction->permit(
					Bitrix24Manager::isFeatureEnabled('catalog_inventory_management_1c')
				);
			}
		}

		return self::$inventoryControl1cRestriction;
	}

	public static function getDealClientFieldsRestriction(): ClientFieldsRestriction
	{
		if (!static::$clientFieldsRestriction)
		{
			static::$clientFieldsRestriction = new ClientFieldsRestriction(\CCrmOwnerType::Deal);
		}

		return static::$clientFieldsRestriction;
	}

	public static function getObserversFieldRestriction(int $entityTypeId): ObserversFieldRestriction
	{
		if (!isset(static::$observersFieldRestrictionList[$entityTypeId]))
		{
			static::$observersFieldRestrictionList[$entityTypeId] = new ObserversFieldRestriction($entityTypeId);
		}

		return static::$observersFieldRestrictionList[$entityTypeId];
	}

	public static function getActivityFieldRestriction(): ActivityFieldRestriction
	{
		if (!static::$activityFieldRestriction)
		{
			static::$activityFieldRestriction = new ActivityFieldRestriction();
		}

		return static::$activityFieldRestriction;
	}

	public static function getObserversRestriction(): Bitrix24AccessRestriction
	{
		if (self::$observersRestriction === null)
		{
			self::$observersRestriction = new Bitrix24AccessRestriction(
				'crm_observers_card_deal',
				false,
				null,
				['ID' => 'limit_crm_observers_card_deal']
			);
			if(!self::$observersRestriction->load())
			{
				self::$observersRestriction->permit(
					Bitrix24Manager::isFeatureEnabled('crm_observers_card_deal')
				);
			}
		}

		return self::$observersRestriction;
	}
	public static function getContactExportRestriction(): Bitrix24AccessRestriction
	{
		if (!static::$contactExportRestriction)
		{
			static::$contactExportRestriction = new Bitrix24AccessRestriction(
				'crm_contacts',
				false,
				null,
				['ID' => 'limit_crm_free_export_contacts']
			);
			if(!static::$contactExportRestriction->load())
			{
				static::$contactExportRestriction->permit(
					Bitrix24Manager::isFeatureEnabled('crm_contacts_export')
				);
			}
		}

		return static::$contactExportRestriction;
	}

	public static function getIntegrationShopRestriction(): IntegrationShopRestriction
	{
		if (!static::$integrationShopRestriction)
		{
			static::$integrationShopRestriction = new IntegrationShopRestriction();
		}

		return static::$integrationShopRestriction;
	}

	public static function getUserFieldAddRestriction(): UserFieldAddRestriction
	{
		static $restriction;
		if (!$restriction)
		{
			$restriction = new UserFieldAddRestriction();
		}

		return $restriction;
	}

	public static function getResourceBookingRestriction(): Bitrix24AccessRestriction
	{
		static $restriction;

		if (!$restriction)
		{
			$restriction = new Bitrix24AccessRestriction(
				'calendar_resourcebooking_limit',
				false,
				null,
				['ID' => 'limit_crm_booking']
			);
			if(!$restriction->load())
			{
				$permitted = true;
				if (Loader::includeModule('calendar'))
				{
					$limit = \Bitrix\Calendar\UserField\ResourceBooking::getBitrx24Limitation();
					$permitted = ($limit !== 0); // Creating resourcebooking is restricted only if $limit = 0
				}
				$restriction->permit($permitted);
			}
		}

		return $restriction;
	}

	public static function getChatInDetailsRestriction(): Bitrix24AccessRestriction
	{
		if (is_null(static::$chatInDetailsRestriction))
		{
			static::$chatInDetailsRestriction = new Bitrix24AccessRestriction(
				'crm_chat_in_details_card',
				false,
				null,
				[
					'ID' => 'limit_crm_chat_card_crm',
				],
			);

			if (!static::$chatInDetailsRestriction->load())
			{
				static::$chatInDetailsRestriction->permit(
					Bitrix24Manager::isFeatureEnabled('crm_chat_in_details_card')
				);
			}
		}

		return static::$chatInDetailsRestriction;
	}

	public static function getVisitRestriction(): Bitrix24AccessRestriction
	{
		if (is_null(static::$visitRestriction))
		{
			static::$visitRestriction = new Bitrix24AccessRestriction(
				'crm_visit_tracker',
				false,
				null,
				[
					// intentional typo 'vizit treker'. We were provided with this exact code and can not change it
					'ID' => 'limit_crm_vizit_treker',
				],
			);

			if (!static::$visitRestriction->load())
			{
				static::$visitRestriction->permit(
					Bitrix24Manager::isFeatureEnabled('crm_visit_tracker')
				);
			}
		}

		return static::$visitRestriction;
	}

	public static function getCalendarSharingRestriction(): Bitrix24AccessRestriction
	{
		if (is_null(static::$calendarSharingRestriction))
		{
			static::$calendarSharingRestriction = new Bitrix24AccessRestriction(
				'crm_event_sharing',
				false,
				null,
				[
					'ID' => 'limit_crm_calendar_free_slots'
				],
			);

			if (!static::$calendarSharingRestriction->load())
			{
				static::$calendarSharingRestriction->permit(
					Bitrix24Manager::isFeatureEnabled('crm_event_sharing')
				);
			}
		}

		return static::$calendarSharingRestriction;
	}

	public static function getActivityRestriction(
		int $activityTypeId,
		string $providerTypeId = ''
	): Bitrix24AccessRestriction
	{
		if ($activityTypeId === \CCrmActivityType::Provider)
		{
			if (empty($providerTypeId))
			{
				throw new Main\ArgumentException(
					'providerTypeId is required if activityTypeId is \CCrmActivityType::Provider',
					'providerTypeId',
				);
			}

			if ($providerTypeId === Visit::PROVIDER_ID)
			{
				return static::getVisitRestriction();
			}
		}

		return new Bitrix24AccessRestriction('', true);
	}

	/**
	 * Return specific for type and item restriction, if detail page view is restricted.
	 *
	 * @param int $entityTypeId
	 * @param int $entityId
	 * @return Bitrix24AccessRestriction
	 */
	public static function getItemDetailPageRestriction(int $entityTypeId, int $entityId = 0): Bitrix24AccessRestriction
	{
		if ($entityTypeId === \CCrmOwnerType::Invoice || $entityTypeId === \CCrmOwnerType::SmartInvoice)
		{
			return static::getInvoicesRestriction();
		}
		if ($entityTypeId === \CCrmOwnerType::Lead)
		{
			return static::getLeadsRestriction();
		}
		if ($entityTypeId === \CCrmOwnerType::Quote)
		{
			return static::getQuotesRestriction();
		}
		if ($entityId > 0 && $entityTypeId === \CCrmOwnerType::Order)
		{
			$orderRestriction = static::getOrderRestriction();

			if ($orderRestriction->isItemRestricted(new ItemIdentifier($entityTypeId, $entityId)))
			{
				return $orderRestriction;
			}
		}
		if (
			$entityId > 0
			&& (
				$entityTypeId === \CCrmOwnerType::Deal
				|| \CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId)
			)
		)
		{
			if ($entityTypeId === \CCrmOwnerType::Deal)
			{
				$orderRestriction = static::getOrderRestriction();

				if ($orderRestriction->isItemRestricted(new ItemIdentifier($entityTypeId, $entityId)))
				{
					return $orderRestriction;
				}
			}

			$webFormRestriction = static::getWebFormResultsRestriction();

			if ($webFormRestriction->isItemRestricted(new ItemIdentifier($entityTypeId, $entityId)))
			{
				return new Bitrix24AccessRestriction(
					$webFormRestriction->getName(),
					false,
					null,
					[
						'ID' => WebFormResultsRestriction::SLIDER_ID,
					]
				);
			}
		}

		return new Bitrix24AccessRestriction('', true);
	}

	public static function getAddOperationRestriction(int $entityTypeId): Bitrix24AccessRestriction
	{
		$commonRestriction = static::getCommonOperationRestriction($entityTypeId);
		if (!$commonRestriction->hasPermission())
		{
			return $commonRestriction;
		}

		$dynamicTypesRestriction = static::getDynamicTypesLimitRestriction();
		if ($dynamicTypesRestriction->isCreateItemRestricted($entityTypeId))
		{
			$restriction = new Bitrix24AccessRestriction(
				$dynamicTypesRestriction::FEATURE_NAME,
				false,
			);
			$error = $dynamicTypesRestriction->getCreateItemRestrictedError();
			$restriction->setErrorMessage($error->getMessage());
			$restriction->setErrorCode($error->getCode());

			return $restriction;
		}

		return new Bitrix24AccessRestriction('', true);
	}

	private static function getCommonOperationRestriction(int $entityTypeId): Bitrix24AccessRestriction
	{
		$diskQuotaRestriction = static::getDiskQuotaRestriction();
		if (!$diskQuotaRestriction->hasPermission())
		{
			return $diskQuotaRestriction;
		}

		if ($entityTypeId === \CCrmOwnerType::Lead)
		{
			return static::getLeadsRestriction();
		}
		if ($entityTypeId === \CCrmOwnerType::Quote)
		{
			return static::getQuotesRestriction();
		}
		if ($entityTypeId === \CCrmOwnerType::Invoice || $entityTypeId === \CCrmOwnerType::SmartInvoice)
		{
			return static::getInvoicesRestriction();
		}

		return new Bitrix24AccessRestriction('', true);
	}

	public static function getUpdateOperationRestriction(ItemIdentifier $identifier): Bitrix24AccessRestriction
	{
		$entityTypeId = $identifier->getEntityTypeId();

		$commonRestriction = static::getCommonOperationRestriction($entityTypeId);
		if (!$commonRestriction->hasPermission())
		{
			return $commonRestriction;
		}

		$dynamicTypesRestriction = static::getDynamicTypesLimitRestriction();
		if ($dynamicTypesRestriction->isUpdateItemRestricted($entityTypeId))
		{
			$restriction = new Bitrix24AccessRestriction(
				$dynamicTypesRestriction::FEATURE_NAME,
				false,
			);
			$error = $dynamicTypesRestriction->getUpdateItemRestrictedError();
			$restriction->setErrorMessage($error->getMessage());
			$restriction->setErrorCode($error->getCode());

			return $restriction;
		}

		$webForResultsRestriction = static::getWebFormResultsRestriction();
		if (
			static::isWebFormResultsRestrictionCanBeAppliedTo($entityTypeId)
			&& $webForResultsRestriction->isItemRestricted($identifier)
		)
		{
			$restriction = new Bitrix24AccessRestriction(
				$webForResultsRestriction->getName(),
				false,
				null,
				[
					'ID' => WebFormResultsRestriction::SLIDER_ID,
				]
			);
			Container::getInstance()->getLocalization()->loadMessages();
			$restriction->setErrorMessage((string)Main\Localization\Loc::getMessage('CRM_FEATURE_RESTRICTION_ERROR'));

			return $restriction;
		}

		return new Bitrix24AccessRestriction('', true);
	}

	public static function isWebFormResultsRestrictionCanBeAppliedTo(int $entityTypeId): bool
	{
		return ($entityTypeId === \CCrmOwnerType::Deal || \CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId));
	}

	/**
	 * Returns object that represents restriction to items created by crm forms above limit
	 *
	 * @return WebFormResultsRestriction
	 */
	public static function getWebFormResultsRestriction(): WebFormResultsRestriction
	{
		if (!static::$webFormResultsRestriction)
		{
			static::$webFormResultsRestriction = new WebFormResultsRestriction(
				'crm_form_results_count',
				false,
				null,
				[
					'ID' => WebFormResultsRestriction::SLIDER_ID,
				]
			);
			if(!static::$webFormResultsRestriction->load())
			{
				$resultsLimit = Bitrix24Manager::getVariable('crm_form_results_count');
				$startDateVariable = Bitrix24Manager::getVariable('crm_form_results_start_date');
				if ($resultsLimit > 0 && !empty($startDateVariable))
				{
					$defaultStartDate = '2021-09-01';
					try
					{
						$startDate = new Date($startDateVariable, 'Y-m-d');
					}
					catch (Main\ObjectException $e)
					{
						$startDate = new Date($defaultStartDate, 'Y-m-d');
					}

					static::$webFormResultsRestriction->setResultsLimit($resultsLimit, $startDate);
				}
				else
				{
					static::$webFormResultsRestriction->permit(true);
				}
			}
		}

		return static::$webFormResultsRestriction;
	}

	public static function getInvoicesRestriction(): Bitrix24AccessRestriction
	{
		if (self::$invoicesRestriction === null)
		{
			self::$invoicesRestriction = new Bitrix24AccessRestriction(
				'crm_invoices',
				false,
				null,
				['ID' => 'limit_crm_free_invoices']
			);
			if(!self::$invoicesRestriction->load())
			{
				Container::getInstance()->getLocalization()->loadMessages();
				self::$invoicesRestriction->setErrorMessage((string)Main\Localization\Loc::getMessage('CRM_FEATURE_RESTRICTION_ERROR'));
				self::$invoicesRestriction->permit(
					Bitrix24Manager::isFeatureEnabled('crm_invoices')
				);
			}
		}
		return self::$invoicesRestriction;
	}

	public static function getReportRestriction(): Bitrix24AccessRestriction
	{
		static $restriction;

		if (!$restriction)
		{
			$restriction = new Bitrix24AccessRestriction(
				'crm_report',
				false,
				null,
				['ID' => 'limit_crm_tasks_constructor_reports']
			);
			if(!$restriction->load())
			{
				$restriction->permit(
					Bitrix24Manager::isFeatureEnabled('report')
					&& Bitrix24Manager::isFeatureEnabled('crm_report')
				);
			}
		}

		return $restriction;
	}

	/**
	 * Return type specific items list restriction, if the page view is restricted.
	 *
	 * @param int $entityTypeId
	 * @return Bitrix24AccessRestriction
	 */
	public static function getItemListRestriction(int $entityTypeId): Bitrix24AccessRestriction
	{
		if ($entityTypeId === \CCrmOwnerType::Lead)
		{
			return static::getLeadsRestriction();
		}
		if ($entityTypeId === \CCrmOwnerType::Quote)
		{
			return static::getQuotesRestriction();
		}
		if ($entityTypeId === \CCrmOwnerType::Invoice || $entityTypeId === \CCrmOwnerType::SmartInvoice)
		{
			return static::getInvoicesRestriction();
		}

		return new Bitrix24AccessRestriction('', true);
	}

	public static function getTaskRestriction(): Bitrix24AccessRestriction
	{
		if (is_null(static::$taskRestriction))
		{
			static::$taskRestriction = new Bitrix24AccessRestriction(
				'tasks_crm_integration',
				false,
				null,
				[
					'ID' => 'limit_tasks_crm_integration',
				],
			);

			if (!static::$taskRestriction->load())
			{
				static::$taskRestriction->permit(
					Bitrix24Manager::isFeatureEnabled('tasks_crm_integration')
				);
			}
		}

		return static::$taskRestriction;
	}
}
