<?php

namespace Bitrix\Tasks\Internals\Notification\UseCase;

use Bitrix\Tasks\Internals\Notification\BufferInterface;
use Bitrix\Tasks\Internals\Notification\EntityCode;
use Bitrix\Tasks\Internals\Notification\EntityOperation;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Notification\Metadata;
use Bitrix\Tasks\Internals\Notification\ProviderCollection;
use Bitrix\Tasks\Internals\Notification\UserRepositoryInterface;
use Bitrix\Tasks\Internals\TaskObject;

class NotificationReply
{
	private TaskObject $task;
	private BufferInterface $buffer;
	private UserRepositoryInterface $userRepository;
	private ProviderCollection $providers;

	public function __construct(
		TaskObject $task,
		BufferInterface $buffer,
		UserRepositoryInterface $userRepository,
		ProviderCollection $providers
	)
	{
		$this->task = $task;
		$this->buffer = $buffer;
		$this->userRepository = $userRepository;
		$this->providers = $providers;
	}

	public function execute(string $text): bool
	{
		$currentLoggedInUserId = \Bitrix\Main\Engine\CurrentUser::get()->getId();
		if ($currentLoggedInUserId === null)
		{
			return false;
		}

		$sender = $this->userRepository->getUserById($currentLoggedInUserId);
		if (!$sender)
		{
			return false;
		}

		$responsible = $this->userRepository->getUserById($this->task->getResponsibleId());
		if (empty($responsible))
		{
			return false;
		}

		foreach ($this->providers as $provider)
		{
			$provider->addMessage(new Message(
				$sender,
				$responsible,
				new Metadata(
					EntityCode::CODE_COMMENT,
					EntityOperation::REPLY,
					[
						'task' => $this->task,
						'text' => $text
					]
				)
			));

			$this->buffer->addProvider($provider);
		}

		return true;
	}
}