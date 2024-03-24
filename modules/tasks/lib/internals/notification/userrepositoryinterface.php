<?php

namespace Bitrix\Tasks\Internals\Notification;

use Bitrix\Tasks\Internals\TaskObject;

interface UserRepositoryInterface
{
	/**
	 * It should return an array of Internals/Notification/User objects participating in provided task
	 * @param TaskObject $task
	 * @param User $sender
	 * @param array $optional
	 * @return array
	 */
	public function getRecepients(TaskObject $task, User $sender, array $optional = []): array;
	public function getSender(TaskObject $task, array $optional = []): ?User;
	public function getUserById(?int $userId): ?User;
	public function getUsersByIds(array $userIds): array;
	public function getUserTimeZoneOffset(int $userId): int;

	/**
	 * It should return an array of user ids participating in provided task
	 * @param TaskObject $task
	 * @param array $optional
	 * @return array
	 */
	public function getParticipants(TaskObject $task, array $optional = []): array;
}