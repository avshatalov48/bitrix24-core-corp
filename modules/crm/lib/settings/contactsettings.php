<?php

namespace Bitrix\Crm\Settings;

use Bitrix\Main;

class ContactSettings
{
	use Traits\EnableFactory;

	public const VIEW_LIST = EntityViewSettings::LIST_VIEW;

	/** @var ContactSettings  */
	private static $current;
	/** @var bool */
	private static $messagesLoaded = false;
	/** @var array */
	private static $descriptions;
	/** @var IntegerSetting */
	private $defaultListView;
	/** @var EntityViewSettings */
	private $entityListView;
	/** @var BooleanSetting  */
	private $isOpened;
	/** @var BooleanSetting  */
	private $enableOutmodedRequisites;
	/** @var BooleanSetting  */
	private $enableDeferredCleaning;
	/** @var BooleanSetting  */
	private $enableRecycleBin;

	public function __construct()
	{
		$this->defaultListView = new IntegerSetting('contact_default_list_view', self::VIEW_LIST);
		$this->isOpened = new BooleanSetting('contact_opened_flag', true);
		$this->enableOutmodedRequisites = new BooleanSetting('~CRM_ENABLE_CONTACT_OUTMODED_FIELDS', false);
		$this->enableDeferredCleaning = new BooleanSetting('enable_contact_deferred_cleaning', true);
		$this->enableRecycleBin = new BooleanSetting('enable_contact_recycle_bin', true);
		$this->initIsFactoryEnabledSetting(\CCrmOwnerType::Contact);
	}
	/**
	 * Get current instance
	 * @return ContactSettings
	 */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new ContactSettings();
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
	 * Check if outmoded requisite fields (ADDRESS) are enabled.
	 * @return bool
	 */
	public function areOutmodedRequisitesEnabled()
	{
		return $this->enableOutmodedRequisites->get();
	}
	/**
	 * Enable/disable outmoded requisite fields (ADDRESS)
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

		$viewID = $this->entityListView->getViewID(\CCrmOwnerType::Contact);
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

			self::$descriptions= [
				self::VIEW_LIST => GetMessage('CRM_COMMON_LIST'),
			];
		}
		return self::$descriptions;
	}
	/**
	 * Prepare list items for view selector
	 * @return array
	 */
	public static function prepareViewListItems()
	{
		$viewDescriptions = self::getViewDescriptions();

		return \CCrmEnumeration::PrepareListItems($viewDescriptions);
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
