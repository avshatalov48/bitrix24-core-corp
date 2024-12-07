<?php

namespace Bitrix\Tasks\Util;

use CTaskListState;

enum View: string
{
	public const STATE_PARAMETER  = 'F_STATE';

	case LIST = 'sV80';
	case KANBAN = 'sVo0';
	case TIMELINE = 'sV100';
	case PLAN = 'sV180';
	case CALENDAR = 'sV1o0';
	case GANTT = 'sVg0';

	public static function fromState(int $state): self
	{
		return match ($state)
		{
			CTaskListState::VIEW_MODE_KANBAN => self::KANBAN,
			CTaskListState::VIEW_MODE_TIMELINE => self::TIMELINE,
			CTaskListState::VIEW_MODE_PLAN => self::PLAN,
			CTaskListState::VIEW_MODE_CALENDAR => self::CALENDAR,
			CTaskListState::VIEW_MODE_GANTT => self::GANTT,
			default => self::LIST,
		};
	}

	public function view(): string
	{
		return $this->value;
	}
}