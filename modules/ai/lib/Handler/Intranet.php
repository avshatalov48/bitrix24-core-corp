<?php

namespace Bitrix\AI\Handler;

use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Integration;
use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;

/**
 * Handlers for module Intranet
 */
class Intranet
{
	/**
	 * Called after system user totally delete.
	 *
	 * @param Event $event
	 * @return void
	 */
	public static function onSettingsProvidersCollect(Main\Event $event): void
	{
		$zone = Loader::includeModule('bitrix24')
			? Bitrix24::getPortalZone()
			:(Application::getInstance()->getLicense()->getRegion() ?? 'ru')
		;

		if (in_array($zone, ['cn'], true))
		{
			return;
		}

		$providers = $event->getParameter('providers');

		$provider = new Integration\Intranet\Settings\AISettingsPageProvider();
		$providers[$provider->getType()] = $provider;

		$event->addResult(new Main\EventResult(Main\EventResult::SUCCESS, ['providers' => $providers]));
	}
}
