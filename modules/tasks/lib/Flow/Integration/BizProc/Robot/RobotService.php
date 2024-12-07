<?php

namespace Bitrix\Tasks\Flow\Integration\BizProc\Robot;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Flow\Integration\BizProc\DocumentTrait;
use Bitrix\Tasks\Flow\Internal\Entity\FlowRobot;
use Bitrix\Tasks\Flow\Internal\Entity\FlowRobotCollection;
use Bitrix\Tasks\Util\User;

class RobotService
{
	use DocumentTrait;

	protected int $projectId;
	protected int $ownerId;
	protected int $flowId;

	private bool $isAvailable;

	protected RobotCommandCollection $commands;

	final public function __construct(int $projectId, int $ownerId, int $flowId)
	{
		if (!$this->isAvailable())
		{
			return;
		}

		$this->projectId = $projectId;
		$this->ownerId = $ownerId;
		$this->flowId = $flowId;
	}

	public function add(RobotCommandCollection $commands): void
	{
		if (!$this->isAvailable)
		{
			return;
		}

		if ($commands->isEmpty())
		{
			return;
		}

		$this->commands = $commands;

		$this->saveRobots();
	}

	final protected function isAvailable(): bool
	{
		$this->isAvailable ??= Loader::includeModule('bizproc');
		return $this->isAvailable;
	}

	private function saveRobots(): void
	{
		$template = $this->createTemplate($this->commands->getStageId(), $this->projectId);

		$robots = $this->commands->toArray();
		$robots = array_merge($robots, $template->getRobots());

		if ([] !== $robots)
		{
			$template->save($robots, User::getAdminId());
		}

		$this->saveLinks();
	}

	private function saveLinks(): void
	{
		$stageId = $this->commands->getStageId();
		$stageType = $this->commands->getStageType();
		$templateId = $this->createTemplate($stageId, $this->projectId)->getId();

		$links = new FlowRobotCollection();

		$sensitiveCommands = $this->commands->getUserSensitive();
		foreach ($sensitiveCommands as $robot)
		{
			$robotLink = (new FlowRobot())
				->setFlowId($this->flowId)
				->setStageId($stageId)
				->setStageType($stageType)
				->setBizProcTemplateId($templateId)
				->setRobot($robot->getName());

			$links->add($robotLink);
		}

		$links->insertIgnore();
	}
}