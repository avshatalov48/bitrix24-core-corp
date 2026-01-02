<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Event;

use Bitrix\Main\Result;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Onboarding\DI\OnboardingContainer;
use Bitrix\Tasks\Onboarding\Internal\Factory\PairFactory;
use Bitrix\Tasks\Onboarding\Internal\Factory\UserJobFactory;
use Bitrix\Tasks\Onboarding\Internal\Type;
use Bitrix\Tasks\Onboarding\OnboardingFeature;
use Bitrix\Tasks\Onboarding\Transfer\CommandModelCollection;
use CUser;

abstract class AbstractEventListener
{
	private static array $instances = [];

	protected OnboardingContainer $container;

	public static function getInstance(): FakeEventListener|static
	{
		if (!OnboardingFeature::isAvailable())
		{
			return new FakeEventListener();
		}

		if (!isset(static::$instances[static::class]))
		{
			static::$instances[static::class] = new static();
		}

		return static::$instances[static::class];
	}

	public function __construct()
	{
		$this->init();
	}

	protected function isTaskViewed(int $taskId, int $userId): bool
	{
		return $this->container->getViewRepository()->isViewed($taskId, $userId);
	}

	protected function isOnePersonTask(TaskObject|array $task): bool
	{
		$responsibleId = (int)($task['RESPONSIBLE_ID'] ?? 0);
		$createdBy = (int)($task['CREATED_BY'] ?? 0);

		return $responsibleId === $createdBy;
	}

	protected function isInvitedUser(int $userId): bool
	{
		$user = CUser::GetByID($userId)->Fetch();
		if (!$user)
		{
			return false;
		}

		$lastLogin = $user['LAST_LOGIN'] ?? null;

		return empty($lastLogin);
	}

	protected function saveCommandModels(CommandModelCollection $commandModels): void
	{
		$this->container->getQueueService()->add($commandModels);
	}

	protected function deleteByPair(int $taskId = 0, int $userId = 0): Result
	{
		$pair = PairFactory::createPair($taskId, $userId);

		return $this->container->getQueueService()->deleteByPair($pair);
	}

	/**
	 * @param Type[] $types
	 */
	protected function deleteByUserJob(array $types, int $userId, int $taskId = 0): Result
	{
		$userJob = UserJobFactory::createUserJob($types, $userId, $taskId);

		return $this->container->getQueueService()->deleteByUserJob($userJob);
	}

	protected function init(): void
	{
		$this->container = OnboardingContainer::getInstance();
	}
}