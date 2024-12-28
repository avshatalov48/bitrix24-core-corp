<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\Booking;

use Bitrix\Booking\Integration\Booking\ClientProviderInterface;
use Bitrix\Booking\Integration\Booking\DataProviderInterface;
use Bitrix\Booking\Integration\Booking\ProviderInterface;
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

	public function getClientProvider(): ?ClientProviderInterface
	{
		return new ClientProvider();
	}

	public function getDataProvider(): ?DataProviderInterface
	{
		return new DataProvider();
	}
}
