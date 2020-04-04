<?php
namespace Bitrix\Crm\Settings;

use Bitrix\Main;

class OrderSettings
{
	const VIEW_LIST = 1;
	const VIEW_WIDGET = 2;
	const VIEW_KANBAN = 3;

	/** @var QuoteSettings  */
	private static $current = null;
	/** @var BooleanSetting  */
	private $enableViewEvent = null;
	/** @var BooleanSetting  */
	private $isOpened = null;
	/** @var IntegerSetting */
	private $defaultListView = null;
	/** @var IntegerSetting */
	private $defaultResponsibleId = null;
	/** @var array */
	private static $descriptions = null;
	/** @var bool */
	private static $messagesLoaded = false;
	/** @var EntityViewSettings */
	private $entityListView = null;

	function __construct()
	{
		$this->defaultListView = new IntegerSetting('order_default_list_view', self::VIEW_KANBAN);
		$this->defaultResponsibleId = new IntegerSetting('order_default_responsible_id', 1);
		$this->isOpened = new BooleanSetting('order_opened_flag', true);
		$this->enableViewEvent = new BooleanSetting('order_enable_view_event', true);
	}
	/**
	 * Get current instance
	 * @return OrderSettings
	 */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new OrderSettings();
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
	 * Get default list view ID
	 * @return int
	 */
	public function getDefaultListViewID()
	{
		return $this->defaultListView->get();
	}

	public function getDefaultResponsibleId()
	{
		return $this->defaultResponsibleId->get();
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
	 * @param int $responsibleId Responsible Id.
	 */
	public function setDefaultResponsibleId($responsibleId)
	{
		$this->defaultResponsibleId->set($responsibleId);
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

		$viewID = $this->entityListView->getViewID(\CCrmOwnerType::Order);
		if($viewID === EntityViewSettings::UNDEFINED)
		{
			$viewID = $this->getDefaultListViewID();
		}
		return $viewID;
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
				//self::VIEW_WIDGET => GetMessage('CRM_ORDER_SETTINGS_VIEW_WIDGET'),
				self::VIEW_LIST => GetMessage('CRM_ORDER_SETTINGS_VIEW_LIST'),
				//self::VIEW_KANBAN => GetMessage('CRM_ORDER_SETTINGS_VIEW_KANBAN')
			);
		}
		return self::$descriptions;
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