<?php

namespace Bitrix\Tasks\Internals\Notification\UseCase;

use Bitrix\Tasks\Internals\Notification\BufferInterface;
use Bitrix\Tasks\Internals\Notification\EntityCode;
use Bitrix\Tasks\Internals\Notification\EntityOperation;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Notification\Metadata;
use Bitrix\Tasks\Internals\Notification\ProviderCollection;
use Bitrix\Tasks\Internals\Notification\User;
use Bitrix\Tasks\Internals\Notification\UserRepositoryInterface;
use Bitrix\Tasks\Internals\TaskObject;

class TaskCreated
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

	public function execute($params = []): bool
	{
		$sender = $this->userRepository->getSender($this->task, $params);
		if (!$sender)
		{
			return false;
		}

		$recepients = $this->userRepository->getRecepients($this->task, $sender, $params);
		if (empty($recepients))
		{
			return false;
		}

		if (
			$sender->getId() !== $this->task->getCreatedBy()
			&& in_array($sender->getId(), $this->userRepository->getParticipants($this->task, $params))
		)
		{
			// special case: add sender to recipients
			$recepients = $this->addUserToRecepients($recepients, $sender);
		}

		foreach ($this->providers as $provider)
		{
			foreach ($recepients as $recepient)
			{
				$metadata = new Metadata(
					EntityCode::CODE_TASK,
					EntityOperation::ADD,
					[
						'task' => $this->task,
						'user_repository' => $this->userRepository,
						'user_params' => $params
					]
				);

				$provider->addMessage(new Message(
					$sender,
					$recepient,
					$metadata
				));
			}

			$this->buffer->addProvider($provider);
		}

		return true;
	}

	private function addUserToRecepients(array $recepients, User $user): array
	{
		foreach ($recepients as $recepient)
		{
			if ($recepient->getId() === $user->getId())
			{
				// already exists
				return $recepients;
			}
		}

		$recepients[] = $user;

		return $recepients;
	}
}