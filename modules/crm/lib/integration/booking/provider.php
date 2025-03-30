<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\Booking;

use Bitrix\Booking\Interfaces\ClientProviderInterface;
use Bitrix\Booking\Interfaces\DataProviderInterface;
use Bitrix\Booking\Interfaces\ProviderInterface;
use Bitrix\Main\Loader;

if (!Loader::includeModule('booking'))
{
	return;
}

class Provider implements ProviderInterface
{
	private const MODULE_ID = 'crm';

	public function getModuleId(): string
	{
		return self::MODULE_ID;
	}

	public function getClientProvider(): ClientProviderInterface|null
	{
		return new ClientProvider();
	}

	public function getDataProvider(): DataProviderInterface|null
	{
		return new DataProvider();
	}
}
