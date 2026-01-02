<?php

declare(strict_types=1);

namespace Bitrix\Tasks\DI;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Validation\ValidationService;
use Bitrix\Tasks\Internals\Trait\SingletonTrait;
use Bitrix\Tasks\V2\Internal\Logger;

abstract class AbstractContainer
{
	use SingletonTrait;

	private static ?array $diConfig = null;

	private ServiceLocator $locator;

	public function getValidationService(): ValidationService
	{
		return $this->get('main.validation.service');
	}

	public function getLogger(): Logger
	{
		return $this->get(Logger::class);
	}

	public function get(string $idOrClass): object
	{
		return $this->locator->get($idOrClass);
	}

	protected function init(): void
	{
		$this->locator = ServiceLocator::getInstance();
	}
}
