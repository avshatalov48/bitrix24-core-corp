<?php

namespace Bitrix\Tasks\Flow\Demo;

use Bitrix\Main\Config\Option;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Control\Template;
use Bitrix\Tasks\Flow\Control\Command\AddDemoCommand;
use Bitrix\Tasks\Flow\Control\Exception\CommandNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotAddedException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Control\FlowService;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Internals\Log\Logger;
use Bitrix\Tasks\InvalidCommandException;

class Service
{
	private const OPTION_KEY = 'demo_flows_created';
	private const OPTION_ERROR_KEY = 'demo_flows_created_with_error';

	public function __construct(private readonly int $creatorId) {}

	public function isDemoFlowsCreated(): bool
	{
		return (
			(bool) Option::get('tasks', self::OPTION_KEY, false)
			|| (bool) Option::get('tasks', self::OPTION_ERROR_KEY, false)
		);
	}

	public function createDemoFlows(): void
	{
		try
		{
			$flowService = new FlowService($this->creatorId);

			$count = 0;
			foreach (array_reverse(DataList::get()) as $data)
			{
				$templateId = $this->createTemplate($data['template']);
				$this->createFlow($flowService, $data['flow'], $templateId, ++$count);
			}

			$this->setOption();
		}
		catch (\Throwable $exception)
		{
			$this->setErrorOption();

			Logger::log($exception, 'FLOW_DEMO_ERROR');
		}
	}

	/**
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\Tasks\Control\Exception\TemplateAddException
	 */
	private function createTemplate(array $data): int
	{
		$templateService = new Template($this->creatorId);

		$data['RESPONSIBLE_ID'] = $this->creatorId;
		$data['CREATED_BY'] = $this->creatorId;

		$template = $templateService->add($data);

		return $template->getId();
	}

	/**
	 * @throws FlowNotFoundException
	 * @throws InvalidCommandException
	 * @throws CommandNotFoundException
	 * @throws SqlQueryException
	 * @throws FlowNotAddedException
	 */
	private function createFlow(FlowService $flowService, AddDemoCommand $addCommand, int $templateId, int $count): void
	{
		$addCommand->setGroupId(0);
		$addCommand->setTemplateId($templateId);
		$addCommand->setPlannedCompletionTime(3600);

		$addCommand->setCreatorId($this->creatorId);
		$addCommand->setOwnerId($this->creatorId);

		$addCommand->setDistributionType(FlowDistributionType::MANUALLY->value);
		$addCommand->setResponsibleList(['U' . $this->creatorId]);

		$addCommand->setTaskControl(true);
		$addCommand->setNotifyAtHalfTime(true);
		$addCommand->setNotifyOnQueueOverflow(50);
		$addCommand->setNotifyOnTasksInProgressOverflow(50);
		$addCommand->setNotifyWhenEfficiencyDecreases(70);

		$addCommand->setActivity(DateTime::createFromTimestamp(time() + $count));

		$addCommand->setActive(false);
		$addCommand->setDemo(true);

		$addCommand->setTaskCreators(['UA']);

		$addCommand->disablePush();

		$flowService->add($addCommand);
	}

	private function setOption(): void
	{
		Option::set('tasks', self::OPTION_KEY, true);
	}

	private function setErrorOption(): void
	{
		Option::set('tasks', self::OPTION_ERROR_KEY, true);
	}
}
