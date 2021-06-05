<?php

namespace Bitrix\Crm\Service;

class EventHandler
{
	public static function onGetUserFieldTypeFactory(): array
	{
		return [
			\Bitrix\Main\DI\ServiceLocator::getInstance()->get('crm.type.factory'),
		];
	}
}
