<?php

namespace Bitrix\Tasks\Internals\Notification\UseCase;

use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Internals\Notification\BufferInterface;
use Bitrix\Tasks\Internals\Notification\EntityCode;
use Bitrix\Tasks\Internals\Notification\EntityOperation;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Notification\Metadata;
use Bitrix\Tasks\Internals\Notification\ProviderCollection;
use Bitrix\Tasks\Internals\Notification\ProviderInterface;
use Bitrix\Tasks\Internals\Notification\User;
use Bitrix\Tasks\Internals\Notification\UserRepositoryInterface;
use Bitrix\Tasks\Internals\TaskObject;

class TaskExpiresSoon
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

	public function execute(): bool
	{
		$sender = $this->userRepository->getUserById($this->task->getCreatedBy());
		if (!$sender)
		{
			return false;
		}

		foreach ($this->providers as $provider)
		{
			$this->expiresSoonForResponsible($sender, $provider);
			$this->expiresSoonForAccomplices($sender, $provider);

			$this->buffer->addProvider($provider);
		}

		return true;
	}

	private function expiresSoonForResponsible(User $sender, ProviderInterface $provider): void
	{
		$recepient = $this->userRepository->getUserById($this->task->getResponsibleId());
		if (!$recepient)
		{
			return;
		}

		$provider->addMessage(new Message(
			$sender,
			$recepient,
			$this->getMetadata([
				'task' => $this->task,
				'member_code' => RoleDictionary::ROLE_RESPONSIBLE
			])
		));
	}

	private function expiresSoonForAccomplices(User $sender, ProviderInterface $provider): void
	{
		foreach ($this->task->getAccompliceMembersIds() as $accompliceMembersId)
		{
			if ($accompliceMembersId === $this->task->getResponsibleId())
			{
				continue;
			}

			$recepient = $this->userRepository->getUserById($accompliceMembersId);
			if (!$recepient)
			{
				continue;
			}

			$provider->addMessage(new Message(
				$sender,
				$recepient,
				$this->getMetadata([
					'task' => $this->task,
					'member_code' => RoleDictionary::ROLE_ACCOMPLICE
				])
			));
		}
	}

	private function getMetadata(array $params = []): Metadata
	{
		return new Metadata(
			EntityCode::CODE_TASK,
			EntityOperation::EXPIRES_SOON,
			$params
		);
	}
}