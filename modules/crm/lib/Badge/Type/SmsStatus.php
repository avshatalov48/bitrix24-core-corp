<?php

namespace Bitrix\Crm\Badge\Type;

use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Badge\ValueItem;
use Bitrix\Crm\Badge\ValueItemOptions;
use Bitrix\Main\Localization\Loc;

class SmsStatus extends Badge
{
	public const SENDING_SMS_ERROR_VALUE = 'sending_sms_error';
	public const SENDING_NOTIFICATION_ERROR_VALUE = 'sending_notification_error';

	protected const TYPE = 'sms_status';

	public function getFieldName(): string
	{
		return Loc::getMessage('CRM_BADGE_SMS_STATUS_FIELD_NAME');
	}

	public function getValuesMap(): array
	{
		return [
			new ValueItem(
				self::SENDING_SMS_ERROR_VALUE,
				Loc::getMessage('CRM_BADGE_SMS_SENDING_ERROR'),
				ValueItemOptions::TEXT_COLOR_FAILURE,
				ValueItemOptions::BG_COLOR_FAILURE
			),
			new ValueItem(
				self::SENDING_NOTIFICATION_ERROR_VALUE,
				Loc::getMessage('CRM_BADGE_SMS_SENDING_ERROR'),
				ValueItemOptions::TEXT_COLOR_FAILURE,
				ValueItemOptions::BG_COLOR_FAILURE
			),
		];
	}

	public function getType(): string
	{
		return self::TYPE;
	}
}
