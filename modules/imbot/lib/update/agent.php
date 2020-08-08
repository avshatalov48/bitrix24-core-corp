<?php
namespace Bitrix\Imbot\Update;

/*
 * \CAgent::AddAgent('\\Bitrix\\Imbot\\Update\\Agent::installSupport24Command();', 'imbot', "N", 300, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+300, "FULL"));
 */

class Agent
{
	public static function installSupport24Command()
	{
		$botId = \Bitrix\Imbot\Bot\Support24::getBotId();
		if (!$botId)
		{
			return "";
		}

		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return "";
		}

		\Bitrix\Im\Command::register(Array(
			'MODULE_ID' => \Bitrix\Imbot\Bot\Support24::MODULE_ID,
			'BOT_ID' => $botId,
			'COMMAND' => 'support24',
			'HIDDEN' => 'Y',
			'CLASS' => \Bitrix\Imbot\Bot\Support24::class,
			'METHOD_COMMAND_ADD' => 'onCommandAdd'
		));

		return "";
	}
}