<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Internals\UserOption\Service;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Throwable;

class AutoMuteService
{
	public function disable(int $userId): Result
	{
		$result = new Result();

		if ($userId <= 0)
		{
			$result->addError(new Error('Invalid user id'));

			return $result;
		}

		try
		{
			$this->removeAutoMute($userId);
		}
		catch (Throwable $t)
		{
			$result->addError(Error::createFromThrowable($t));
		}

		return $result;
	}

	public function enable(int $userId): Result
	{
		$result = new Result();

		if ($userId <= 0)
		{
			$result->addError(new Error('Invalid user id'));

			return $result;
		}

		try
		{
			$this->addAutoMute($userId);
		}
		catch (Throwable $t)
		{
			$result->addError(Error::createFromThrowable($t));
		}

		return $result;
	}

	public function getDisabledAutoMuteUsers(): array
	{
		$value = Option::get('tasks', 'tasks_except_mute_users');

		try
		{
			$users = unserialize($value, ['allowed_classes' => false]);
			if (is_array($users))
			{
				return $users;
			}

			return [];
		}
		catch (Throwable)
		{
			return [];
		}
	}

	protected function addAutoMute(int $userId): void
	{
		$disabledAutoMuteUsers = $this->getDisabledAutoMuteUsers();

		$index = array_search($userId, $disabledAutoMuteUsers, true);

		if ($index !== false)
		{
			unset($disabledAutoMuteUsers[$index]);

			$disabledAutoMuteUsers = array_values($disabledAutoMuteUsers);
		}

		$value = serialize($disabledAutoMuteUsers);

		Option::set('tasks', 'tasks_except_mute_users', $value);
	}

	protected function removeAutoMute(int $userId): void
	{
		$disabledAutoMuteUsers = $this->getDisabledAutoMuteUsers();
		$disabledAutoMuteUsers[] = $userId;

		$value = serialize($disabledAutoMuteUsers);

		Option::set('tasks', 'tasks_except_mute_users', $value);
	}
}