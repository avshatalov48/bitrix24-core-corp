<?php

namespace Bitrix\Tasks\Flow\Provider;

use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Flow\Time\DatePresenter;
use Bitrix\Tasks\Internals\Log\Logger;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Provider\Exception\TaskListException;
use Bitrix\Tasks\Provider\TaskList;
use Bitrix\Tasks\Provider\TaskQuery;

class AverageTimeProvider
{
	private const CLOSED_DATE_LIMIT = 30;

	private TaskList $taskProvider;

	private array $realStatus;
	private int $flowId;
	private int $status;

	public function __construct()
	{
		$this->init();
	}

	public function getAverageTimeInStatus(int $flowId, int $status, array $realStatus, int $tail = 50): int
	{
		if ($flowId <= 0 || $tail <= 0)
		{
			return 0;
		}

		if (!in_array($status, [Status::PENDING, Status::IN_PROGRESS, Status::COMPLETED], true))
		{
			return 0;
		}

		$this->flowId = $flowId;
		$this->status = $status;
		$this->realStatus = $realStatus;

		$query = (new TaskQuery())
			->skipAccessCheck()
			->setSelect($this->getSelect())
			->setWhere($this->getFilter())
			->setOrder($this->getOrder())
			->setLimit($tail);

		try
		{
			$tasks = $this->taskProvider->getList($query);
		}
		catch (TaskListException $e)
		{
			Logger::logThrowable($e);
			return 0;
		}

		$now = new DateTime();
		$sum = 0;
		$count = 0;

		foreach ($tasks as $task)
		{
			$date = $task[$this->getDateField()] ?? null;
			if ($date === null)
			{
				continue;
			}

			if ($this->status === Status::COMPLETED)
			{
				$closedDate = $task['CLOSED_DATE'] ?? null;
				if ($closedDate === null)
				{
					continue;
				}
				$duration = DatePresenter::get($task[$this->getDateField()], $task['CLOSED_DATE'])->getRaw()->getSecondTotal();
			}
			else
			{
				$duration = DatePresenter::get($now, $task[$this->getDateField()])->getRaw()->getSecondTotal();
			}

			$sum += $duration;
			++$count;
		}

		if ($count > 0)
		{
			return (int)($sum / $count);
		}

		return 0;
	}

	private function getSelect(): array
	{
		$select = [
			'FLOW_ID',
			$this->getDateField()
		];

		if ($this->status === Status::COMPLETED)
		{
			$select[] = $this->getCompletedExpression();
			$select[] = 'CLOSED_DATE';
		}

		return $select;
	}

	private function getFilter(): array
	{
		$filter = [
			'FLOW_ID' => $this->flowId,
			'REAL_STATUS' => $this->realStatus,
		];

		if ($this->status === Status::COMPLETED)
		{
			$filter[] = [
				'>=CLOSED_DATE' => (new DateTime())->add('-' . static::CLOSED_DATE_LIMIT . ' days')
			];
		}

		return $filter;
	}

	private function getOrder(): array
	{
		return [
			'ID' => 'DESC',
			$this->getDateField() => 'ASC',
		];
	}

	private function getDateField(): string
	{
		return match ($this->status)
		{
			Status::PENDING => 'CREATED_DATE',
			Status::IN_PROGRESS => 'DATE_START',
			Status::COMPLETED => 'START_POINT',
		};
	}


	private function getCompletedExpression(): ExpressionField
	{
		return new ExpressionField(
			'START_POINT',
			'CASE
							WHEN CREATED_DATE IS NOT NULL THEN CREATED_DATE
							WHEN DATE_START IS NOT NULL THEN DATE_START
							ELSE NOW()
						END'
		);
	}

	private function init(): void
	{
		$this->taskProvider = new TaskList();
	}
}
