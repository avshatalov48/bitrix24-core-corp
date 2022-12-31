<?php
namespace Bitrix\Crm\Settings;
use Bitrix\Main;
class ActivitySettings
{
	const VIEW_LIST = 1;

	const UNDEFINED = 0;
	const KEEP_COMPLETED_CALLS = 1;
	const KEEP_COMPLETED_MEETINGS = 2;
	const KEEP_UNBOUND_TASKS = 3;
	const KEEP_REASSIGNED_CALLS = 4;
	const KEEP_REASSIGNED_MEETINGS = 5;
	const MARK_FORWARDED_EMAIL_AS_OUTGOING = 6;
	const USE_OUTDATED_CALENDAR_ACTIVITIES = 7;

	/** @var ActivitySettings  */
	private static $current = null;
	/** @var bool */
	private static $messagesLoaded = false;
	/** @var array */
	private static $descriptions = null;
	/** @var IntegerSetting */
	private $defaultListView = null;
	/** @var IntegerSetting */
	private $outgoingEmailOwnerType = null;
	/** @var BooleanSetting */
	private $enableDeadlineSync = null;

	public function __construct()
	{
		$this->defaultListView = new IntegerSetting('activity_default_list_view', self::VIEW_LIST);
		$this->outgoingEmailOwnerType = new IntegerSetting('activity_outgoing_email_owner_type', \CCrmOwnerType::Contact);
		$this->enableDeadlineSync = new BooleanSetting('activity_enable_deadline_sync', true);
	}

	/**
	 * Get current instance
	 * @return ActivitySettings
	 */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new ActivitySettings();
		}
		return self::$current;
	}

	public static function isDefined($ID)
	{
		$ID = (int)$ID;
		return $ID > self::UNDEFINED && $ID <= self::MARK_FORWARDED_EMAIL_AS_OUTGOING;
	}

	public static function getValue($ID)
	{
		$ID = (int)$ID;
		if($ID === self::KEEP_COMPLETED_CALLS)
		{
			return Main\Config\Option::get('crm', 'act_cal_show_compl_call', 'Y', '') === 'Y';
		}
		elseif($ID === self::KEEP_COMPLETED_MEETINGS)
		{
			return Main\Config\Option::get('crm', 'act_cal_show_compl_meeting', 'Y', '') === 'Y';
		}
		elseif($ID === self::KEEP_UNBOUND_TASKS)
		{
			return Main\Config\Option::get('crm', 'act_task_keep_unbound', 'Y', '') === 'Y';
		}
		elseif($ID === self::KEEP_REASSIGNED_CALLS)
		{
			return Main\Config\Option::get('crm', 'act_cal_keep_reassign_call', 'Y', '') === 'Y';
		}
		elseif($ID === self::KEEP_REASSIGNED_MEETINGS)
		{
			return Main\Config\Option::get('crm', 'act_cal_keep_reassign_meeting', 'Y', '') === 'Y';
		}
		elseif($ID === self::MARK_FORWARDED_EMAIL_AS_OUTGOING)
		{
			return Main\Config\Option::get('crm', 'act_mark_fwd_emai_outgoing', 'N', '') === 'Y';
		}
		elseif($ID === self::USE_OUTDATED_CALENDAR_ACTIVITIES)
		{
			return Main\Config\Option::get('crm', 'use_outdated_calendar_activities', 'N', '') === 'Y';
		}
		else
		{
			throw new Main\NotSupportedException("The setting '{$ID}' is not supported in current context");
		}
	}

	public static function setValue($ID, $value)
	{
		$ID = (int)$ID;
		if($ID === self::KEEP_COMPLETED_CALLS)
		{
			Main\Config\Option::set('crm', 'act_cal_show_compl_call', $value ? 'Y' : 'N', '');
		}
		elseif($ID === self::KEEP_COMPLETED_MEETINGS)
		{
			Main\Config\Option::set('crm', 'act_cal_show_compl_meeting', $value ? 'Y' : 'N', '');
		}
		elseif($ID === self::KEEP_UNBOUND_TASKS)
		{
			Main\Config\Option::set('crm', 'act_task_keep_unbound', $value ? 'Y' : 'N', '');
		}
		elseif($ID === self::KEEP_REASSIGNED_CALLS)
		{
			Main\Config\Option::set('crm', 'act_cal_keep_reassign_call', $value ? 'Y' : 'N', '');
		}
		elseif($ID === self::KEEP_REASSIGNED_MEETINGS)
		{
			Main\Config\Option::set('crm', 'act_cal_keep_reassign_meeting', $value ? 'Y' : 'N', '');
		}
		elseif($ID === self::MARK_FORWARDED_EMAIL_AS_OUTGOING)
		{
			Main\Config\Option::set('crm', 'act_mark_fwd_emai_outgoing', $value ? 'Y' : 'N', '');
		}
		elseif($ID === self::USE_OUTDATED_CALENDAR_ACTIVITIES)
		{
			Main\Config\Option::set('crm', 'use_outdated_calendar_activities', $value ? 'Y' : 'N', '');
		}
		else
		{
			throw new Main\NotSupportedException("The setting '{$ID}' is not supported in current context");
		}
	}
	/**
	 * Return true if deletion to recycle bin is enabled.
	 * @return bool
	 */
	public function isRecycleBinEnabled()
	{
		return true;
		//return $this->enableRecycleBin->get();
	}
	/**
	 * Enable deletion to recycle bin.
	 * @param bool $enabled Enabled Flag.
	 * @return void
	 */
	public function enableRecycleBin($enabled)
	{
		//$this->enableRecycleBin->set($enabled);
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

	public function getOutgoingEmailOwnerTypeId()
	{
		return $this->outgoingEmailOwnerType->get();
	}

	public function setOutgoingEmailOwnerTypeId($ownerTypeId)
	{
		$this->outgoingEmailOwnerType->set($ownerTypeId);
	}
	/**
	 * Check if synchronization of the 'DEADLINE' field is enabled.
	 * If enabled DEADLINE' field will be updated then activity mark as completed.
	 * @return bool
	 */
	public function isDeadlineSyncEnabled()
	{
		return $this->enableDeadlineSync->get();
	}

	/**
	 * Enable synchronization of the 'DEADLINE' field
	 * @param bool $enabled Enabled Flag.
	 * @return void
	 */
	public function enableDeadlineSync($enabled)
	{
		$this->enableDeadlineSync->set($enabled);
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
				self::VIEW_LIST => GetMessage('CRM_ACTIVITY_SETTINGS_VIEW_LIST'),
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

	public static function areOutdatedCalendarActivitiesEnabled(): bool
	{
		if (!Crm::isUniversalActivityScenarioEnabled())
		{
			return true;
		}

		return (bool)self::getValue(self::USE_OUTDATED_CALENDAR_ACTIVITIES) ;
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