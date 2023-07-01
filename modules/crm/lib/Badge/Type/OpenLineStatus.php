<?php

namespace Bitrix\Crm\Badge\Type;

use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Badge\ValueItem;
use Bitrix\Crm\Badge\ValueItemOptions;
use Bitrix\Main\Localization\Loc;

class OpenLineStatus extends Badge
{
	public const CHAT_NOT_READ_VALUE = 'not_read_chat';

	protected const TYPE = 'open_line_status';

	public function getFieldName(): string
	{
		return Loc::getMessage('CRM_BADGE_OPEN_LINE_STATUS_FIELD_NAME');
	}

	public function getValuesMap(): array
	{
		return [
			new ValueItem(
				self::CHAT_NOT_READ_VALUE,
				Loc::getMessage('CRM_BADGE__OPEN_LINE_CHAT_NOT_READ'),
				ValueItemOptions::TEXT_COLOR_WARNING,
				ValueItemOptions::BG_COLOR_WARNING
			),
		];
	}

	public function getType(): string
	{
		return self::TYPE;
	}
}
