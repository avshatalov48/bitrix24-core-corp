<?php
namespace Bitrix\Crm\Settings;
use Bitrix\Main;
class HistorySettings
{
	/** @var HistorySettings|null  */
	private static $current = null;
	/** @var BooleanSetting|null  */
	private $enableViewEvent = null;
	/** @var BooleanSetting|null  */
	private $enableExportEvent = null;
	/** @var IntegerSetting|null  */
	private $viewEventGroupInterval = null;
	/** @var BooleanSetting|null  */
	private $enableLeadDeletionEvent = null;
	/** @var BooleanSetting|null  */
	private $enableDealDeletionEvent = null;
	/** @var BooleanSetting|null  */
	private $enableQuoteDeletionEvent = null;
	/** @var BooleanSetting|null  */
	private $enableContactDeletionEvent = null;
	/** @var BooleanSetting|null  */
	private $enableCompanyDeletionEvent = null;

	const VIEW_EVENT_GROUPING_INTERVAL = 60;

	function __construct()
	{
		$this->enableExportEvent = new BooleanSetting('history_enable_export_event', true);
		$this->enableViewEvent = new BooleanSetting('history_enable_view_event', true);
		$this->viewEventGroupInterval = new IntegerSetting('history_view_event_group_interval', self::VIEW_EVENT_GROUPING_INTERVAL);
		$this->enableLeadDeletionEvent = new BooleanSetting('history_enable_lead_rm_event', true);
		$this->enableDealDeletionEvent = new BooleanSetting('history_enable_deal_rm_event', true);
		$this->enableQuoteDeletionEvent = new BooleanSetting('history_enable_quote_rm_event', true);
		$this->enableContactDeletionEvent = new BooleanSetting('history_enable_contact_rm_event', true);
		$this->enableCompanyDeletionEvent = new BooleanSetting('history_enable_company_rm_event', true);
	}
	/**
	 * @return HistorySettings
	 */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new HistorySettings();
		}
		return self::$current;
	}
	/**
	 * @return bool
	 */
	public function isExportEventEnabled()
	{
		return $this->enableExportEvent->get();
	}
	/**
	 * @param bool $enabled Enabled Flag
	 * @return void
	 */
	public function enableExportEvent($enabled)
	{
		$this->enableExportEvent->set($enabled);
	}
	/**
	 * @return bool
	 */
	public function isViewEventEnabled()
	{
		return $this->enableViewEvent->get();
	}
	/**
	 * @param bool $enabled Enabled Flag
	 * @return void
	 */
	public function enableViewEvent($enabled)
	{
		$this->enableViewEvent->set($enabled);
	}
	/**
	 * @return int
	 */
	public function getViewEventGroupingInterval()
	{
		return $this->viewEventGroupInterval->get();
	}
	/**
	 * @param int $interval Interval
	 * @return void
	 */
	public function setViewEventGroupingInterval($interval)
	{
		$interval = (int)$interval;
		if($interval <= 0)
		{
			$interval = self::VIEW_EVENT_GROUPING_INTERVAL;
		}
		$this->viewEventGroupInterval->set($interval);
	}
	/**
	 * Check if registration of event for Lead deletion is enabled.
	 * @return bool
	 */
	public function isLeadDeletionEventEnabled()
	{
		return $this->enableLeadDeletionEvent->get();
	}
	/**
	 * Enable registration of event for Lead deletion.
	 * @param bool $enabled Enabled Flag.
	 * @return void
	 */
	public function enableLeadDeletionEvent($enabled)
	{
		$this->enableLeadDeletionEvent->set($enabled);
	}
	/**
	 * Check if registration of event for Deal deletion is enabled.
	 * @return bool
	 */
	public function isDealDeletionEventEnabled()
	{
		return $this->enableDealDeletionEvent->get();
	}
	/**
	 * Enable registration of event for Deal deletion.
	 * @param bool $enabled Enabled Flag.
	 * @return void
	 */
	public function enableDealDeletionEvent($enabled)
	{
		$this->enableDealDeletionEvent->set($enabled);
	}
	/**
	 * Check if registration of event for Quote deletion is enabled.
	 * @return bool
	 */
	public function isQuoteDeletionEventEnabled()
	{
		return $this->enableQuoteDeletionEvent->get();
	}
	/**
	 * Enable registration of event for Quote deletion.
	 * @param bool $enabled Enabled Flag.
	 * @return void
	 */
	public function enableQuoteDeletionEvent($enabled)
	{
		$this->enableQuoteDeletionEvent->set($enabled);
	}
	/**
	 * Check if registration of event for Contact deletion is enabled
	 * @return bool
	 */
	public function isContactDeletionEventEnabled()
	{
		return $this->enableContactDeletionEvent->get();
	}
	/**
	 * Enable registration of event for Contact deletion.
	 * @param bool $enabled Enabled Flag
	 * @return void
	 */
	public function enableContactDeletionEvent($enabled)
	{
		$this->enableContactDeletionEvent->set($enabled);
	}
	/**
	 * Check if registration of event for Company deletion is enabled.
	 * @return bool
	 */
	public function isCompanyDeletionEventEnabled()
	{
		return $this->enableCompanyDeletionEvent->get();
	}
	/**
	 * Enable registration of event for Company deletion.
	 * @param bool $enabled Enabled Flag.
	 * @return void
	 */
	public function enableCompanyDeletionEvent($enabled)
	{
		$this->enableCompanyDeletionEvent->set($enabled);
	}
}