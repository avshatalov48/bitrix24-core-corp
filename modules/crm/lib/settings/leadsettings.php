<?php
namespace Bitrix\Crm\Settings;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Crm\Activity;
use Bitrix\Crm\Restriction\RestrictionManager;

class LeadSettings
{
	use Traits\EnableFactory;

	const VIEW_LIST = EntityViewSettings::LIST_VIEW;
	const VIEW_KANBAN = EntityViewSettings::KANBAN_VIEW;
	const VIEW_CALENDAR = EntityViewSettings::CALENDAR_VIEW;

	public const CRM_TYPE_MENU_ITEM_ID = 'crm-type-button';

	/** @var LeadSettings  */
	private static $current = null;
	/** @var bool */
	private static $messagesLoaded = false;
	/** @var array */
	private static $descriptions = null;
	/** @var BooleanSetting  */
	private $isOpened = null;
	/** @var IntegerSetting */
	private $defaultListView = null;
	/** @var EntityViewSettings */
	private $entityListView = null;
	/** @var BooleanSetting */
	private $enableProductRowExport = null;
	/** @var BooleanSetting */
	private $enableAutoGenRc = null;
	/** @var BooleanSetting */
	private $enableAutoUsingFinishedLead = null;
	/** @var ArraySetting */
	private $activityCompletionConfig = null;
	/** @var ArraySetting */
	private $freeModeConverterConfig = null;
	/** @var BooleanSetting  */
	private $enableDeferredCleaning = null;
	/** @var BooleanSetting  */
	private $enableRecycleBin = null;

