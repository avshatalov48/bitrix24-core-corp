<?php

namespace Bitrix\Extranet\Service;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Extranet\Contract;
use Bitrix\Main\ObjectNotFoundException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class ServiceContainer implements ContainerInterface
{
	private static ServiceContainer $instance;
	private ServiceLocator $serviceLocator;
	private string $prefix;

	private function __construct()
	{
		$this->serviceLocator = ServiceLocator::getInstance();
		$this->prefix = 'extranet.';
	}

	private function __clone()
	{}

	public static function getInstance(): self
	{
		if (!isset(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function has(string $id): bool
	{
		return $this->serviceLocator->has($this->prefix . $id);
	}

	/**
	 * @throws NotFoundExceptionInterface
	 * @throws ObjectNotFoundException
	 */
	public function get(string $id): mixed
	{
		return $this->serviceLocator->get($this->prefix . $id);
	}

	/**
	 * @throws NotFoundExceptionInterface
	 * @throws ObjectNotFoundException
	 */
	public function getCollaberService(): Contract\Service\CollaberService
	{
		return $this->get('service.collaber');
	}

	/**
	 * @throws NotFoundExceptionInterface
	 * @throws ObjectNotFoundException
	 */
	public function getUserService(): Contract\Service\UserService
	{
		return $this->get('service.user');
	}

	/**
	 * @throws NotFoundExceptionInterface
	 * @throws ObjectNotFoundException
	 */
	public function getExtranetUserRepository(): Contract\Repository\ExtranetUserRepository
	{
		return $this->get('repository.user');
	}
}
