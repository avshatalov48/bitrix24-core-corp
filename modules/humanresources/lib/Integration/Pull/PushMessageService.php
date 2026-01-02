<?php

namespace Bitrix\HumanResources\Integration\Pull;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Pull;

final class PushMessageService
{
	private const MODULE_ID = 'humanresources';

	public function isAvailable(): bool
	{
		return Loader::includeModule('pull');
	}

	public function send(string $command, array $params, array $userIds = []): bool
	{
		if (!$this->isAvailable())
		{
			return false;
		}

		if (empty($userIds))
		{
			$userIds = [CurrentUser::get()?->getId() ?? 0];
		}

		return Pull\Event::add(
			$userIds,
			[
				'module_id' => self::MODULE_ID,
				'command' => $command,
				'params' => $params,
			]
		);
	}
}