<?php

namespace Bitrix\Crm\Badge\Type;

use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Badge\ValueItem;
use Bitrix\Crm\Badge\ValueItemOptions;
use Bitrix\Main\Localization\Loc;

final class MailMessageDeliveryStatus extends Badge
{
	public const MAIL_MESSAGE_DELIVERY_ERROR_VALUE = 'message_delivery_error';

	public function getFieldName(): string
	{
		return Loc::getMessage('CRM_BADGE_MAIL_MESSAGE_STATUS_FIELD_NAME') ?? '';
	}

	public function getValuesMap(): array
	{
		return [
			new ValueItem(
				self::MAIL_MESSAGE_DELIVERY_ERROR_VALUE,
				Loc::getMessage('CRM_BADGE_MAIL_MESSAGE_DELIVERY_ERROR'),
				ValueItemOptions::TEXT_COLOR_FAILURE,
				ValueItemOptions::BG_COLOR_FAILURE
			),
		];
	}

	public function getType(): string
	{
		return parent::MAIL_MESSAGE_DELIVERY_STATUS_TYPE;
	}
}