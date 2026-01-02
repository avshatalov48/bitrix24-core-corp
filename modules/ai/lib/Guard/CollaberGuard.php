<?php declare(strict_types=1);

namespace Bitrix\AI\Guard;

use Bitrix\Intranet\Enum\UserRole;
use Bitrix\Intranet\User;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Exception;

class CollaberGuard implements Guard
{

	public function hasAccess(?int $userId = null): bool
	{
		if (is_null($userId))
		{
			return true;
		}

		return $this->checkAccess($userId);
	}

	private function checkAccess(int $userId): bool
	{
		try
		{
			if (Loader::includeModule('intranet'))
			{
				return (new User($userId))->getUserRole() === UserRole::COLLABER;
			}
		}
		catch (LoaderException | ArgumentOutOfRangeException $exception)
		{
			$this->writeExceptionToLog($exception);

			return false;
		}

		return true;
	}

	private function writeExceptionToLog(Exception $exception): void
	{
		Application::getInstance()
			->getExceptionHandler()
			->writeToLog($exception)
		;
	}
}
