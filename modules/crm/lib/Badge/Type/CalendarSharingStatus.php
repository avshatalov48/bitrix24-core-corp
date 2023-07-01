<?php

namespace Bitrix\Crm\Badge\Type;

use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Badge\ValueItem;
use Bitrix\Crm\Badge\ValueItemOptions;
use Bitrix\Main\Localization\Loc;

class CalendarSharingStatus extends Badge
{
	public const SLOTS_NOT_VIEWED = 'slots_not_viewed';
	public const SLOTS_VIEWED = 'slots_viewed';
	public const EVENT_CONFIRMED = 'event_confirmed';
	public const CANCELED_BY_CLIENT = 'canceled_by_client';

	protected const TYPE = 'calendar_sharing_status';

	public function getFieldName(): string
	{
		if ($this->value === self::SLOTS_NOT_VIEWED || $this->value === self::SLOTS_VIEWED)
		{
			return Loc::getMessage('CRM_BADGE_CALENDAR_SHARING_STATUS_FIELD_NAME_SLOTS');
		}
		if ($this->value === self::EVENT_CONFIRMED)
		{
			return Loc::getMessage('CRM_BADGE_CALENDAR_SHARING_STATUS_FIELD_NAME_MEETING_WITH_CLIENT');
		}
		if ($this->value === self::CANCELED_BY_CLIENT)
		{
			return Loc::getMessage('CRM_BADGE_CALENDAR_SHARING_STATUS_FIELD_NAME_MEETING_CANCELED');
		}

		return Loc::getMessage('CRM_BADGE_CALENDAR_SHARING_STATUS_FIELD_NAME_MEETING_WITH_CLIENT');
	}

	public function getValuesMap(): array
	{
		return [
			new ValueItem(
				self::SLOTS_NOT_VIEWED,
				Loc::getMessage('CRM_BADGE_CALENDAR_SHARING_STATUS_NOT_VIEWED_VALUE'),
				ValueItemOptions::TEXT_COLOR_SECONDARY,
				ValueItemOptions::BG_COLOR_SECONDARY
			),
			new ValueItem(
				self::SLOTS_VIEWED,
				Loc::getMessage('CRM_BADGE_CALENDAR_SHARING_STATUS_VIEWED_VALUE'),
				ValueItemOptions::TEXT_COLOR_PRIMARY,
				ValueItemOptions::BG_COLOR_PRIMARY
			),
			new ValueItem(
				self::EVENT_CONFIRMED,
				Loc::getMessage('CRM_BADGE_CALENDAR_SHARING_STATUS_EVENT_CONFIRMED_VALUE'),
				ValueItemOptions::TEXT_COLOR_SUCCESS,
				ValueItemOptions::BG_COLOR_SUCCESS
			),
			new ValueItem(
				self::CANCELED_BY_CLIENT,
				Loc::getMessage('CRM_BADGE_CALENDAR_SHARING_STATUS_CANCELED_BY_CLIENT_VALUE'),
				ValueItemOptions::TEXT_COLOR_FAILURE,
				ValueItemOptions::BG_COLOR_FAILURE
			)
		];
	}

	public function getType(): string
	{
		return self::TYPE;
	}
}