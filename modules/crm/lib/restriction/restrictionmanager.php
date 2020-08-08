<?php
namespace Bitrix\Crm\Restriction;
use Bitrix\Main;
use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Crm\Category\DealCategory;

class RestrictionManager
{
	const SQL_ROW_COUNT_THRESHOLD = 5000;
	/** @var bool */
	private static $isInitialized = null;
	/** @var Bitrix24SqlRestriction|null */
	private static $sqlRestriction = null;
	/** @var Bitrix24AccessRestriction|null  */
	private static $conversionRestriction = null;
	/** @var Bitrix24AccessRestriction|null  */
	private static $dupControlRestriction = null;
	/** @var Bitrix24AccessRestriction|null  */
	private static $historyViewRestriction = null;
	/** @var Bitrix24SearchLimitRestriction|null  */
	private static $searchLimitRestriction = null;
	/** @var Bitrix24QuantityRestriction|null  */
	private static $dealCategoryLimitRestriction = null;
	/** @var Bitrix24AccessRestriction|null  */
	private static $attributeConfigRestriction = null;
	/** @var Bitrix24AccessRestriction|null  */
	private static $permissionControlRestriction = null;
	/** @var Bitrix24AccessRestriction|null  */
	private static $dealRecurringRestriction = null;
	/** @var Bitrix24AccessRestriction|null  */
	private static $invoiceRecurringRestriction = null;
	/** @var Bitrix24AccessRestriction|null  */
	private static $detailsSearchByInnRestriction = null;
	/** @var Bitrix24AccessRestriction|null  */
	private static $detailsSearchByEdrpouRestriction = null;
	/** @var Bitrix24AccessRestriction|null  */
	private static $automationRestriction = null;
	/** @var Bitrix24AccessRestriction|null  */
	private static $generatorRestriction = null;
	/** @var Bitrix24AccessRestriction|null  */
	private static $webformRestriction = null;
	/** @var Bitrix24QuantityRestriction|null  */
	private static $webformLimitRestriction = null;
	/** @var Bitrix24AccessRestriction|null  */
	private static $imconnectorRestriction = null;
	/** @var Bitrix24AccessRestriction|null  */
	private static $callListRestriction = null;
	/** @var Bitrix24AccessRestriction|null  */
	private static $addressSearchRestriction = null;

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
		self::initialize();
		return self::$automationRestriction;
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
	* @return void
	*/
	public static function reset()
	{
		self::initialize();
		self::initializeAddressRestriction();

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
		self::$automationRestriction->reset();
		self::$generatorRestriction->reset();
		self::$webformRestriction->reset();
		self::$webformLimitRestriction->reset();
		self::$imconnectorRestriction->reset();
		self::$callListRestriction->reset();
		self::$addressSearchRestriction->reset();

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
		self::$automationRestriction = null;
		self::$generatorRestriction = null;
		self::$webformRestriction = null;
		self::$webformLimitRestriction = null;
		self::$imconnectorRestriction = null;
		self::$callListRestriction = null;
		self::$addressSearchRestriction = null;

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
				'ID' => 'limit_crm_history',
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
		self::$dealCategoryLimitRestriction = new Bitrix24QuantityRestriction(
			'crm_clr_cfg_deal_category',
			false,
			null,
			array(
				'ID' => 'limit_crm_sales_funnels',
				'TITLE' => GetMessage('CRM_RESTR_MGR_DEAL_CATEGORY_POPUP_TITLE'),
				'CONTENT' => GetMessage(
					'CRM_RESTR_MGR_DEAL_CATEGORY_POPUP_CONTENT_2',
					array('#TEAM_CRM_FUNNEL#' => 10)
				)
			)
		);

		if(!self::$dealCategoryLimitRestriction->load())
		{
			self::$dealCategoryLimitRestriction->setQuantityLimit(Bitrix24Manager::getDealCategoryCount());
		}
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

		self::$automationRestriction = new Bitrix24AccessRestriction(
			'crm_automation_deal',
			false,
			[],
			[
				'ID' => 'limit_crm_robots',
				'TITLE' => GetMessage('CRM_ST_ROBOTS_POPUP_TITLE'),
				'CONTENT' => GetMessage('CRM_ST_ROBOTS_POPUP_TEXT')
			]
		);

		self::$generatorRestriction = new Bitrix24AccessRestriction('crm_generator', false, [], ['ID' => 'limit_crm_marketing_sales_generator']);

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

	public static function onDealCategoryLimitChange(Main\Event $event)
	{
		DealCategory::applyMaximumLimitRestrictions(Bitrix24Manager::getDealCategoryCount());
	}

	public static function onMigrateToBox()
	{
		Main\Config\Option::delete('crm', array('name' => 'crm_enable_permission_control'));
		Main\Config\Option::delete('crm', array('name' => 'recurring_deal_enabled'));
	}
}