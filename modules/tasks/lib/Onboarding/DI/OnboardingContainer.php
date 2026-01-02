<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\DI;

use Bitrix\Tasks\DI\AbstractContainer;
use Bitrix\Tasks\Onboarding\Command\CommandRepositoryInterface;
use Bitrix\Tasks\Onboarding\Command\Executor\BatchCommandExecutor;
use Bitrix\Tasks\Onboarding\Command\Repository\CommandRepository;
use Bitrix\Tasks\Onboarding\Counter\CounterRepositoryInterface;
use Bitrix\Tasks\Onboarding\Counter\CounterServiceInterface;
use Bitrix\Tasks\Onboarding\Counter\Repository\CounterRepository;
use Bitrix\Tasks\Onboarding\Counter\Service\CounterService;
use Bitrix\Tasks\Onboarding\Internal\Queue\QueueServiceInterface;
use Bitrix\Tasks\Onboarding\Internal\Queue\Service\QueueService;
use Bitrix\Tasks\Onboarding\Promo\Repository\InviteToMobileRepositoryInterface;
use Bitrix\Tasks\Onboarding\Promo\Service\InviteToMobileService;
use Bitrix\Tasks\Onboarding\Task\Repository\TaskRepository;
use Bitrix\Tasks\Onboarding\Task\TaskRepositoryInterface;
use Bitrix\Tasks\Onboarding\View\Repository\ViewRepository;
use Bitrix\Tasks\Onboarding\View\ViewRepositoryInterface;

final class OnboardingContainer extends AbstractContainer
{
	public function getQueueService(): QueueServiceInterface
	{
		return $this->get(QueueService::class);
	}

	public function getViewRepository(): ViewRepositoryInterface
	{
		return $this->get(ViewRepository::class);
	}

	public function getCommandRepository(): CommandRepositoryInterface
	{
		return $this->get(CommandRepository::class);
	}

	public function getBatchCommandExecutor(): BatchCommandExecutor
	{
		return $this->get(BatchCommandExecutor::class);
	}

	public function getInviteToMobileService(): InviteToMobileService
	{
		return $this->get(InviteToMobileService::class);
	}

	public function getInviteToMobileRepository(): InviteToMobileRepositoryInterface
	{
		return $this->get(InviteToMobileRepositoryInterface::class);
	}

	public function getCounterService(): CounterServiceInterface
	{
		return $this->get(CounterService::class);
	}

	public function getCounterRepository(): CounterRepositoryInterface
	{
		return $this->get(CounterRepository::class);
	}

	public function getTaskRepository(): TaskRepositoryInterface
	{
		return $this->get(TaskRepository::class);
	}
}
