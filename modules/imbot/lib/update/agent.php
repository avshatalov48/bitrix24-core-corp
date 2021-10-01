<?php
namespace Bitrix\Imbot\Update;

use Bitrix\Imbot\Bot\Support24;
use Bitrix\Imbot\Bot\SupportBox;

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
		$botId = Support24::getBotId();
		if (!$botId)
		{
			return "";
		}

		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return "";
		}

		$commandList = [];
		$orm = \Bitrix\Im\Model\CommandTable::getList([
			'filter' => [
				'=MODULE_ID' => Support24::MODULE_ID,
				'=BOT_ID' => $botId,
				'=CLASS' => Support24::class,
			],
			'select' => [
				'COMMAND'
			]
		]);
		while ($row = $orm->fetch())
		{
			$commandList[] = $row['COMMAND'];
		}

		foreach (Support24::getCommandList() as $command => $commandParam)
		{
			if (!in_array($command, $commandList))
			{
				\Bitrix\Im\Command::register([
					'MODULE_ID' => Support24::MODULE_ID,
					'BOT_ID' => $botId,
					'COMMAND' => $command,
					'HIDDEN' => $commandParam['visible'] === true ? 'N' : 'Y',
					'CLASS' => $commandParam['class'] ?? Support24::class,
					'METHOD_COMMAND_ADD' => $commandParam['handler'] ?? 'onCommandAdd'
				]);
			}
		}

		return "";
	}

	/**
	 * @return string
	 */
	public static function installSupport24SessionCommand()
	{
		return self::installSupport24Command();
	}

	/**
	 * @return string
	 */
	public static function installSupportBoxCommand()
	{
		$botId = SupportBox::getBotId();
		if (!$botId)
		{
			return "";
		}

		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return "";
		}

		$commandList = [];
		$orm = \Bitrix\Im\Model\CommandTable::getList([
			'filter' => [
				'=MODULE_ID' => SupportBox::MODULE_ID,
				'=BOT_ID' => $botId,
				'=CLASS' => SupportBox::class,
			],
			'select' => [
				'COMMAND'
			]
		]);
		while ($row = $orm->fetch())
		{
			$commandList[] = $row['COMMAND'];
		}

		foreach (SupportBox::getCommandList() as $command => $commandParam)
		{
			if (!in_array($command, $commandList))
			{
				\Bitrix\Im\Command::register([
					'MODULE_ID' => SupportBox::MODULE_ID,
					'BOT_ID' => $botId,
					'COMMAND' => $command,
					'HIDDEN' => $commandParam['visible'] === true ? 'N' : 'Y',
					'CLASS' => $commandParam['class'] ?? SupportBox::class,
					'METHOD_COMMAND_ADD' => $commandParam['handler'] ?? 'onCommandAdd'
				]);
			}
		}

		return "";
	}

	/**
	 * Installs Support bot.
	 * @see SupportBox::register
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
			if (SupportBox::register())
			{
				SupportBox::addAgent();
			}
		}

		return '';
	}

	/**
	 * Installs Support bot.
	 * @see SupportBox::refreshAgent
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
			if (SupportBox::isEnabled())
			{
				SupportBox::refreshAgent();
			}
		}

		return '';
	}
}