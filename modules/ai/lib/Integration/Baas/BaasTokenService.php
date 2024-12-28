<?php declare(strict_types=1);

namespace Bitrix\AI\Integration\Baas;

use Bitrix\Main;
use Bitrix\Baas;

class BaasTokenService
{
	/**
	 * Code service
	 */
	public const SERVICE_CODE = 'ai_copilot_token';

	/**
	 * Key consumption from consume response
	 */
	public const KEY_REQUEST_CONSUMPTION = 'consumptionId';

	protected bool $isServiceInitialized = false;
	protected ?Baas\Service $service;

	public function isAvailable(): bool
	{
		return $this->getService()?->isAvailable() === true;
	}

	/**
	 * Returns true if the package is available and active depending on the license.
	 * @return bool
	 */
	public function hasPackage(): bool
	{
		return $this->getService()?->isEnabled()
			&& Baas\Baas::getInstance()->isActive();
	}

	public function isActive(): bool
	{
		return $this->getService()?->isActive() === true;
	}

	public function isActualExpirationDate(): bool
	{
		return $this->getService()?->isActual() === true;
	}

	public function canConsume(int $tokens = 1): bool
	{
		return $this->getService()?->canConsume($tokens) === true;
	}

	/**
	 * @throws NotServiceException
	 */
	public function rollbackConsumption(string $consumptionId): Main\Result
	{
		$service = $this->getService();
		if (empty($service))
		{
			throw new NotServiceException('Not Baas service');
		}

		return $service->release($consumptionId);
	}

	/**
	 * @throws NotServiceException
	 */
	public function consume(int $tokens = 1): Main\Result
	{
		$service = $this->getService();
		if (empty($service))
		{
			throw new NotServiceException('Not Baas service');
		}

		return $service->forceConsume($tokens);
	}

	protected function getService(): ?Baas\Service
	{
		if (!empty($this->service))
		{
			return $this->service;
		}

		if ($this->isServiceInitialized)
		{
			return null;
		}

		$this->isServiceInitialized = true;
		if (
			Main\Loader::includeModule('baas')
			&& Baas\Baas::getInstance()->isAvailable()
		)
		{
			$this->service = Baas\ServiceManager::getInstance()->getByCode(static::SERVICE_CODE);
		}

		return $this->service ?? null;
	}
}
