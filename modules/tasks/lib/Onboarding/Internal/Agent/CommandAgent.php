<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Internal\Agent;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Onboarding\DI\OnboardingContainer;
use Bitrix\Tasks\Onboarding\Internal\Factory\JobCodeFactory;
use Bitrix\Tasks\Onboarding\Internal\Factory\JobIdsFactory;
use Bitrix\Tasks\Update\AgentInterface;
use Bitrix\Tasks\Update\AgentTrait;
use CAgent;

final class CommandAgent implements AgentInterface
{
	use AgentTrait;

	private const INTERVAL = 300; // 5 minutes

	private static bool $isProcess = false;

	private OnboardingContainer $container;

	private function __construct()
	{
		$this->init();
	}

	public static function install(): void
	{
		$timestamp = time() + 600; // 10 minutes offset
		$roundedTimestamp = ceil($timestamp / self::INTERVAL) * self::INTERVAL;

		$nextExec = DateTime::createFromTimestamp($roundedTimestamp)->toString();

		CAgent::AddAgent(
			name: self::getAgentName(),
			module: 'tasks',
			period: 'Y',
			interval: self::INTERVAL,
			next_exec: $nextExec,
		);
	}

	public static function execute(): string
	{
		if (self::$isProcess)
		{
			return '';
		}

		self::$isProcess = true;

		$agent = new self();
		$result = $agent->run();

		self::$isProcess = false;

		return $result;
	}

	private function run(): string
	{
		$commands = $this->container->getCommandRepository()->getAll();

		if ($commands->isEmpty())
		{
			return self::getAgentName();
		}

		$commandIds = $commands->getIdList();

		$service = $this->container->getQueueService();

		$jobIds = JobIdsFactory::createIds($commandIds);
		$result = $service->markAsProcessing($jobIds);

		if (!$result->isSuccess())
		{
			return self::getAgentName();
		}

		$result = $this->container->getBatchCommandExecutor()->execute($commands);

		$completedCommandIds = $result->getCompletedCommandIds();

		$completedJobs = JobIdsFactory::createIds($completedCommandIds);
		$service->deleteByIds($completedJobs);

		$duplicatedCommandCodes = $result->getDuplicatedCommandCodes();

		$duplicatedJobs = JobCodeFactory::createCodes($duplicatedCommandCodes);
		$service->deleteByCodes($duplicatedJobs);

		$notCompletedCommandIds = $result->getNotCompletedCommandIds();

		$notCompletedJobs = JobIdsFactory::createIds($notCompletedCommandIds);
		$service->unmarkAsProcessing($notCompletedJobs);

		return self::getAgentName();
	}

	private function init(): void
	{
		$this->container = OnboardingContainer::getInstance();
	}
}