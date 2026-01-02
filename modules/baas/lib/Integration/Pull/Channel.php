<?php

declare(strict_types=1);

namespace Bitrix\Baas\Integration\Pull;

use Bitrix\Main;

class Channel
{
	public const NAME = 'baas_services';

	public static function add(string $command, array $params): void
	{
		if (Main\Loader::includeModule('pull') && \CPullOptions::GetNginxStatus())
		{
			\CPullWatch::AddToStack(self::NAME, [
				'module_id' => 'baas',
				'command' => $command,
				'params' => $params,
			]);
		}
	}

	public static function subscribe(): void
	{
		if (Main\Loader::includeModule('pull'))
		{
			\CPullWatch::Add(
				Main\Engine\CurrentUser::get()->getId(),
				self::NAME,
			);

		}
	}

	public static function getSettings(): array
	{
		return [
			'channelName' => self::NAME,
		];
	}
}
