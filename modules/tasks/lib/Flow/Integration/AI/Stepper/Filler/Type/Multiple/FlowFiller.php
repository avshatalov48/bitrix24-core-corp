<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Stepper\Filler\Type\Multiple;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Flow\Integration\AI\Configuration;
use Bitrix\Tasks\Flow\Integration\AI\Result\CollectorResult;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\EntityType;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type\NestedValueNode;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type\ValueNode;
use Bitrix\Tasks\Flow\Integration\AI\Stepper\Filler\AbstractFiller;
use Bitrix\Tasks\Util\User;

class FlowFiller extends AbstractFiller
{
	public function fill(CollectorResult $result): void
	{
		$this->result = $result;

		$this->fillRealEmployeesCount();
	}

	private function fillRealEmployeesCount(): void
	{
		$absences = User::getAbsences(Configuration::getCopilotPeriod(), new DateTime(), ...$this->employees);

		foreach ($this->employees as $employee)
		{
			if (!array_key_exists($employee, $absences))
			{
				$absences[$employee] = [];
			}
		}

		$valuesByUsers = [];
		foreach ($absences as $userId => $periods)
		{
			$count = 0;
			foreach ($periods as $period)
			{
				$count += $this->getOverlapDays($period['from'], $period['to']);
			}

			$result = 1 - $count / Configuration::getCopilotDayPeriod();

			$valuesByUsers[] = [
				'value' => $result,
				'identifier' => $this->formatUserIdForNode($userId),
			];
		}

		$usersNode = (new NestedValueNode(
			EntityType::FLOW,
			'count',
			$valuesByUsers,
			'absences',
		));

		$this->result->addNode($usersNode);
	}

	private function getOverlapDays(DateTime $absenceStart, DateTime $absenceEnd): int
	{
		$start = Configuration::getCopilotPeriod();
		$end = new DateTime();

		$overlapStart = max($absenceStart, $start);
		$overlapEnd = min($absenceEnd, $end);

		if ($overlapStart <= $overlapEnd)
		{
			$interval = $overlapStart->getDiff($overlapEnd);

			return $interval->days + 1;
		}

		return 0;
	}

	protected function init(): void
	{
		$this->flow = $this->registry->getFlow();
		$this->employees = $this->registry->getEmployees();
	}
}
