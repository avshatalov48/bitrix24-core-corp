<?php

declare(strict_types=1);

namespace Bitrix\Crm\Badge\Type;

use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Badge\ValueItem;
use Bitrix\Crm\Badge\ValueItemOptions;
use Bitrix\Main\Localization\Loc;

class BookingStatus extends Badge
{
	public const CONFIRMATION_SEND_NOT_READ = 'confirmation_send_not_read';
	public const CONFIRMATION_OPEN_NOT_CONFIRMED = 'confirmation_open_not_confirme';
	public const DELAY_SEND_NOT_READ = 'delay_send_not_read';
	public const DELAY_OPEN_NOT_CONFIRMED = 'delay_open_not_confirmed';
	public const DELAY_CONFIRMED = 'delay_confirmed';
	public const COMING_SOON = 'coming_soon';
	public const CANCELED_BY_CLIENT = 'canceled_by_client';
	public const NOT_BOOKED_CLIENT = 'not_booked_client';

	protected const TYPE = 'booking_status';

	public function getValuesMap(): array
	{
		return [
			new ValueItem(
				self::CONFIRMATION_SEND_NOT_READ,
				Loc::getMessage('CRM_BADGE_BOOKING_BOOKING_STATUS_CONFIRMATION_SEND_NOT_READ') ?? '',
				ValueItemOptions::TEXT_COLOR_SECONDARY,
				ValueItemOptions::BG_COLOR_SECONDARY,
			),
			new ValueItem(
				self::CONFIRMATION_OPEN_NOT_CONFIRMED,
				Loc::getMessage('CRM_BADGE_BOOKING_BOOKING_STATUS_CONFIRMATION_OPEN_NOT_CONFIRMED') ?? '',
				ValueItemOptions::TEXT_COLOR_WARNING,
				ValueItemOptions::BG_COLOR_WARNING,
			),
			new ValueItem(
				self::DELAY_SEND_NOT_READ,
				Loc::getMessage('CRM_BADGE_BOOKING_BOOKING_STATUS_DELAY_SEND_NOT_READ') ?? '',
				ValueItemOptions::TEXT_COLOR_SECONDARY,
				ValueItemOptions::BG_COLOR_SECONDARY,
			),
			new ValueItem(
				self::DELAY_OPEN_NOT_CONFIRMED,
				Loc::getMessage('CRM_BADGE_BOOKING_BOOKING_STATUS_DELAY_OPEN_NOT_CONFIRMED') ?? '',
				ValueItemOptions::TEXT_COLOR_FAILURE,
				ValueItemOptions::BG_COLOR_FAILURE,
			),
			new ValueItem(
				self::DELAY_CONFIRMED,
				Loc::getMessage('CRM_BADGE_BOOKING_BOOKING_STATUS_DELAY_CONFIRMED') ?? '',
				ValueItemOptions::TEXT_COLOR_SUCCESS,
				ValueItemOptions::BG_COLOR_SUCCESS,
			),
			new ValueItem(
				self::COMING_SOON,
				Loc::getMessage('CRM_BADGE_BOOKING_BOOKING_STATUS_COMING_SOON') ?? '',
				ValueItemOptions::TEXT_COLOR_PRIMARY,
				ValueItemOptions::BG_COLOR_PRIMARY,
			),
			new ValueItem(
				self::CANCELED_BY_CLIENT,
				Loc::getMessage('CRM_BADGE_BOOKING_BOOKING_STATUS_CANCELED_BY_CLIENT') ?? '',
				ValueItemOptions::TEXT_COLOR_FAILURE,
				ValueItemOptions::BG_COLOR_FAILURE,
			),
			new ValueItem(
				self::NOT_BOOKED_CLIENT,
				Loc::getMessage('CRM_BADGE_BOOKING_BOOKING_STATUS_NOT_BOOKED_CLIENT') ?? '',
				ValueItemOptions::TEXT_COLOR_FAILURE,
				ValueItemOptions::BG_COLOR_FAILURE,
			),
		];
	}

	public function getFieldName(): string
	{
		$phrase = match ($this->value)
		{
			self::DELAY_SEND_NOT_READ, self::DELAY_OPEN_NOT_CONFIRMED, self::DELAY_CONFIRMED => 'CRM_BADGE_BOOKING_BOOKING_TITLE_DELAY',
			self::CONFIRMATION_SEND_NOT_READ, self::CONFIRMATION_OPEN_NOT_CONFIRMED => 'CRM_BADGE_BOOKING_BOOKING_TITLE_CONFIRMATION',
			self::COMING_SOON, self::CANCELED_BY_CLIENT, self::NOT_BOOKED_CLIENT => 'CRM_BADGE_BOOKING_BOOKING_TITLE_BOOKING',
		};

		return Loc::getMessage($phrase) ?? '';
	}

	public function getType(): string
	{
		return self::TYPE;
	}
}
