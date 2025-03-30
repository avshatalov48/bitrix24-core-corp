<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Booking\Internals\Integration\Notifications\TemplateRepository;

class NotificationTemplateCodesProvider
{
	public static function getAll(): array
	{
		return TemplateRepository::getAllKnowTemplateCodes();
	}
}
