<?php

declare(strict_types=1);

namespace Bitrix\Disk\Document\OnlyOffice\Clients\CommandService;

use Bitrix\Disk\Document\OnlyOffice\Configuration;
use Bitrix\Main\Config\ConfigurationException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ObjectNotFoundException;
use Psr\Container\NotFoundExceptionInterface;

class CommandServiceClientFactory
{
	/**
	 * @return CommandServiceClientInterface
	 * @throws ConfigurationException
	 * @throws NotFoundExceptionInterface
	 * @throws ObjectNotFoundException
	 */
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

	/**
	 * @return string
	 * @throws ConfigurationException
	 * @throws ObjectNotFoundException
	 * @throws NotFoundExceptionInterface
	 */
	protected static function getApiUrlRoot(): string
	{
		$server = ServiceLocator::getInstance()->get('disk.onlyofficeConfiguration')->getServer();

		if (empty($server))
		{
			throw new ConfigurationException('OnlyOffice server configuration is not configured');
		}

		return $server;
	}

	/**
	 * @return string
	 * @throws ConfigurationException
	 * @throws ObjectNotFoundException
	 * @throws NotFoundExceptionInterface
	 */
	protected static function getSecretKey(): string
	{
		$secretKey = ServiceLocator::getInstance()->get('disk.onlyofficeConfiguration')->getSecretKey();

		if (empty($secretKey))
		{
			throw new ConfigurationException('OnlyOffice secret key configuration is not configured');
		}

		return $secretKey;
	}
}