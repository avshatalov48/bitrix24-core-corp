<?php

namespace Bitrix\Crm\Integration\Booking;

use Bitrix\Main\Loader;

class TemplateRepository
{
	public static function getCodes(): array
	{
		if (!Loader::includeModule('booking'))
		{
			return [];
		}

		return \Bitrix\Booking\Integration\Notifications\TemplateRepository::getAllKnowTemplateCodes();
	}
}
