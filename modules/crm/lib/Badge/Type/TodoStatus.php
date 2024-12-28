<?php

namespace Bitrix\Crm\Badge\Type;

use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Badge\ValueItem;
use Bitrix\Crm\Badge\ValueItemOptions;
use Bitrix\Main\Localization\Loc;

class TodoStatus extends Badge
{
	protected const TYPE = 'todo_status';

	public const OVERLAP_EVENT_VALUE = 'overlap_event';

	public function getFieldName(): string
	{
		return Loc::getMessage('CRM_BADGE_TODO_STATUS_FIELD_NAME');
	}

	public function getValuesMap(): array
	{
		return [
			new ValueItem(
				self::OVERLAP_EVENT_VALUE,
				Loc::getMessage('CRM_BADGE_TODO_STATUS_TEXT_VALUE_OVERLAP_EVENT'),
				ValueItemOptions::TEXT_COLOR_PRIMARY,
				ValueItemOptions::BG_COLOR_PRIMARY
			),
		];
	}

	public function getType(): string
	{
		return self::TYPE;
	}
}