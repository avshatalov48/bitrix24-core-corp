<?php
namespace Bitrix\Timeman\Service\Worktime\Violation;

use Bitrix\Timeman\Model\Schedule\Violation\ViolationRules;

class WorktimeViolation
{
	# fixed schedule
	const TYPE_LATE_START = 'LATE_START';
	const TYPE_EARLY_START = 'EARLY_START';
	const TYPE_EARLY_ENDING = 'EARLY_ENDING';
	const TYPE_LATE_ENDING = 'LATE_ENDING';

	const TYPE_MIN_DAY_DURATION = 'MIN_DAY_DURATION';

	const TYPE_EDITED_BREAK_LENGTH = 'EDITED_BREAK_LENGTH';
	const TYPE_EDITED_START = 'EDITED_START';
	const TYPE_EDITED_ENDING = 'EDITED_STOP';

	const TYPE_TIME_LACK_FOR_PERIOD = 'TIME_FOR_PERIOD';

	# shifted schedule
	const TYPE_MISSED_SHIFT = 'MISSED_SHIFT';

	const TYPE_SHIFT_LATE_START = 'SHIFT_LATE_START';

	public $type;
	public $recordedTimeValue;
	public $violatedSeconds;
	public $userId;
	/** @var ViolationRules */
	public $violationRules;

	public function isManuallyChangedTime()
	{
		return in_array($this->type, [static::TYPE_EDITED_BREAK_LENGTH, static::TYPE_EDITED_ENDING, static::TYPE_EDITED_START], true);
	}
}