<?php

namespace Bitrix\Tasks\Internals\Notification\Strategy\Default;

use Bitrix\Tasks\Internals\Notification\Dictionary;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Internals\Notification\Strategy\RecipientStrategyInterface;
use Bitrix\Tasks\Internals\Notification\User;
use Bitrix\Tasks\Internals\Notification\UserRepositoryInterface;
use Bitrix\Tasks\Internals\TaskObject;

class TaskExpiredStrategy implements RecipientStrategyInterface
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
			RoleDictionary::ROLE_DIRECTOR => $this->forOriginator(),
			RoleDictionary::ROLE_AUDITOR => $this->forAuditor(),
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

	private function forOriginator(): array
	{
		$accomplices = $this->task->getAccompliceMembersIds();

		if (
			$this->task->getCreatedBy() === $this->task->getResponsibleId()
			|| in_array($this->task->getCreatedBy(), $accomplices)
		)
		{
			return [];
		}

		$recipient = $this->userRepository->getUserById($this->task->getCreatedBy());

		return is_null($recipient) ? [] : [$recipient];
	}

	private function forAuditor(): array
	{
		$result = [];
		$accomplices = $this->task->getAccompliceMembersIds();

		foreach ($this->task->getAuditorMembersIds() as $auditorId)
		{
			if (
				$auditorId === $this->task->getCreatedBy()
				|| $auditorId === $this->task->getResponsibleId()
				|| in_array($auditorId, $accomplices)
			)
			{
				continue;
			}

			$recipient = $this->userRepository->getUserById($auditorId);
			if (is_null($recipient))
			{
				continue;
			}

			$result[] = $recipient;
		}

		return $result;
	}
}