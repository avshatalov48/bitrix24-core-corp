<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Notifications;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Notifications\VirtualWhatsApp;

class LegalEntityProvider
{
	public function isRu(): bool|null
	{
		if (!Loader::includeModule('notifications'))
		{
			return null;
		}

		$region = Application::getInstance()->getLicense()->getRegion() ?? 'en';

		return VirtualWhatsApp::getAreaToVirtualWhatsAppRegion($region) === VirtualWhatsApp::RU;
	}
}
