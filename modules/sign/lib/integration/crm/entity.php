<?php

namespace Bitrix\Sign\Integration\CRM;

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;

class Entity
{
	public static function getDetailPageUri(int $entityTypeId, int $entityId): ?Uri
	{
		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		return \Bitrix\Crm\Service\Container::getInstance()
			->getRouter()
			->getItemDetailUrl($entityTypeId, $entityId)
		;
	}

	public static function getContactName(int $entityId): ?string
	{
		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		return \Bitrix\Crm\Service\Container::getInstance()
			->getContactBroker()->getFormattedName($entityId)
		;
	}
}