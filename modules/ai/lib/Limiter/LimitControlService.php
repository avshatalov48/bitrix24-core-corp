<?php declare(strict_types=1);

namespace Bitrix\AI\Limiter;

use Bitrix\AI\Integration\Baas\BaasTokenService;
use Bitrix\AI\Integration\Baas\NotServiceException;
use Bitrix\AI\Limiter\Enums\ErrorLimit;
use Bitrix\AI\Limiter\Enums\TypeLimit;
use Bitrix\AI\Limiter\Repository\BaasPackageRepository;
use Bitrix\AI\Limiter\Repository\CounterRepository;
use Bitrix\Main\Type\Date;

/**
 *  Service for control limits in ai
 */
class LimitControlService
{
	protected const DEFAULT_COST = 1;
	protected const FREE_COST = 0;

	protected BaasTokenService $baasTokenService;
	protected CounterRepository $counterRepository;
	protected BaasPackageRepository $baasPackageRepository;

	public function commitRequest(ReserveRequest $reservedRequest): string
	{
		if ($reservedRequest->getCost() === self::FREE_COST)
		{
			return '';
		}

		if (!$reservedRequest->isSuccess())
		{
			return '';
		}

		if ($reservedRequest->getTypeLimit() === TypeLimit::PROMO)
		{
			$reservedRequest->getLimiter()->increment($reservedRequest->getCost());

			return '';
		}

		if ($reservedRequest->getTypeLimit() === TypeLimit::BAAS)
		{
			return $this->consumeBaasLimits($reservedRequest->getCost());
		}

		return '';
	}

	/**
	 * Main logic for check limits
	 */
	public function reserveRequest(Usage $limiter, int $cost = self::DEFAULT_COST): ReserveRequest
	{
		$reserveRequest = new ReserveRequest(TypeLimit::PROMO, $limiter, $cost);
		if ($cost === self::FREE_COST)
		{
			return $reserveRequest;
		}

		if (!$this->isAvailableBaas())
		{
			return $this->getReserveRequestInPromoLimit($reserveRequest, ErrorLimit::PROMO_LIMIT);
		}

		$dateExpired = $this->getDateExpired();
		if (empty($dateExpired))
		{
			return $this->getReserveRequestInPromoLimit($reserveRequest, ErrorLimit::BAAS_LIMIT);
		}

		if ($dateExpired->getTimestamp() >= $this->getTimeOnStartDay())
		{
			if ($this->canConsumeInBaas($cost))
			{
				return new ReserveRequest(TypeLimit::BAAS, $limiter, $cost);
			}

			return $this->getReserveRequestInPromoWhenNotBaasRequests($reserveRequest, $dateExpired);
		}

		if ($this->getTimestampFirstDayOnNextMonths($dateExpired) > time())
		{
			return $reserveRequest->setErrorLimit(ErrorLimit::BAAS_LIMIT);
		}

		return $this->getReserveRequestInPromoLimit($reserveRequest, ErrorLimit::BAAS_LIMIT);
	}

	public function rollbackConsumption(Usage $limiter, int $cost, string $consumptionId): void
	{
		if ($cost === self::FREE_COST)
		{
			return;
		}

		if (!empty($consumptionId))
		{
			try
			{
				$result = $this->getBaasService()->rollbackConsumption($consumptionId);
				if (!$result->isSuccess())
				{
					AddMessage2Log(
						'AI_LIMIT_CONTROL: Error in rollbackConsumption '
						. implode(' ', $result->getErrorMessages())
					);
				}
			}
			catch (NotServiceException $exception)
			{
				AddMessage2Log('AI_LIMIT_CONTROL: ' . $exception->getMessage() . ' for limiter in engine');
			}
		}
		else
		{
			$limiter->decrement($cost);
		}
	}

	protected function getDateExpired(): ?Date
	{
		$dateExpiredInfo = $this->getBaasPackageRepository()->getLatestPackageByExpiration();
		if (empty($dateExpiredInfo['DATE_EXPIRED']))
		{
			return null;
		}

		return $dateExpiredInfo['DATE_EXPIRED'];
	}

