<?php

namespace Bitrix\Tasks\Flow\Kanban;

use Bitrix\Main\ORM\Data\Result;
use Bitrix\Tasks\Flow\Integration\BizProc\Robot\RobotCommandCollection;
use Bitrix\Tasks\Flow\Integration\BizProc\Robot\RobotStatusChangedCommand;
use Bitrix\Tasks\Flow\Integration\BizProc\Robot\RobotService;
use Bitrix\Tasks\Flow\Integration\BizProc\Trigger\TriggerCommand;
use Bitrix\Tasks\Flow\Integration\BizProc\Trigger\TriggerService;
use Bitrix\Tasks\Kanban\Stage;

abstract class AbstractStage
{
	protected int $projectId;
	protected int $ownerId;
	protected int $flowId;
	protected int $stageId;


	public function __construct(int $projectId, int $ownerId, int $flowId, int $stageId = 0)
	{
		$this->projectId = $projectId;
		$this->ownerId = $ownerId;
		$this->flowId = $flowId;
		$this->stageId = $stageId;
	}

	abstract protected function getInternalStage(): Stage;

	/**
	 * @return TriggerCommand[]
	 */
	abstract protected function getTriggers(): array;

	/**
	 * @return RobotStatusChangedCommand[]
	 */
	abstract protected function getRobots(): array;

	public function create(): Result
	{
		$result = $this->getInternalStage()->save();
		if (!$result->isSuccess())
		{
			return $result;
		}

		$this->stageId = $result->getId();

		$this->saveTriggers();

		$this->saveRobots();

		return $result;
	}

	final protected function saveTriggers(): void
	{
		$triggers = $this->getTriggers();
		$service = (new TriggerService($this->stageId, $this->projectId));

		$service->add(...$triggers);
	}

	final public function saveRobots(): void
	{
		$service = new RobotService($this->projectId, $this->ownerId, $this->flowId);
		$robots = new RobotCommandCollection($this->stageId, $this->getInternalStage()->getSystemType(), ...$this->getRobots());

		$service->add($robots);
	}
}