<?php
namespace Bitrix\Imbot\Update;

/*
 * \CAgent::AddAgent('\\Bitrix\\Imbot\\Update\\Agent::installSupport24Command();', 'imbot', "N", 300, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+300, "FULL"));
 */

final class Agent
{
	/**
	 * @return string
	 */
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

	/**
	 * Installs Support bot.
	 * @see \Bitrix\ImBot\Bot\SupportBox::register
	 * @return string
	 */
	public static function installSupportBox()
	{
		if (
			\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24') ||
			!\Bitrix\Main\Loader::includeModule('im') ||
			!\Bitrix\Main\Loader::includeModule('imbot')
		)
		{
			return '';
		}

		if (class_exists('\\Bitrix\\ImBot\\Bot\\SupportBox', true))
		{
			if (\Bitrix\ImBot\Bot\SupportBox::register())
			{
				\Bitrix\ImBot\Bot\SupportBox::addAgent();
			}
		}

		return '';
	}

	/**
	 * Installs Support bot.
	 * @see \Bitrix\ImBot\Bot\SupportBox::refreshAgent
	 * @return string
	 */
	public static function updateSupportBox()
	{
		if (
			\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24') ||
			!\Bitrix\Main\Loader::includeModule('im') ||
			!\Bitrix\Main\Loader::includeModule('imbot')
		)
		{
			return '';
		}

		if (class_exists('\\Bitrix\\ImBot\\Bot\\SupportBox', true))
		{
			if (\Bitrix\ImBot\Bot\SupportBox::isEnabled())
			{
				\Bitrix\ImBot\Bot\SupportBox::refreshAgent();
			}
		}

		return '';
	}
}