<?php

namespace Bitrix\Stafftrack\Integration\Pull;

use Bitrix\Main;

class PushService
{
	public const MODULE_NAME = 'stafftrack';

	public static function subscribeToTag(string $tag): void
	{
		if (!Main\Loader::includeModule('pull'))
		{
			return;
		}

		\CPullWatch::Add(Main\Engine\CurrentUser::get()->getId(), $tag, true);
	}

	public static function sendByTag(string $tag, PushCommand $command, array $params): void
	{
		if (!Main\Loader::includeModule('pull'))
		{
			return;
		}

		\CPullWatch::AddToStack(
			$tag,
			[
				'module_id' => self::MODULE_NAME,
				'command' => $command->value,
				'params' => $params,
			],
		);
	}

	public static function send(int $userId, PushCommand $command, array $params): void
	{
		if (!Main\Loader::includeModule('pull'))
		{
			return;
		}

		\Bitrix\Pull\Event::add(
			$userId,
			[
				'module_id' => self::MODULE_NAME,
				'command' => $command->value,
				'params' => $params,
			],
		);
	}
}