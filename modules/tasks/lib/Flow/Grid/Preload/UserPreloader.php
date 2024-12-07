<?php

namespace Bitrix\Tasks\Flow\Grid\Preload;

use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Provider\UserProvider;
use Bitrix\Tasks\Flow\User\User;
use Bitrix\Tasks\Internals\Log\Logger;

class UserPreloader
{
	private static array $storage = [];

	private UserProvider $userProvider;

	public function __construct()
	{
		$this->init();
	}

	final public function preload(int ...$userIds): void
	{
		try
		{
			$users = $this->userProvider->getUsersInfo($userIds);
			foreach ($users as $userId => $user)
			{
				static::$storage[$userId] = $user;
			}
		}
		catch (SystemException $e)
		{
			Logger::logThrowable($e);
		}
	}

	final public function get(int $userId): ?User
	{
		return static::$storage[$userId] ?? null;
	}

	private function init(): void
	{
		$this->userProvider = new UserProvider();
	}
}