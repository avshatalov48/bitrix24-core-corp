<?php

declare(strict_types=1);


namespace Bitrix\Tasks\Onboarding\Notification;

use Bitrix\Tasks\Internals\Notification\Controller;
use Bitrix\Tasks\Internals\Notification\ProviderCollection;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Onboarding\Notification\UseCase\TaskNotViewedOneDay;
use Bitrix\Tasks\Onboarding\Notification\UseCase\TaskNotViewedTwoDays;
use Bitrix\Tasks\Onboarding\Notification\UseCase\TooManyTasks;

class NotificationController extends Controller
{
	public function onTaskNotViewedOneDay(TaskObject $task): static
	{
		(new TaskNotViewedOneDay(
			$task,
			$this->buffer,
			$this->userRepository,
			new ProviderCollection(...$this->getDefaultNotificationProviders()),
		))->execute();

		return $this;
	}

	public function onTaskNotViewedTwoDays(TaskObject $task): static
	{
		(new TaskNotViewedTwoDays(
			$task,
			$this->buffer,
			$this->userRepository,
			new ProviderCollection(...$this->getDefaultNotificationProviders()),
		))->execute();

		return $this;
	}

	public function onTooManyTasks(TaskObject $task): static
	{
		(new TooManyTasks(
			$task,
			$this->buffer,
			$this->userRepository,
			new ProviderCollection(...$this->getDefaultNotificationProviders()),
		))->execute();

		return $this;
	}
}