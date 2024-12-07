<?php

namespace Bitrix\Tasks\Flow\Kanban;

use Bitrix\Bizproc\Automation\Engine\Robot;
use Bitrix\Main\ArgumentException;
use Bitrix\Tasks\InvalidCommandException;
use Bitrix\Tasks\Flow\Integration\BizProc\DocumentTrait;
use Bitrix\Tasks\Flow\Internal\Entity\FlowRobotCollection;
use Bitrix\Tasks\Flow\Internal\FlowRobotTable;
use Bitrix\Tasks\Flow\Kanban\Command\ReinstallFlowRobotsCommand;
use Bitrix\Tasks\Flow\Kanban\Stages\StageFactory;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;
use Bitrix\Tasks\Flow\Provider\RobotProvider;

class BizProcService
{
	use DocumentTrait;

	protected RobotProvider $robotProvider;
	protected ReinstallFlowRobotsCommand $updateCommand;
	protected FlowRobotCollection $flowRobots;

	public function __construct()
	{
		$this->init();
	}
	/**
	 * @throws InvalidCommandException
	 * @throws ProviderException
	 * @throws ArgumentException
	 */
	public function reinstall(ReinstallFlowRobotsCommand $command): void
	{
		$this->updateCommand = $command;

		$this->updateCommand->validateAdd();

		$this->flowRobots = $this->robotProvider->getAutoCreatedRobots($command->flowId);
		if ($this->flowRobots->isEmpty())
		{
			return;
		}

		$this->cleanFlowRobots();

		$this->recreateRobots();

		$this->cleanLinks();
	}

	private function cleanFlowRobots(): void
	{
		$groupedLinks = array_fill_keys($this->flowRobots->getStageIdList(), []);
		foreach ($this->flowRobots as $link)
		{
			$groupedLinks[$link->getStageId()][] = $link->getRobot();
		}

		foreach ($groupedLinks as $stageId => $flowRobots)
		{
			$template = $this->createTemplate($stageId, $this->updateCommand->projectId);
			$templateRobots = $template->getRobots();
			$robots = [];
			foreach ($templateRobots as $templateRobot)
			{
				/** @var Robot $templateRobot */
				if (in_array($templateRobot->getName(), $flowRobots,  true))
				{
					continue;
				}

				$robots[] = $templateRobot;
			}

			$template->save($robots, $this->updateCommand->ownerId);
		}
	}

	private function recreateRobots(): void
	{
		$groupedLinks = [];
		foreach ($this->flowRobots as $link)
		{
			$groupedLinks[$link->getStageId() .  '_' .  $link->getStageType()] = true;
		}

		foreach ($groupedLinks as $key => $data)
		{
			[$stageId, $stageType] = explode('_', $key);
			$stage = StageFactory::get(
				SystemType::from($stageType),
				$this->updateCommand->projectId,
				$this->updateCommand->ownerId,
				$this->updateCommand->flowId,
				$stageId
			);

			$stage->saveRobots();
		}
	}

	/**
	 * @throws ArgumentException
	 */
	private function cleanLinks(): void
	{
		FlowRobotTable::deleteByFilter(['FLOW_ID' => $this->updateCommand->flowId, '@ROBOT' => $this->flowRobots->getRobotList()]);
	}

	protected function init(): void
	{
		$this->robotProvider = new RobotProvider();
	}
}