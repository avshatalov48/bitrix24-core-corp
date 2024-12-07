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
		$recipientIds = [];

		foreach ($this->getParticipants($task, $optional) as $id)
		{
			if ($id === null || $this->ignoreRecepient((int)$id, $optional))
			{
				continue;
			}

			if (
				(int)$id === $sender->getId()
				&& !$this->isSpawnedByAgent($optional)
				&& !$this->isSpawnedByWorkFlow($optional)
				&& !$this->isOccurredUserIdSet()
			)
			{
				continue;
			}

			$recipientIds[] = (int)$id;
		}

		return $this->getUsersByIds(array_unique($recipientIds));
	}

	public function getSender(TaskObject $task, array $optional = []): ?User
	{
		if(isset($optional['AUTHOR_ID']) && (int)$optional['AUTHOR_ID'] > 0)
		{
			return $this->getUserById((int)$optional['AUTHOR_ID']);
		}

		if($this->isOccurredUserIdSet())
		{
			$senderId = (int)\Bitrix\Tasks\Util\User::getOccurAsId();
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
				$task->getAllMemberIds($optional['forceFetchMembers'] ?? true),
				(array)($optional['additional_recepients'] ?? [])
			)
		);
	}

	public function getUserById(?int $userId): ?User
	{
		if ($userId === null)
		{
			return null;
		}

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

	public function getUsersByIds(array $userIds): array
	{
		if (empty($userIds))
		{
			return [];
		}

		$unCachedUserIds = $this->extractUnCachedUserIds($userIds);
		if (empty($unCachedUserIds))
		{
			$cachedUserIds = $this->extractCachedUserIds($userIds);
			return array_map(fn (int $userId): User => $this->cache[$userId], $cachedUserIds);
		}

		$users = UserTable::query()
			->whereIn('ID', $unCachedUserIds)
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
			->fetchCollection()
		;

		if (!$users)
		{
			return [];
		}

		foreach ($users as $user)
		{
			$this->cache[$user->getId()] = new User(
				$user->getId(),
				$user->getName(),
				$user->getNotificationLanguageId(),
				[
					'gender' => $user->getPersonalGender(),
					'last_name' => $user->getLastName(),
					'second_name' => $user->getSecondName(),
					'email' => $user->getEmail(),
					'external_auth_id' => $user->getExternalAuthId(),
				]
			);
		}

		return array_filter(array_map(fn (int $userId): ?User => $this->cache[$userId] ?? null, $userIds));
	}

	public function getUserTimeZoneOffset(int $userId, bool $force = false): int
	{
		return \Bitrix\Tasks\Util\User::getTimeZoneOffset($userId, false, $force);
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

	private function isSpawnedByWorkFlow(array $params): bool
	{
		if (
			isset($params['SPAWNED_BY_WORKFLOW']) &&
			($params['SPAWNED_BY_WORKFLOW'] === true || $params['SPAWNED_BY_WORKFLOW'] === 'Y')
		)
		{
			return true;
		}

		if (
			isset($params['spawned_by_workflow']) &&
			($params['spawned_by_workflow'] === true || $params['spawned_by_workflow'] === 'Y')
		)
		{
			return true;
		}

		return false;
	}

	private function isOccurredUserIdSet(): bool
	{
		$occurredUserId = \Bitrix\Tasks\Util\User::getOccurAsId();
		return $occurredUserId && is_int($occurredUserId);
	}

	private function extractCachedUserIds(array $userIds): array
	{
		return array_filter($userIds, fn (int $userId): bool => isset($this->cache[$userId]));
	}

	private function extractUnCachedUserIds(array $userIds): array
	{
		return array_filter($userIds, fn (int $userId): bool => !isset($this->cache[$userId]));
	}
}
