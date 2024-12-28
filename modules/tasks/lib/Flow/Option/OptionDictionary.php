<?php

namespace Bitrix\Tasks\Flow\Option;

enum OptionDictionary: string
{
	case MANUAL_DISTRIBUTOR_ID = 'manual_distributor_id';
	case RESPONSIBLE_QUEUE_LATEST_ID = 'responsible_queue_latest_id';
	case RESPONSIBLE_CAN_CHANGE_DEADLINE = 'responsible_can_change_deadline';
	case MATCH_WORK_TIME = 'match_work_time';
	case MATCH_SCHEDULE = 'match_schedule';
	case NOTIFY_AT_HALF_TIME = 'notify_at_half_time';
	case NOTIFY_ON_QUEUE_OVERFLOW = 'notify_on_queue_overflow';
	case NOTIFY_ON_TASKS_IN_PROGRESS_OVERFLOW = 'notify_on_tasks_in_progress_overflow';
	case NOTIFY_WHEN_EFFICIENCY_DECREASES = 'notify_when_efficiency_decreases';

	case TASK_CONTROL = 'task_control';
	case NOTIFY_WHEN_TASK_NOT_TAKEN = 'notify_when_task_not_taken';
}