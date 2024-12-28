<?php

declare(strict_types=1);

namespace Bitrix\Disk\Document\OnlyOffice\Clients\CommandService;

use Bitrix\Disk\Document\OnlyOffice\Configuration;
use Bitrix\Main\DI\ServiceLocator;

class CommandServiceClientFactory
{
	public static function createCommandServiceClient(): CommandServiceClientInterface
	{
		$configuration = new Configuration();
		$cloudRegistrationData = $configuration->getCloudRegistrationData();

		if ($cloudRegistrationData)
		{
			return new ProxyServerCommandServiceClient($cloudRegistrationData['serverHost']);
		}

		return new DirectAccessCommandServiceClient(self::getApiUrlRoot(), self::getSecretKey());
	}

	protected static function getApiUrlRoot(): string
	{
		return ServiceLocator::getInstance()->get('disk.onlyofficeConfiguration')->getServer();
	}

	protected static function getSecretKey(): string
	{
		return ServiceLocator::getInstance()->get('disk.onlyofficeConfiguration')->getSecretKey();
	}
}