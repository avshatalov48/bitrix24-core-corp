<?php

namespace Bitrix\Crm\Badge\Type;

use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Badge\ValueItem;
use Bitrix\Crm\Badge\ValueItemOptions;
use Bitrix\Main\Localization\Loc;

class PaymentStatus extends Badge
{
	protected const TYPE = 'payment_status';

	public const MISSED_CALL_VALUE = 'complete_payment';

	public function getFieldName(): string
	{
		return Loc::getMessage('CRM_BADGE_PAYMENT_STATUS_FIELD_NAME');
	}

	public function getValuesMap(): array
	{
		return [
			new ValueItem(
				self::MISSED_CALL_VALUE,
				Loc::getMessage('CRM_BADGE_PAYMENT_STATUS_COMPLETE_PAYMENT_VALUE'),
				ValueItemOptions::TEXT_COLOR_SUCCESS,
				ValueItemOptions::BG_COLOR_SUCCESS
			),
		];
	}

	public function getType(): string
	{
		return self::TYPE;
	}
}
