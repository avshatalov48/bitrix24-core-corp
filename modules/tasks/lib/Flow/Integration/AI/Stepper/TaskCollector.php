<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Stepper;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Main\Update\Stepper;
use Bitrix\Tasks\Flow\Integration\AI\Configuration;
use Bitrix\Tasks\Flow\Integration\AI\FlowCopilotFeature;
use Bitrix\Tasks\Flow\Integration\AI\Registry;
use Bitrix\Tasks\Flow\Integration\AI\Result\CollectorResult;
use Bitrix\Tasks\Flow\Integration\AI\Stepper\Filler\AbstractFiller;
use Bitrix\Tasks\Flow\Integration\AI\Stepper\Filler\Type\Multiple\FlowFiller;
use Bitrix\Tasks\Flow\Integration\AI\Stepper\Filler\Type\Multiple\TaskFiller;
use Bitrix\Tasks\Flow\Integration\AI\Stepper\Filler\Type\Multiple\UserFiller;
use Bitrix\Tasks\Flow\Integration\AI\Stepper\Trait\CollectorTrait;
use Bitrix\Tasks\Flow\Integration\AI\Stepper\Trait\StepperQueueTrait;
use Bitrix\Tasks\Flow\Internal\FlowCopilotCollectedDataTable;
use Bitrix\Tasks\Flow\Internal\FlowTaskTable;
use Bitrix\Tasks\Internals\Log\Logger;
use Bitrix\Tasks\Internals\Task\LogTable;
use Bitrix\Tasks\Internals\TaskTable;
use Exception;
use Throwable;

class TaskCollector extends Stepper
{
	use StepperQueueTrait;
	use CollectorTrait;

	protected static $moduleId = 'tasks';

	private int $flowId;
	private array $taskIds = [];
	private array $option = [];

	private CollectorResult $result;

	public function execute(array &$option): bool
	{
		if (!FlowCopilotFeature::isOn())
		{
			return static::FINISH_EXECUTION;
		}

		try
		{
			$this->init();

			$this->fillCurrentStep();

			if ($this->isLastStep())
			{
				$this->finalize();
				$this->deleteStepOption();

				return static::FINISH_EXECUTION;
			}

			$this->fillData();
			$this->summarize();
			$this->fillNextStep();

			return static::CONTINUE_EXECUTION;
		}
		catch (Throwable $t)
		{
			Logger::logThrowable($t);
			$this->deleteStepOption();

			return static::FINISH_EXECUTION;
		}
	}

	public function finalize(): void
	{
		$this->runNext();
	}

	/**
	 * @throws Exception
	 */
	public static function clean(int $flowId): void
	{
		FlowCopilotCollectedDataTable::delete($flowId);
	}

	private function fillData(): void
	{
		$registry = Registry::getInstance($this->flowId, $this->taskIds);

		/**
		 * @var AbstractFiller[] $fillers
		 */
		$fillers = [
			new FlowFiller($registry),
			new TaskFiller($registry),
			new UserFiller($registry),
		];

		foreach ($fillers as $filler)
		{
			$filler->fill($this->result);
		}
	}

	/**
	 * @throws SystemException
	 * @throws ObjectPropertyException
	 * @throws ArgumentException
	 */
	private function getTaskIds(): array
	{
		$flowReference = (new ReferenceField(
			'FLOW',
			FlowTaskTable::getEntity(),
			Join::on('this.ID', 'ref.TASK_ID')
				->where('ref.FLOW_ID', $this->flowId),
		))->configureJoinType(Join::TYPE_INNER);

		$logReference = (new ReferenceField(
			'LOG',
			LogTable::getEntity(),
			Join::on('this.ID', 'ref.TASK_ID')
				->where('ref.FIELD', 'FLOW_ID')
				->where('ref.TO_VALUE', $this->flowId),
		))->configureJoinType(Join::TYPE_LEFT);

		$dateFilter = Query::filter()
			->logic(ConditionTree::LOGIC_OR)
			->where('CREATED_DATE', '>', Configuration::getCopilotPeriod())
			->where('LOG.CREATED_DATE', '>', Configuration::getCopilotPeriod());

		$rows = TaskTable::query()
			->setDistinct()
			->setSelect(['ID'])
			->where($dateFilter)
			->registerRuntimeField($flowReference)
			->registerRuntimeField($logReference)
			->setLimit(Configuration::getCopilotTasksLimit())
			->exec()
			->fetchAll();

		return array_map('intval', array_column($rows, 'ID'));
	}

	private function isLastStep(): bool
	{
		return empty($this->taskIds);
	}

	private function getFlowId(): int
	{
		return $this->flowId;
	}

	private function fillCurrentStep(): void
	{
		$this->taskIds = array_slice($this->option['taskIds'], 0, Configuration::getCopilotStepLimit());
	}

	private function fillNextStep(): void
	{
		$this->option['taskIds'] = array_diff($this->option['taskIds'], $this->taskIds);

		$this->setStepOption();
	}

	private function setStepOption(): void
	{
		Option::set('main.stepper.tasks', self::class . "({$this->flowId})", serialize($this->option));
	}

	private function deleteStepOption(): void
	{
		Option::delete('main.stepper.tasks', ['name' => self::class . "({$this->flowId})"]);
	}

	private function getNext(): array
	{
		return [
			'class' => FlowCollector::class,
			'args' => [$this->flowId],
		];
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function init(): void
	{
		$this->result = new CollectorResult();

		$this->flowId = $this->outerParams[0];

		$flowOption = Option::get('main.stepper.tasks', self::class . "({$this->flowId})");
		if ($flowOption !== "")
		{
			$flowOption = unserialize($flowOption, ['allowed_classes' => false]);
		}
		$flowOption = is_array($flowOption) ? $flowOption : [];
		$this->option['taskIds'] = $flowOption['taskIds'] ?? null;

		if ($this->option['taskIds'] === null)
		{
			$this->option['taskIds'] = $this->getTaskIds();
		}
	}
}