	protected function getDateLastRequest(): ?Date
	{
		$lastDateInfo = $this->getCounterRepository()->getLastDate();
		if (empty($lastDateInfo) || empty($lastDateInfo['VALUE']))
		{
			return null;
		}

		$timestamp = strtotime($lastDateInfo['VALUE']);
		if ($timestamp === false)
		{
			AddMessage2Log(
				'AI_LIMIT_CONTROL: In last request - ' . var_export($lastDateInfo['VALUE'], true)
			);
			return null;
		}

		return Date::createFromTimestamp($timestamp);
	}

	protected function getReserveRequestInPromoWhenNotBaasRequests(
		ReserveRequest $reserveRequest,
		Date $dateExpired
	): ReserveRequest
	{
		if ($this->getTimestampFirstDayOnNextMonths($dateExpired) < time())
		{
			return $this->getReserveRequestInPromoLimit($reserveRequest, ErrorLimit::BAAS_LIMIT);
		}

		$dateLastRequest = $this->getDateLastRequest();
		if (
			!empty($dateLastRequest)
			&& ($this->getTimestampFirstDayOnNextMonths($dateLastRequest) < time())
		)
		{
			return $this->getReserveRequestInPromoLimit($reserveRequest, ErrorLimit::BAAS_LIMIT);
		}

		return $reserveRequest->setErrorLimit(ErrorLimit::BAAS_LIMIT);
	}

	protected function getTimestampFirstDayOnNextMonths(Date $date): bool|int
	{
		$dataString = date('Y-m-d', $date->getTimestamp());
		return (new \DateTime($dataString))
			->modify('first day of next month')
			->getTimestamp()
		;
	}

	protected function consumeBaasLimits(int $cost): string
	{
		try
		{
			$result = $this->getBaasService()->consume($cost);
		}
		catch (NotServiceException $exception)
		{
			AddMessage2Log('AI_LIMIT_CONTROL: ' . $exception->getMessage() . ' for limiter');

			return '';
		}

		if (!$result->isSuccess())
		{
			AddMessage2Log(
				'AI_LIMIT_CONTROL: BAAS_consume ' . implode(', ', $result->getErrorMessages())
			);
		}

		if ($cost > 0)
		{
			$this->getCounterRepository()->updateLastRequest();
		}

		$data = $result->getData();
		if (empty($data[BaasTokenService::KEY_REQUEST_CONSUMPTION]))
		{
			return '';
		}

		return (string)$data[BaasTokenService::KEY_REQUEST_CONSUMPTION];
	}

	protected function getReserveRequestInPromoLimit(
		ReserveRequest $reserveRequest,
		ErrorLimit $errorLimit
	): ReserveRequest
	{
		$promoLimitCode = '';
		if ($reserveRequest->getLimiter()->isInLimit($promoLimitCode, $reserveRequest->getCost()))
		{
			return $reserveRequest;
		}

		return $reserveRequest
			->setErrorLimit($errorLimit)
			->setPromoLimitCode($promoLimitCode)
		;
	}

	protected function getTimeOnStartDay(): int
	{
		return (int)strtotime(date('Y-m-d'));
	}

	public function isAvailableBaas(): bool
	{
		return $this->getBaasService()->isAvailable();
	}

	protected function canConsumeInBaas(int $cost): bool
	{
		return $this->getBaasService()->canConsume($cost);
	}

	protected function getBaasService(): BaasTokenService
	{
		if (empty($this->baasTokenService))
		{
			$this->baasTokenService = new BaasTokenService();
		}

		return $this->baasTokenService;
	}

	protected function getCounterRepository(): CounterRepository
	{
		if (empty($this->counterRepository))
		{
			$this->counterRepository = new CounterRepository();
		}

		return $this->counterRepository;
	}

	protected function getBaasPackageRepository(): BaasPackageRepository
	{
		if (empty($this->baasPackageRepository))
		{
			$this->baasPackageRepository = new BaasPackageRepository();
		}

		return $this->baasPackageRepository;
	}
}
