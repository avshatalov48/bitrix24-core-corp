<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Crm;

use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;

class MyCompany
{
	public static function getName(): string|null
	{
		if (Loader::includeModule('crm'))
		{
			return Container::getInstance()->getCompanyBroker()->getTitle(EntityLink::getDefaultMyCompanyId());
		}

		return null;
	}
}
