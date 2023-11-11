<?php

namespace Bitrix\Tasks\Internals\Notification;

use Bitrix\Main\UserTable;
use Bitrix\Tasks\Internals\TaskObject;

class UserRepository implements UserRepositoryInterface
{
	/** @var User[]  */
	private array $cache = [];
	/** @var int[] */
	private array $timeZoneCache = [];

	/**
	 * @param TaskObject $task
	 * @param User $sender
	 * @param array $optional
	 * @return User[]
	 */
	public function getRecepients(TaskObject $task, User $sender, array $optional = []): array
	{
		$recepients = [];

		foreach ($this->getParticipants($task, $optional) as $id)
		{
			if ($id === null || $this->ignoreRecepient((int)$id, $optional))
			{
				continue;
			}

			if ((int)$id === $sender->getId() && !$this->isSpawnedByAgent($optional))
			{
				continue;
			}

			$recepient = $this->getUserById((int)$id);
			if ($recepient)
			{
				$recepients[] = $recepient;
			}
		}

		return $recepients;
	}

	public function getSender(TaskObject $task, array $optional = []): ?User
	{
		if(isset($optional['AUTHOR_ID']) && (int)$optional['AUTHOR_ID'] > 0)
		{
			return $this->getUserById((int)$optional['AUTHOR_ID']);
		}

		if(\Bitrix\Tasks\Util\User::getOccurAsId() && is_int(\Bitrix\Tasks\Util\User::getOccurAsId()))
		{
			$senderId = \Bitrix\Tasks\Util\User::getOccurAsId();
			return $this->getUserById($senderId);
		}

		if(\Bitrix\Tasks\Util\User::getId() && !$this->isSpawnedByAgent($optional))
		{
			return $this->getUserById(\Bitrix\Tasks\Util\User::getId());
		}

		// try to fetch fresh data from db
		$senderIdFromDb = $task->getCreatedByMemberId();
		if ($senderIdFromDb)
		{
			return $this->getUserById($senderIdFromDb);
		}

		// by default
		return $this->getUserById($task->getCreatedBy());
	}

	public function getParticipants(TaskObject $task, array $optional = []): array
	{
		return array_unique(
			array_merge(
				[$task->getCreatedBy(), $task->getResponsibleId()],
				$task->getAccompliceMembersIds(),
				$task->getAuditorMembersIds(),
				(array)($optional['additional_recepients'] ?? [])
			)
		);
	}

	public function getUserById(int $userId): ?User
	{
		if (isset($this->cache[$userId]))
		{
			return $this->cache[$userId];
		}

		$res = UserTable::query()
			->where('ID', $userId)
			->setSelect([
				'ID',
				'NAME',
				'LAST_NAME',
				'SECOND_NAME',
				'EMAIL',
				'EXTERNAL_AUTH_ID',
				'PERSONAL_GENDER',
				'NOTIFICATION_LANGUAGE_ID'
			])
			->exec()
			->fetchObject()
		;

		if (!$res)
		{
			return null;
		}

		$user = new User(
			$res->getId(),
			$res->getName(),
			$res->getNotificationLanguageId(),
			[
				'gender' => $res->getPersonalGender(),
				'last_name' => $res->getLastName(),
				'second_name' => $res->getSecondName(),
				'email' => $res->getEmail(),
				'external_auth_id' => $res->getExternalAuthId(),
			]
		);

		$this->cache[$user->getId()] = $user;

		return $user;
	}

	public function getUserTimeZoneOffset(int $userId): int
	{
		if (isset($this->timeZoneCache[$userId]))
		{
			return $this->timeZoneCache[$userId];
		}

		$this->timeZoneCache[$userId] = \Bitrix\Tasks\Util\User::getTimeZoneOffset($userId);

		return $this->timeZoneCache[$userId];
	}

	private function ignoreRecepient(int $recepientId, array $options): bool
	{
		return (
			isset($options['IGNORE_RECIPIENTS'])
			&& is_array($options['IGNORE_RECIPIENTS'])
			&& in_array($recepientId, $options['IGNORE_RECIPIENTS'], true)
		);
	}

	private function isSpawnedByAgent(array $params): bool
	{
		if (
			isset($params['SPAWNED_BY_AGENT']) &&
			($params['SPAWNED_BY_AGENT'] === true || $params['SPAWNED_BY_AGENT'] === 'Y')
		)
		{
			return true;
		}

		if (
			isset($params['spawned_by_agent']) &&
			($params['spawned_by_agent'] === true || $params['spawned_by_agent'] === 'Y')
		)
		{
			return true;
		}

		return false;
	}
}
