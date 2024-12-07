<?php

namespace Bitrix\Intranet\MainPage;

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\EventManager;

class Publisher
{
	private const MODULE_ID = 'intranet';
	private const ACTIVITY_OPTION_NAME = 'main-page-activity';
	private const ON = 'Y';
	private const OFF = 'N';

	public function publish(): void
	{
		Option::set('intranet', 'left_menu_first_page', (new Url)->getPublic()->getUri(), false);
		Option::set(self::MODULE_ID, self::ACTIVITY_OPTION_NAME, self::ON);
		EventManager::getInstance()->registerEventHandler(
			'intranet', 'onLicenseHasChanged',
			'bitrix24', EventHandler::class, 'onLicenseHasChanged');
		ServiceLocator::getInstance()->get('intranet.customSection.manager')->clearLeftMenuCache();
	}

	public function withdraw(): void
	{
		Option::set(self::MODULE_ID, self::ACTIVITY_OPTION_NAME, self::OFF);
		ToolsManager::getInstance()->getFirstPageChanger()->changeForAllUsers();
		EventManager::getInstance()->unregisterEventHandler(
			'intranet', 'onLicenseHasChanged',
			'bitrix24', EventHandler::class, 'onLicenseHasChanged');
		ServiceLocator::getInstance()->get('intranet.customSection.manager')->clearLeftMenuCache();
	}

	public function isPublished(): bool
	{
		return Option::get(self::MODULE_ID, self::ACTIVITY_OPTION_NAME, self::OFF) === self::ON;
	}
}