<?php
namespace Bitrix\Imbot\Update;

use Bitrix\Im;
use Bitrix\Imbot;
use Bitrix\Imbot\Bot\Support24;
use Bitrix\Imbot\Bot\SupportBox;
use Bitrix\Imbot\Bot\Partner24;

/*
 * \CAgent::addAgent('\\Bitrix\\Imbot\\Update\\Agent::installSupport24Command();', 'imbot', "N", 300, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::getOffset()+300, "FULL"));
 */

final class Agent
{

	/**
	 * Installs Support bot.
	 * @see SupportBox::refreshAgent
	 * @return string
	 */
	public static function updateSupport24()
	{
		if (
			\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24')
			&& class_exists('\\Bitrix\\ImBot\\Bot\\Support24', true)
			&& method_exists('\\Bitrix\\ImBot\\Bot\\Support24', 'refreshAgent')
		)
		{
			Support24::addAgent([
				'class' => Support24::class,
				'agent' => 'refreshAgent()',
				'regular' => false,
				'delay' => random_int(60, 6600),
			]);
		}

		return '';
	}


	/**
	 * @return string
	 */
	public static function installSupport24Command()
	{
		if (
			\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24')
			&& class_exists('\\Bitrix\\ImBot\\Bot\\Support24', true)
			&& method_exists('\\Bitrix\\ImBot\\Bot\\Support24', 'registerCommands')
		)
		{
			Support24::registerCommands();
		}
		return '';
	}

	/**
	 * @return string
	 */
	public static function installSupport24App()
	{
		if (
			\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24')
			&& class_exists('\\Bitrix\\ImBot\\Bot\\Support24', true)
			&& method_exists('\\Bitrix\\ImBot\\Bot\\Support24', 'registerApps')
		)
		{
			Support24::registerApps();
		}
		return '';
	}

	/**
	 * @deprecated
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
		if (
			!\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24')
			&& class_exists('\\Bitrix\\ImBot\\Bot\\SupportBox', true)
			&& method_exists('\\Bitrix\\ImBot\\Bot\\SupportBox', 'registerCommands')
		)
		{
			SupportBox::registerCommands();
		}
		return '';
	}

	/**
	 * @return string
	 */
	public static function installSupportBoxApp()
	{
		if (
			!\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24')
			&& class_exists('\\Bitrix\\ImBot\\Bot\\SupportBox', true)
			&& method_exists('\\Bitrix\\ImBot\\Bot\\SupportBox', 'registerApps')
		)
		{
			SupportBox::registerApps();
		}
		return '';
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
				SupportBox::addAgent([
					'class' => SupportBox::class,
					'agent' => 'refreshAgent()',
					'regular' => false,
					'delay' => random_int(60, 3600),
				]);
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
			\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24')
			|| !\Bitrix\Main\Loader::includeModule('im')
			|| !\Bitrix\Main\Loader::includeModule('imbot')
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

	/**
	 * @return string
	 */
	public static function installPartner24Command()
	{
		if (
			!\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24')
			|| !\Bitrix\Main\Loader::includeModule('im')
			|| !\Bitrix\Main\Loader::includeModule('imbot')
		)
		{
			return '';
		}

		if (
			class_exists('\\Bitrix\\ImBot\\Bot\\Partner24', true)
			&& method_exists('\\Bitrix\\ImBot\\Bot\\Partner24', 'registerCommands')
		)
		{
			if (Partner24::getBotId() > 0)
			{
				Partner24::registerCommands(Partner24::getBotId());
			}
		}
		return '';
	}
}