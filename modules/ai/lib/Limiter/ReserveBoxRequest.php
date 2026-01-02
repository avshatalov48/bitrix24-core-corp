<?php declare(strict_types=1);

namespace Bitrix\AI\Limiter;

use Bitrix\AI\Limiter\Enums\ErrorLimit;
use Bitrix\AI\Limiter\Enums\TypeLimit;

final class ReserveBoxRequest
{
	public function __construct(
		protected readonly int $cost,
		protected readonly bool $baasAvailable,
		protected readonly string $errorLimitType = ''
	)
	{
	}

	public function getCost(): int
	{
		return $this->cost;
	}

	public function getTypeLimit(): TypeLimit
	{
		if ($this->baasAvailable)
		{
			return TypeLimit::BAAS;
		}

		return TypeLimit::PROMO;
	}

	public function getErrorByLimit(): ?ErrorLimit
	{
		if (empty($this->errorLimitType))
		{
			return null;
		}

		/** @see  \Bitrix\AiProxy\Limiter\Enums\ErrorLimit::LimitIsExceededMonthly */
		if ($this->errorLimitType === 'LIMIT_IS_EXCEEDED_MONTHLY')
		{
			return ErrorLimit::PROMO_LIMIT;
		}

		/** @see  \Bitrix\AiProxy\Limiter\Enums\ErrorLimit::BaasLimit */
		if ($this->errorLimitType === 'LIMIT_IS_EXCEEDED_BAAS')
		{
			return ErrorLimit::BAAS_LIMIT;
		}

		/** @see  \Bitrix\AiProxy\Limiter\Enums\ErrorLimit::RateLimit */
		if ($this->errorLimitType === 'RATE_LIMIT')
		{
			return ErrorLimit::RATE_LIMIT;
		}

		return null;
	}

	public function isSuccess(): bool
	{
		return empty($this->errorLimitType);
	}
}
