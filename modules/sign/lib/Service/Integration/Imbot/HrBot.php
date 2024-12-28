<?php

namespace Bitrix\Sign\Service\Integration\Imbot;

use Bitrix\Sign\Service\Container;

class HrBot
{
	public function getBotUserId(): ?int
	{
		if (!\Bitrix\Main\Loader::includeModule('imbot'))
		{
			return null;
		}

		if (!class_exists(\Bitrix\Imbot\Bot\HrBot::class))
		{
			return null;
		}

		return \Bitrix\Imbot\Bot\HrBot::getBotIdOrRegister() ?: null;
	}

	public static function isAvailable(): bool
	{
		return Container::instance()->getImService()->isAvailable();
	}
}
