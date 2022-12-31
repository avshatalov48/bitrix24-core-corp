<?php
namespace Bitrix\Crm\Settings;

use Bitrix\Main;

class CompanySettings
{
	use Traits\EnableFactory;

	const VIEW_LIST = EntityViewSettings::LIST_VIEW;

	/** @var CompanySettings  */
	private static $current = null;
	/** @var bool */
	private static $messagesLoaded = false;
	/** @var array */
	private static $descriptions = null;
	/** @var IntegerSetting */
	private $defaultListView = null;
	/** @var EntityViewSettings */
	private $entityListView = null;
	/** @var BooleanSetting  */
	private $isOpened = null;
	/** @var BooleanSetting  */
	private $enableOutmodedRequisites = null;
	/** @var BooleanSetting  */
	private $enableDeferredCleaning = null;
	/** @var BooleanSetting  */
	private $enableRecycleBin = null;

	function __construct()
	{
		$this->defaultListView = new IntegerSetting('company_default_list_view', self::VIEW_LIST);
		$this->isOpened = new BooleanSetting('company_opened_flag', true);
		$this->enableOutmodedRequisites = new BooleanSetting('~CRM_ENABLE_COMPANY_OUTMODED_FIELDS', false);
		$this->enableDeferredCleaning = new BooleanSetting('enable_company_deferred_cleaning', true);
		$this->enableRecycleBin = new BooleanSetting('enable_company_recycle_bin', true);
		$this->initIsFactoryEnabledSetting(\CCrmOwnerType::Company, false);
	}
	/**
	 * Get current instance
	 * @return CompanySettings
	 */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new CompanySettings();
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
	 * Check if outmoded requisite fields (ADDRESS, ADDRESS_LEGAL) are enabled.
	 * @return bool
	 */
	public function areOutmodedRequisitesEnabled()
	{
		return $this->enableOutmodedRequisites->get();
	}
	/**
	 * Enable/disable outmoded requisite fields (ADDRESS, ADDRESS_LEGAL)
	 * @param bool $enabled Enabled Flag.
	 * @return void
	 */
	public function enableOutmodedRequisites($enabled)
	{
		$this->enableOutmodedRequisites->set($enabled);
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

		$viewID = $this->entityListView->getViewID(\CCrmOwnerType::Company);
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
	 * Include language file
	 * @return void
	 */
	protected static function includeModuleFile()
	{
		if(self::$messagesLoaded)
		{
			return;
		}

		Main\Localization\Loc::loadMessages(__FILE__);
		self::$messagesLoaded = true;
	}
}
