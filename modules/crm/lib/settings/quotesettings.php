<?php
namespace Bitrix\Crm\Settings;
use Bitrix\Main;
class QuoteSettings
{
	const VIEW_LIST = EntityViewSettings::LIST_VIEW;
	const VIEW_KANBAN = EntityViewSettings::KANBAN_VIEW;

	/** @var QuoteSettings  */
	private static $current = null;
	/** @var BooleanSetting  */
	private $enableViewEvent = null;
	/** @var BooleanSetting  */
	private $isOpened = null;
	/** @var IntegerSetting */
	private $defaultListView = null;
	/** @var EntityViewSettings */
	private $entityListView = null;

	function __construct()
	{
		$this->defaultListView = new IntegerSetting('quote_default_list_view', self::VIEW_KANBAN);
		$this->isOpened = new BooleanSetting('quote_opened_flag', true);
		$this->enableViewEvent = new BooleanSetting('quote_enable_view_event', true);
	}
	/**
	 * Get current instance
	 * @return QuoteSettings
	 */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new QuoteSettings();
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
	 * Get current list view ID
	 * @return int
	 */
	public function getCurrentListViewID()
	{
		if($this->entityListView === null)
		{
			$this->entityListView = new EntityViewSettings();
		}

		$viewID = $this->entityListView->getViewID(\CCrmOwnerType::Quote);
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
}