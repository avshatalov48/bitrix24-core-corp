<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Main\Application;

class NotificationsLanguageProvider
{
	public function getLanguageId(): string
	{
		$region = Application::getInstance()->getLicense()->getRegion() ?? 'en';

		return match ($region) {
			'ru', 'kz' => 'ru',
			'br' => 'pt',
			default => 'en',
		};
	}
}
