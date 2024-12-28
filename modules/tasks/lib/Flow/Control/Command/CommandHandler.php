<?php

namespace Bitrix\Tasks\Flow\Control\Command;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\UI\EntitySelector\Converter;
use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotFoundException;
use Bitrix\Tasks\InvalidCommandException;
use Bitrix\Tasks\Flow\Control\Exception\MiddlewareException;
use Bitrix\Tasks\Flow\Control\Middleware\MiddlewareInterface;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Flow\Option\OptionService;
use Bitrix\Tasks\Flow\Responsible\ResponsibleQueue\ResponsibleQueueProvider;
use Bitrix\Tasks\Integration\Pull\PushService;

abstract class CommandHandler
{
	protected AbstractCommand $command;
	protected FlowEntity $flowEntity;
	protected FlowRegistry $flowRegistry;
	protected ResponsibleQueueProvider $queueProvider;
	protected OptionService $optionProvider;
	protected Connection $connection;
	protected MiddlewareInterface $middleware;
	protected Converter $converter;

	protected array $requiredObservers = [];
	protected array $extraObservers = [];

	public function __construct()
	{
		$this->init();
	}

	public function addMiddleware(MiddlewareInterface $middleware): static
	{
		if (!isset($this->middleware))
		{
			$this->middleware = $middleware;
		}
		else
		{
			$this->middleware->setNext($middleware);
		}

		return $this;
	}

	/**
	 * @throws FlowNotFoundException
	 * @throws \Bitrix\Tasks\Flow\Provider\Exception\FlowNotFoundException
	 */
	protected function loadFlow(int $id): Flow
	{
		$flowEntity = $this->flowRegistry->get($id, ['*', 'MEMBERS']);
		if ($flowEntity === null)
		{
			throw new FlowNotFoundException("Unable to load flow {$id}");
		}

		$this->flowEntity = $flowEntity;

		$flow = new Flow($this->flowEntity->collectValues());

		$responsibleList = $this->getResponsibleList($flow);
		$options = $this->optionProvider->getOptions($flow->getId());

		return $flow
			->setResponsibleList($responsibleList)
			->setOptions($options)
		;
	}

	/**
	 * @throws \Bitrix\Tasks\Flow\Provider\Exception\FlowNotFoundException
	 * @return string[]

	 */
	private function getResponsibleList(Flow $flow): array
	{
		$memberFacade = ServiceLocator::getInstance()->get('tasks.flow.member.facade');
		$responsibleAccessCodes = $memberFacade->getResponsibleAccessCodes($flow->getId());

		return $this->converter::convertFromFinderCodes($responsibleAccessCodes);
	}

	protected function init(): void
	{
		$this->connection = Application::getConnection();
		$this->flowRegistry = FlowRegistry::getInstance();
		$this->queueProvider = new ResponsibleQueueProvider();
		$this->optionProvider = OptionService::getInstance();
		$this->converter = new Converter();
	}

	protected function sendPush(Flow $flow, string $tag, string $pushCommand): void
	{
		if ($this->command->isNecessarySendPush())
		{
			$params = $this->command->getPushParams();

			$params['FLOW_ID'] = $flow->getId();

			PushService::addEventByTag($tag, [
				'module_id' => 'tasks',
				'command' => $pushCommand,
				'params' => $params,
			]);
		}
	}

	/**
	 * @throws InvalidCommandException
	 */
	protected function buildMiddleware(): void
	{
		try
		{
			$this->middleware->handle($this->command);
		}
		catch (MiddlewareException $e)
		{
			throw new InvalidCommandException($e->getMessage());
		}
	}
}