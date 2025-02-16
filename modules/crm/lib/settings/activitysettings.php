<?php
namespace Bitrix\Crm\Settings;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Feature;

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
	public const ENABLE_CREATE_CALENDAR_EVENT_FOR_CALL = 8;
	public const ENABLE_CALENDAR_EVENTS_SETTINGS = 9;
    const REMOVE_ENTITY_BADGES_INTERVAL_DAYS = 30;
	const REMOVE_DAYS_30 = 30;
	const REMOVE_DAYS_60 = 60;
	const REMOVE_DAYS_90 = 90;

	public const CHECK_BINDINGS_BEFORE_AUTO_COMPLETE = 15;

	/** @var ActivitySettings  */
	private static $current = null;
	/** @var bool */
	private static $messagesLoaded = false;
	/** @var array */
	private static $descriptions = null;
	private static ?array $BadgeTtlValues = null;
	/** @var IntegerSetting */
	private $defaultListView = null;
	/** @var IntegerSetting */
	private $outgoingEmailOwnerType = null;
	/** @var BooleanSetting */
	private $enableDeadlineSync = null;
	private $enableUnconnectedRecipients = null;
	private IntegerSetting $deleteEntityBadgesIntervalDays;

	public function __construct()
	{
		$this->defaultListView = new IntegerSetting('activity_default_list_view', self::VIEW_LIST);
		$this->outgoingEmailOwnerType = new IntegerSetting('activity_outgoing_email_owner_type', \CCrmOwnerType::Contact);
		$this->enableDeadlineSync = new BooleanSetting('activity_enable_deadline_sync', true);
		$this->enableUnconnectedRecipients = new BooleanSetting('activity_enable_unconnected_recipients', true);
		$this->deleteEntityBadgesIntervalDays = new IntegerSetting(
			'activity_remove_entity_badges_interval_days',
			self::REMOVE_DAYS_30,
		);
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
		elseif ($ID === self::ENABLE_CREATE_CALENDAR_EVENT_FOR_CALL)
		{
			return
				self::getValue(self::ENABLE_CALENDAR_EVENTS_SETTINGS)
				&& Main\Config\Option::get('crm', 'is_create_calendar_event_for_call', 'N', '') === 'Y'
			;
		}
		elseif ($ID === self::ENABLE_CALENDAR_EVENTS_SETTINGS)
		{
			$optionValue = Main\Config\Option::get('crm', 'is_enable_calendar_events_settings', null);
			if ($optionValue === null)
			{
				$isEnableCalendarEventsSettings = Crm::isPortalCreatedBefore(strtotime('2024-06-01'));
				self::setValue(self::ENABLE_CALENDAR_EVENTS_SETTINGS, $isEnableCalendarEventsSettings);

				return $isEnableCalendarEventsSettings;
			}

			return $optionValue === 'Y';
		}
		elseif ($ID === self::CHECK_BINDINGS_BEFORE_AUTO_COMPLETE)
		{
			return Main\Config\Option::get('crm', 'check_bindings_before_auto_complete', 'N', '') === 'Y';
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
		elseif ($ID === self::ENABLE_CREATE_CALENDAR_EVENT_FOR_CALL)
		{
			Main\Config\Option::set('crm', 'is_create_calendar_event_for_call', $value ? 'Y' : 'N', '');
		}
		elseif ($ID === self::ENABLE_CALENDAR_EVENTS_SETTINGS)
		{
			Main\Config\Option::set('crm', 'is_enable_calendar_events_settings', $value ? 'Y' : 'N', '');
		}
		elseif ($ID === self::CHECK_BINDINGS_BEFORE_AUTO_COMPLETE)
		{
			Main\Config\Option::set('crm', 'check_bindings_before_auto_complete', $value ? 'Y' : 'N', '');
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

	public function setEnableUnconnectedRecipients(bool $enabled): void
	{
		$this->enableUnconnectedRecipients->set($enabled);
	}

	public function getEnableUnconnectedRecipients(): bool
	{
		return $this->enableUnconnectedRecipients->get();
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
		return Feature::enabled(Feature\OutdatedMeetingsAndCalls::class);
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

	public function getRemoveEntityBadgesIntervalDays(): int
	{
		return $this->deleteEntityBadgesIntervalDays->get();
	}

	public function setRemoveEntityBadgesIntervalDays(int $interval): void
	{
		if($interval <= 0)
		{
			$interval = self::REMOVE_ENTITY_BADGES_INTERVAL_DAYS;
		}

		$this->deleteEntityBadgesIntervalDays->set($interval);
	}

	public static function getBadgeTtlValues(): array
	{
		if (!self::$BadgeTtlValues)
		{
			self::includeModuleFile();

			self::$BadgeTtlValues = [
				self::REMOVE_DAYS_30 => Loc::getMessage('CRM_BADGE_TTL_SETTINGS_QUANTITY_DAYS_REMOVE', ['#DAYS#' => 30]),
				self::REMOVE_DAYS_60 => Loc::getMessage('CRM_BADGE_TTL_SETTINGS_QUANTITY_DAYS_REMOVE', ['#DAYS#' => 60]),
				self::REMOVE_DAYS_90 => Loc::getMessage('CRM_BADGE_TTL_SETTINGS_QUANTITY_DAYS_REMOVE', ['#DAYS#' => 90]),
			];
		}

		return self::$BadgeTtlValues;
	}
}
