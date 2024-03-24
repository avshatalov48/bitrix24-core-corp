<?php

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Tasks\Access\Role\RoleDictionary;

class MemberObject extends EO_Member
{
	public static function createCreator(int $userId, int $taskId = 0): static
	{
		return static::createFromId($userId, $taskId, RoleDictionary::ROLE_DIRECTOR);
	}

	public static function createResponsible(int $userId, int $taskId = 0): static
	{
		return static::createFromId($userId, $taskId, RoleDictionary::ROLE_RESPONSIBLE);
	}

	public static function createAccomplice(int $userId, int $taskId = 0): static
	{
		return static::createFromId($userId, $taskId, RoleDictionary::ROLE_ACCOMPLICE);
	}

	public static function createAuditor(int $userId, int $taskId = 0): static
	{
		return static::createFromId($userId, $taskId, RoleDictionary::ROLE_AUDITOR);
	}

	private static function createFromId(int $userId, int $taskId = 0, string $type = ''): static
	{
		return (new static())
			->setUserId($userId)
			->setType($type)
			->setTaskId($taskId);
	}


}