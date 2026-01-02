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
		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		$id = self::getId();
		if ($id === null)
		{
			return null;
		}

		return Container::getInstance()->getCompanyBroker()->getTitle($id);
	}

	public static function getId(): int|null
	{
		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		$id = (int)EntityLink::getDefaultMyCompanyId();

		return $id > 0 ? $id : null;
	}
}
