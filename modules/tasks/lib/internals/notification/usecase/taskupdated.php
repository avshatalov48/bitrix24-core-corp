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

class TaskUpdated
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

	public function execute(array $newFields, array $previousFields, array $params = []): bool
	{
		$sender = $this->userRepository->getSender($this->task, $params);
		if (!$sender)
		{
			return false;
		}

		$recepients = $this->getRecepients($sender, $previousFields, $params);
		if (empty($recepients))
		{
			return false;
		}

		$changes = \CTaskLog::GetChanges($previousFields, $newFields);
		$trackedFields = \CTaskLog::getTrackedFields();

		foreach ($this->providers as $provider)
		{
			foreach ($recepients as $recepient)
			{
				$provider->addMessage(new Message(
					$sender,
					$recepient,
					new Metadata(
						EntityCode::CODE_TASK,
						EntityOperation::UPDATE,
						[
							'task' => $this->task,
							'previous_fields' => $previousFields,
							'changes' => $changes,
							'tracked_fields' => $trackedFields,
							'user_repository' => $this->userRepository,
							'user_params' => $params,
							'assigned_to' => $this->getAssignedTo($newFields, $previousFields),
						]
					)
				));
			}

			$this->buffer->addProvider($provider);
		}

		return true;
	}

	/**
	 * @param User $sender
	 * @param array $previousFields
	 * @param array $params
	 * @return User[]
	 */
	private function getRecepients(User $sender, array $previousFields, array $params): array
	{
		$params['additional_recepients'] = [];
		// Pack prev users ids to ADDITIONAL_RECIPIENTS, to ensure,
		// that they all will receive message
		{
			if (isset($previousFields['CREATED_BY']))
			{
				$params['additional_recepients'][] = $previousFields['CREATED_BY'];
			}

			if (isset($previousFields['RESPONSIBLE_ID']))
			{
				$params['additional_recepients'][] = $previousFields['RESPONSIBLE_ID'];
			}

			if (isset($previousFields['ACCOMPLICES']) && is_array($previousFields['ACCOMPLICES']))
			{
				foreach ($previousFields['ACCOMPLICES'] as $userId)
				{
					$params['additional_recepients'][] = $userId;
				}
			}

			if (isset($previousFields['AUDITORS']) && is_array($previousFields['AUDITORS']))
			{
				foreach ($previousFields['AUDITORS'] as $userId)
				{
					$params['additional_recepients'][] = $userId;
				}
			}
		}

		return $this->userRepository->getRecepients($this->task, $sender, $params);
	}

	private function getAssignedTo(array $newFields, array $prevFields): int|null
	{
		if (
			isset($newFields['RESPONSIBLE_ID'], $prevFields['RESPONSIBLE_ID'])
			&& ($newFields['RESPONSIBLE_ID'] > 0)
			&& ($newFields['RESPONSIBLE_ID'] != $prevFields['RESPONSIBLE_ID'])
		)
		{
			return (int)$newFields['RESPONSIBLE_ID'];
		}

		return null;
	}
}