	function __construct()
	{
		$this->defaultListView = new IntegerSetting('lead_default_list_view', self::VIEW_KANBAN);
		$this->isOpened = new BooleanSetting('lead_opened_flag', true);
		$this->enableProductRowExport = new BooleanSetting('enable_lead_prod_row_export', true);
		$this->enableAutoGenRc = new BooleanSetting('enable_auto_gen_rc', true);
		$this->enableAutoUsingFinishedLead = new BooleanSetting('enable_auto_using_finished_lead', false);
		$this->enableDeferredCleaning = new BooleanSetting('enable_lead_deferred_cleaning', true);
		$this->enableRecycleBin = new BooleanSetting('enable_lead_recycle_bin', true);
		$this->initIsFactoryEnabledSetting(\CCrmOwnerType::Lead);

		$completionConfig = array();
		foreach(Activity\Provider\ProviderManager::getCompletableProviderList() as $providerInfo)
		{
			$completionConfig[$providerInfo['ID']] = true;
		}
		$this->activityCompletionConfig = new ArraySetting('lead_act_completion_cfg', $completionConfig);
		$this->freeModeConverterConfig = new ArraySetting(
			'lead_free_mode_converter_cfg',
			[
				'items' => [\CCrmOwnerType::Deal, \CCrmOwnerType::Contact],
				'dealCategoryId' => 0,
				'completeActivities' => false
			]
		);
	}
	/**
	 * Get current instance
	 * @return LeadSettings
	 */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new LeadSettings();
		}
		return self::$current;
	}
	/**
	 * Get value of flag 'OPENED'
	 * @return bool
	 */
	public function getOpenedFlag()
	{
		return $this->isOpened->get();
	}
	/**
	 * Set value of flag 'OPENED'
	 * @param bool $opened Opened Flag.
	 * @return void
	 */
	public function setOpenedFlag($opened)
	{
		$this->isOpened->set($opened);
	}
	/**
	 * Check if export of the product rows is enabled
	 * @return bool
	 */
	public function isProductRowExportEnabled()
	{
		return $this->enableProductRowExport->get();
	}
	/**
	 * Enable export of the product rows
	 * @param bool $enabled Enabled Flag.
	 * @return void
	 */
	public function enableProductRowExport($enabled)
	{
		$this->enableProductRowExport->set($enabled);
	}
	/**
	 * Return true if auto generate of RC(return customer) is enabled.
	 * @return bool
	 */
	public function isAutoGenRcEnabled()
	{
		return $this->enableAutoGenRc->get();
	}
	/**
	 * Enable auto generate of RC(return customer).
	 * @param bool $enabled Enabled Flag.
	 * @return void
	 */
	public function enableAutoGenRc($enabled)
	{
		$this->enableAutoGenRc->set($enabled);
	}
	/**
	 * Return true if using finished lead for auto generating is enabled.
	 * @return bool
	 */
	public function isAutoUsingFinishedLeadEnabled()
	{
		return $this->enableAutoUsingFinishedLead->get();
	}
	/**
	 * Enable using finished lead for auto generating.
	 * @param bool $enabled Enabled Flag.
	 * @return void
	 */
	public function enableAutoUsingFinishedLead($enabled)
	{
		$this->enableAutoUsingFinishedLead->set($enabled);
	}
	public function getActivityCompletionConfig()
	{
		return $this->activityCompletionConfig->get();
	}
	public function setActivityCompletionConfig(array $config)
	{
		$this->activityCompletionConfig->set($config);
	}
	public function resetActivityCompletionConfig()
	{
		$this->activityCompletionConfig->remove();
	}
	public function getFreeModeConverterConfig()
	{
		return $this->freeModeConverterConfig->get();
	}
	public function setFreeModeConverterConfig(array $config)
	{
		$this->freeModeConverterConfig->set($config);
	}
	/**
	 * Return true if deferred cleaning of related entities during deletion operation is enabled.
	 * @return bool
	 */
	public function isDeferredCleaningEnabled()
	{
		return $this->enableDeferredCleaning->get();
	}
	/**
	 * Enable enable deferred cleaning of related entities during deletion operation.
	 * @param bool $enabled Enabled Flag.
	 * @return void
	 */
	public function enableDeferredCleaning($enabled)
	{
		$this->enableDeferredCleaning->set($enabled);
	}
	/**
	 * Return true if deletion to recycle bin is enabled.
	 * @return bool
	 */
	public function isRecycleBinEnabled()
	{
		return $this->enableRecycleBin->get();
	}
	/**
	 * Enable deletion to recycle bin.
	 * @param bool $enabled Enabled Flag.
	 * @return void
	 */
	public function enableRecycleBin($enabled)
	{
		$this->enableRecycleBin->set($enabled);
	}
	/**
	 * Get current list view ID
	 * @return int
	 */
	public function getCurrentListViewID()
	{
		if($this->entityListView === null)
		{
			$this->entityListView = new EntityViewSettings();
		}

		$viewID = $this->entityListView->getViewID(\CCrmOwnerType::Lead);
		if($viewID === EntityViewSettings::UNDEFINED)
		{
			$viewID = $this->getDefaultListViewID();
		}
		return $viewID;
	}
	/**
	 * Get default list view ID
	 * @return int
	 */
	public function getDefaultListViewID()
	{
		return $this->defaultListView->get();
	}
	/**
	 * Set default list view ID
	 * @param int $viewID View ID.
	 * @return void
	 */
	public function setDefaultListViewID($viewID)
	{
		$this->defaultListView->set($viewID);
	}
	/**
	 * Get descriptions of views supported in current context
	 * @return array
	 */
	public static function getViewDescriptions()
	{
		if(!self::$descriptions)
		{
			self::includeModuleFile();

			self::$descriptions= array(
				self::VIEW_LIST => GetMessage('CRM_COMMON_LIST'),
				self::VIEW_KANBAN => GetMessage('CRM_LEAD_SETTINGS_VIEW_KANBAN')
			);
		}
		return self::$descriptions;
	}
	/**
	 * Prepare list items for view selector
	 * @return array
	 */
	public static function prepareViewListItems()
	{
		return \CCrmEnumeration::PrepareListItems(self::getViewDescriptions());
	}
	/**
	 * Enable leads
	 * @param bool $enabled Enabled Flag.
	 * @return bool
	 */
	public static function enableLead($enabled)
	{
		$currentValue = \Bitrix\Main\Config\Option::get('crm', 'crm_lead_enabled', "");
		if ($currentValue === ($enabled ? "Y" : "N"))
			return true;

		$enabled = (bool)$enabled;
		if ($enabled)
		{
			ConversionSettings::getCurrent()->enableAutocreation(true);
		}

		\Bitrix\Main\Config\Option::set('crm', 'crm_lead_enabled', $enabled ? "Y" : "N");

		//clear menu settings
		\CUserOptions::DeleteOptionsByName("ui", "crm_control_panel_menu");
		if (\Bitrix\Main\Loader::includeModule("intranet"))
		{
			\CIntranetUtils::clearMenuCache();
		}

		return true;
	}
	/**
	 * Check if leads are enabled
	 * @return bool
	 */
	public static function isEnabled()
	{
		if (!RestrictionManager::getLeadsRestriction()->hasPermission())
		{
			return false;
		}

		$isEnabled = \Bitrix\Main\Config\Option::get('crm', 'crm_lead_enabled', "Y");
		return $isEnabled == "Y";
	}

	public static function getCrmTypeMenuItem(bool $compatibleKeys = false): array
	{
		$result = [
			'id' => self::CRM_TYPE_MENU_ITEM_ID,
			'text' => Main\Localization\Loc::getMessage('CRM_LEAD_SETTINGS_CRM_TYPE_MENU_ITEM'),
			'title' => Main\Localization\Loc::getMessage('CRM_LEAD_SETTINGS_CRM_TYPE_MENU_ITEM'),
			'onclick' => self::showCrmTypePopup(),
		];

		if ($compatibleKeys)
		{
			$result = array_change_key_case($result, CASE_UPPER);
		}

		return $result;
	}

	public static function showCrmTypePopup()
	{
		\CJSCore::Init(array('popup', 'sidepanel'));

		$isCrmAdmin = "N";
		$CrmPerms = \CCrmPerms::GetCurrentUserPermissions();
		if ($CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			$isCrmAdmin = "Y";
		}

		$arParams = array(
			"ajaxPath" => "/bitrix/tools/crm_lead_mode.php",
			"dealPath" => (\Bitrix\Crm\Settings\DealSettings::getCurrent()->getCurrentListViewID() == \Bitrix\Crm\Settings\DealSettings::VIEW_KANBAN)
				? SITE_DIR."crm/deal/kanban/" : \CCrmOwnerType::GetListUrl(\CCrmOwnerType::Deal),
			"leadPath" => (\Bitrix\Crm\Settings\LeadSettings::getCurrent()->getCurrentListViewID() == self::VIEW_KANBAN)
				? SITE_DIR."crm/lead/kanban/" : \CCrmOwnerType::GetListUrl(\CCrmOwnerType::Lead),
			"isAdmin" => $isCrmAdmin,
			"isLeadEnabled" => self::isEnabled() ? "Y" : "N",
			"messages" => array(
				"CRM_TYPE_TITLE" => GetMessage("CRM_TYPE_TITLE"),
				"CRM_TYPE_SAVE" => GetMessage("CRM_TYPE_SAVE"),
				"CRM_TYPE_CANCEL" => GetMessage("CRM_TYPE_CANCEL"),
				"CRM_TYPE_TURN_ON" => GetMessage("CRM_TYPE_TURN_ON"),
				"CRM_LEAD_CONVERT_TITLE" => GetMessage("CRM_LEAD_CONVERT_TITLE"),
				"CRM_LEAD_CONVERT_TEXT" => GetMessage("CRM_LEAD_CONVERT_TEXT"),
				"CRM_TYPE_CONTINUE" => GetMessage("CRM_TYPE_CONTINUE"),
				"CRM_LEAD_BATCH_CONVERSION_STATE" => GetMessage("CRM_LEAD_BATCH_CONVERSION_STATE"),
				"CRM_LEAD_BATCH_CONVERSION_TITLE" => GetMessage("CRM_LEAD_BATCH_CONVERSION_TITLE"),
				"CRM_LEAD_BATCH_CONVERSION_COMPLETED" => GetMessage("CRM_LEAD_BATCH_CONVERSION_COMPLETED"),
				"CRM_LEAD_BATCH_CONVERSION_COUNT_SUCCEEDED" => GetMessage("CRM_LEAD_BATCH_CONVERSION_COUNT_SUCCEEDED"),
				"CRM_LEAD_BATCH_CONVERSION_COUNT_FAILED" => GetMessage("CRM_LEAD_BATCH_CONVERSION_COUNT_FAILED"),
				"CRM_LEAD_BATCH_CONVERSION_NO_NAME" => GetMessage("CRM_LEAD_BATCH_CONVERSION_NO_NAME")
			)
		);

		return "BX.CrmLeadMode.init(".\CUtil::PhpToJSObject($arParams)."); BX.CrmLeadMode.preparePopup();";
	}
	/**
	 * Include language file
	 * @return void
	 */
	protected static function includeModuleFile()
	{
		Container::getInstance()->getLocalization()->loadMessages();
		if(self::$messagesLoaded)
		{
			return;
		}

		Main\Localization\Loc::loadMessages(__FILE__);
		self::$messagesLoaded = true;
	}
}
