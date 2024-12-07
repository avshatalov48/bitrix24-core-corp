<?php declare(strict_types=1);

namespace Bitrix\AI\Guard;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Intranet\Util;
use Bitrix\Main\LoaderException;

class ShowCopilotGuard
{
	public function hasAccess(null|int|string $userId): bool
	{
		if (is_null($userId))
		{
			return true;
		}

		return $this->checkAccess((int)$userId);
	}

	private function checkAccess(int $userId): bool
	{
		try
		{
			if (Loader::includeModule('intranet'))
			{
				return Util::isIntranetUser($userId);
			}
		}
		catch (LoaderException $exception)
		{
			Application::getInstance()
				->getExceptionHandler()
				->writeToLog($exception)
			;

			return false;
		}

		return true;
	}
}
