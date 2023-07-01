<?php
namespace Bitrix\Crm\Settings;

use Bitrix\Crm\Conversion\ConversionManager;
use Bitrix\Crm\Conversion\EntityConversionConfig;
use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\ModuleManager;

class InvoiceSettings
{
	use Traits\UseNumberInTitlePlaceholder;

	const VIEW_LIST = EntityViewSettings::LIST_VIEW;
	const VIEW_KANBAN = EntityViewSettings::KANBAN_VIEW;

	/** @var LeadSettings  */
	private static $current = null;
	/** @var bool */
	private static $messagesLoaded = false;
	/** @var array */
	private static $descriptions = null;
	/** @var BooleanSetting  */
	private $isOpened = null;
	private $isOldInvoicesEnabled;
	private $isShowInvoiceTransitionNotice;
	/** @var BooleanSetting  */
	private $isEnableSign = null;
	/** @var IntegerSetting */
	private $defaultListView = null;
	/** @var EntityViewSettings */
	private $entityListView = null;

	function __construct()
	{
		$this->defaultListView = new IntegerSetting('invoice_default_list_view', self::VIEW_KANBAN);
		$this->isOpened = new BooleanSetting('invoice_opened_flag', true);
		$this->isEnableSign = new BooleanSetting('invoice_enable_public_b24_sign', true);
		$this->isShowInvoiceTransitionNotice = new BooleanSetting('invoice_show_transition_notice', true);
		$this->isOldInvoicesEnabled = new BooleanSetting('old_invoice_enable', true);
		$this->initIsUseNumberInTitlePlaceholderSettings(\CCrmOwnerType::SmartInvoice);
	}
	/**
	 * Get current instance
	 * @return InvoiceSettings
	 */
	public static function getCurrent(): InvoiceSettings
	{
		if(self::$current === null)
		{
			self::$current = new InvoiceSettings();
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
	 * Check the possibility to disable a sign
	 * @return bool
	 */
	public static function allowDisableSign()
	{
		return (!(ModuleManager::isModuleInstalled('bitrix24')) || Bitrix24Manager::isPaidAccount());
	}
	/**
	 * Get value of flag 'ENABLED_PUBLIC_B24_SIGN'
	 * @return bool
	 */
	public function getEnableSignFlag()
	{
		return $this->isEnableSign->get();
	}
	/**
	 * Set value of flag 'ENABLED_PUBLIC_B24_SIGN'
	 * @param bool $enabled Opened Flag.
	 * @return void
	 */
	public function setEnableSignFlag($enabled)
	{
		$this->isEnableSign->set($enabled);
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

		$viewID = $this->entityListView->getViewID(\CCrmOwnerType::Invoice);
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
				self::VIEW_KANBAN => GetMessage('CRM_INVOICE_SETTINGS_VIEW_KANBAN')
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

	public function isSmartInvoiceEnabled(): bool
	{
		return Container::getInstance()->getTypeByEntityTypeId(\CCrmOwnerType::SmartInvoice) !== null;
	}

	public function isOldInvoicesEnabled(): bool
	{
		return $this->isOldInvoicesEnabled->get();
	}

	public function setOldInvoicesEnabled(bool $isEnabled): void
	{
		$isEnabledPrevious = $this->isOldInvoicesEnabled();
		$this->isOldInvoicesEnabled->set($isEnabled);

		if ($isEnabledPrevious !== $isEnabled)
		{
			$this->onAfterChangeIsOldInvoicesEnabled();
		}
	}

	private function onAfterChangeIsOldInvoicesEnabled(): void
	{
		foreach (ConversionManager::getSourceEntityTypeIDs(\CCrmOwnerType::Invoice) as $sourceEntityTypeID)
		{
			EntityConversionConfig::removeByEntityTypeId($sourceEntityTypeID);
		}
	}

	public function isOldInvoicesEnablingPossible(): bool
	{
		return $this->isSmartInvoiceEnabled();
	}

	public function isShowInvoiceTransitionNotice(): bool
	{
		return $this->isShowInvoiceTransitionNotice->get();
	}

	public function setShowInvoiceTransitionNotice(bool $isShowInvoiceTransitionNotice): void
	{
		$this->isShowInvoiceTransitionNotice->set($isShowInvoiceTransitionNotice);
	}
}
