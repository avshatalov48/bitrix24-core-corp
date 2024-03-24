<?php

namespace Bitrix\Tasks\Internals\Notification\Strategy\Default;

use Bitrix\Tasks\Internals\Notification\Dictionary;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Internals\Notification\Strategy\RecipientStrategyInterface;
use Bitrix\Tasks\Internals\Notification\User;
use Bitrix\Tasks\Internals\Notification\UserRepositoryInterface;
use Bitrix\Tasks\Internals\TaskObject;

class TaskExpiresSoonStrategy implements RecipientStrategyInterface
{
	use StrategyConstructorTrait;

	private UserRepositoryInterface $userRepository;
	private TaskObject $task;
	private Dictionary $dictionary;

	public function getRecipients(): array
	{
		$role = $this->dictionary->get('role');
		return match ($role)
		{
			RoleDictionary::ROLE_RESPONSIBLE => $this->forResponsible(),
			RoleDictionary::ROLE_ACCOMPLICE => $this->forAccomplice(),
			default => [],
		};
	}

	public function getSender(): ?User
	{
		return $this->userRepository->getUserById($this->task->getCreatedBy());
	}

	private function forResponsible(): array
	{
		$responsible = $this->userRepository->getUserById($this->task->getResponsibleId());
		return is_null($responsible) ? [] : [$responsible];
	}

	private function forAccomplice(): array
	{
		$result = [];
		foreach ($this->task->getAccompliceMembersIds() as $accompliceMembersId)
		{
			if ($accompliceMembersId === $this->task->getResponsibleId())
			{
				continue;
			}

			$recipient = $this->userRepository->getUserById($accompliceMembersId);
			if (is_null($recipient))
			{
				continue;
			}

			$result[] = $recipient;
		}

		return $result;
	}
}