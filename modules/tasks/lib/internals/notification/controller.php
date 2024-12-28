<?php

namespace Bitrix\Tasks\Internals\Notification;

use Bitrix\Main\Config\Option;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Integration\CRM\TimeLineManager;
use Bitrix\Tasks\Integration\IM;
use Bitrix\Tasks\Integration\Mail;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Integration\Forum;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Internals\Notification;

class Controller
{
	private PusherInterface $pusher;
	private BufferInterface $buffer;
	private UserRepositoryInterface $userRepository;

	public function __construct(
		?PusherInterface $pusher = null,
		?BufferInterface $buffer = null,
		?UserRepositoryInterface $userRepository = null
	)
	{
		$this->buffer = $buffer ?? InMemoryBuffer::getInstance();
		$this->pusher = $pusher ?? new Pusher();
		$this->userRepository = $userRepository ?? new UserRepository();
	}

	public function push(): void
	{
		try
		{
			$notifications = $this->buffer->flush();
			$this->pusher->push($notifications);
		}
		catch (\Throwable $e)
		{
			throw new \Exception('Failed pushing notifications with message: ' . $e->getMessage(), $e->getCode());
		}
	}

	public function onTaskCreated(TaskObject $task, array $params = []): self
	{
		(new Notification\UseCase\TaskCreated(
			$task,
			$this->buffer,
			$this->userRepository,
			new ProviderCollection(...$this->getDefaultNotificationProviders([
				new Mail\ExternalUserProvider(),
				new SocialNetwork\NotificationProvider(),
			])),
		))->execute($params);

		return $this;
	}

	public function onTaskAddedToFlowWithManualDistribution(TaskObject $task, FlowEntity $flow): self
	{
		(new Notification\UseCase\Flow\TaskAddedToFlowWithManualDistribution(
			$task,
			$this->buffer,
			$this->userRepository,
			new ProviderCollection(...$this->getDefaultNotificationProviders()),
		))->execute($flow);

		return $this;
	}

	public function onTaskAddedToFlowWithHimselfDistribution(TaskObject $task, FlowEntity $flow): self
	{
		(new Notification\UseCase\Flow\TaskAddedToFlowWithHimselfDistribution(
			$task,
			$this->buffer,
			$this->userRepository,
			new ProviderCollection(...$this->getDefaultNotificationProviders()),
		))->execute($flow);

		return $this;
	}

	public function onRegularTaskReplicated(TaskObject $task, array $params = []): self
	{
		(new Notification\UseCase\Regularity\RegularTaskReplicated(
			$task,
			$this->buffer,
			$this->userRepository,
			new ProviderCollection(...$this->getDefaultNotificationProviders([
				new Mail\ExternalUserProvider(),
				new SocialNetwork\NotificationProvider(),
			])),
		))->execute($params);

		return $this;
	}

	public function onRegularTaskStarted(TaskObject $task, array $params = []): self
	{
		(new Notification\UseCase\Regularity\RegularTaskStarted(
			$task,
			$this->buffer,
			$this->userRepository,
			new ProviderCollection(...$this->getDefaultNotificationProviders([
				new Mail\ExternalUserProvider(),
				new SocialNetwork\NotificationProvider(),
			])),
		))->execute($params);

		return $this;
	}

	public function onTaskUpdated(TaskObject $task, array $newFields, array $previousFields, array $params = []): self
	{
		(new Notification\UseCase\TaskUpdated(
			$task,
			$this->buffer,
			$this->userRepository,
			new ProviderCollection(...$this->getDefaultNotificationProviders([
				new Mail\ExternalUserProvider(),
				new SocialNetwork\NotificationProvider(),
			])),
		))->execute($newFields, $previousFields, $params);

		return $this;
	}

	public function onTaskDeleted(TaskObject $task, bool $safeDelete = false): self
	{
		(new Notification\UseCase\TaskDeleted(
			$task,
			$this->buffer,
			$this->userRepository,
			new ProviderCollection(...$this->getDefaultNotificationProviders([
				new SocialNetwork\NotificationProvider(),
			])),
		))->execute($safeDelete);

		return $this;
	}

	public function onTaskExpired(TaskObject $task): self
	{
		(new Notification\UseCase\TaskExpired(
			$task,
			$this->buffer,
			$this->userRepository,
			new ProviderCollection(...$this->getDefaultNotificationProviders()),
		))->execute();

		return $this;
	}

	public function onTaskExpiresSoon(TaskObject $task): self
	{
		(new Notification\UseCase\TaskExpiresSoon(
			$task,
			$this->buffer,
			$this->userRepository,
			new ProviderCollection(...$this->getDefaultNotificationProviders()),
		))->execute();

		return $this;
	}

	public function onTaskStatusChanged(TaskObject $task, int $taskCurrentStatus, array $params = []): self
	{
		(new Notification\UseCase\TaskStatusChanged(
			$task,
			$this->buffer,
			$this->userRepository,
			new ProviderCollection(...$this->getDefaultNotificationProviders([
				new SocialNetwork\NotificationProvider(),
			])),
		))->execute($taskCurrentStatus, $params);

		return $this;
	}

	public function onTaskPingSend(TaskObject $task, int $authorId): self
	{
		$result = (new Notification\UseCase\TaskPingSent(
			$task,
			$this->buffer,
			$this->userRepository,
			new ProviderCollection(...$this->getDefaultNotificationProviders()),
		))->execute($authorId);

		if ($result === true)
		{
			(new TimeLineManager($task->getId(), $authorId))->onTaskPingSent()->save();
		}

		return $this;
	}

	public function onNotificationReply(TaskObject $task, string $text): self
	{
		(new Notification\UseCase\NotificationReply(
			$task,
			$this->buffer,
			$this->userRepository,
			new ProviderCollection(...[
				new Forum\NotificationProvider()
			])
		))->execute($text);

		return $this;
	}

	public function onCommentCreated(TaskObject $task, int $commentId, string $text): self
	{
		(new Notification\UseCase\CommentCreated(
			$task,
			$this->buffer,
			$this->userRepository,
			new ProviderCollection(...$this->getDefaultNotificationProviders([
				new Mail\ExternalUserProvider(),
			])),
		))->execute($commentId, $text);

		return $this;
	}

	private function getDefaultNotificationProviders(array $additionalProviders = []): array
	{
		$defaultProviders = [
			new Im\Notification\Provider(),
			...$additionalProviders
		];

		if(Option::get('tasks', 'notification_logs_enabled', 'null') !== 'null')
		{
			// add logs provider
			$defaultProviders[] = new Log();
		}

		return $defaultProviders;
	}
}