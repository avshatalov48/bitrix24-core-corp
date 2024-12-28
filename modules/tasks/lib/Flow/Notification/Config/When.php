<?php

namespace Bitrix\Tasks\Flow\Notification\Config;

use Bitrix\Tasks\Flow\Notification\Exception\InvalidPayload;
use Bitrix\Tasks\ValueObjectInterface;

class When implements ValueObjectInterface
{
	public const BEFORE_EXPIRE_HALF_TIME = 'beforeExpireHalfTime';
	public const BEFORE_EXPIRE = 'beforeExpire';
	public const SLOW_QUEUE = 'slowQueue';
	public const BUSY_RESPONSIBLE = 'busyResponsible';
	public const SLOW_EFFICIENCY = 'efficiencyLowerThan';
	public const ON_TASK_ADDED = 'onTaskAdded';
	public const ON_EXPIRE = 'onExpire';
	public const FORCED_FLOW_SWITCH_TO_MANUAL_DISTRIBUTION = 'forcedFlowSwitchToManualDistribution';
	public const FORCED_FLOW_SWITCH_TO_MANUAL_DISTRIBUTION_ABSENT = 'forcedFlowSwitchToManualDistributionAbsent';
	public const FORCED_FLOW_MANUAL_DISTRIBUTOR_CHANGE = 'forcedFlowManualDistributorChange';
	public const FORCED_FLOW_MANUAL_DISTRIBUTOR_CHANGE_ABSENT = 'forcedFlowManualDistributorChangeAbsent';
	public const HIMSELF_FLOW_TASK_NOT_TAKEN = 'himselfFlowTaskNotTaken';

	private string $type;
	private int $offset;

	public function __construct(string $type, int $offset = 0)
	{
		if (!$this->isTypeAllowed($type))
		{
			throw new InvalidPayload('When:type must be one of the: ' . json_encode($this->getAllowedTypes()));
		}

		$timeOneYearInSeconds = 31536000;
		if ($offset > $timeOneYearInSeconds)
		{
			throw new InvalidPayload('When:offset is too big');
		}

		$this->type = $type;
		$this->offset = $offset;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getValue(): array
	{
		return ['type' => $this->type, 'offset' => $this->offset];
	}

	private function isTypeAllowed(string $when): bool
	{
		return in_array($when, $this->getAllowedTypes(), true);
	}

	private function getAllowedTypes(): array
	{
		return [
			self::SLOW_EFFICIENCY,
			self::BEFORE_EXPIRE_HALF_TIME,
			self::BEFORE_EXPIRE,
			self::SLOW_QUEUE,
			self::BUSY_RESPONSIBLE,
			self::ON_EXPIRE,
			self::ON_TASK_ADDED,
			self::FORCED_FLOW_SWITCH_TO_MANUAL_DISTRIBUTION,
			self::FORCED_FLOW_SWITCH_TO_MANUAL_DISTRIBUTION_ABSENT,
			self::FORCED_FLOW_MANUAL_DISTRIBUTOR_CHANGE,
			self::FORCED_FLOW_MANUAL_DISTRIBUTOR_CHANGE_ABSENT,
			self::HIMSELF_FLOW_TASK_NOT_TAKEN,
		];
	}
}