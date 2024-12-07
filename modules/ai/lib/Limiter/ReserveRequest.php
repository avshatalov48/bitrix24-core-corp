<?php declare(strict_types=1);

namespace Bitrix\AI\Limiter;

use Bitrix\AI\Limiter\Enums\ErrorLimit;
use Bitrix\AI\Limiter\Enums\TypeLimit;

final class ReserveRequest
{
	protected ErrorLimit $errorLimit;
	protected string $promoLimitCode = '';

	public function __construct(
		protected readonly TypeLimit $typeLimit,
		protected readonly Usage $limiter,
		protected readonly int $cost,
	)
	{
	}

	public function setErrorLimit(ErrorLimit $errorLimit): self
	{
		$this->errorLimit = $errorLimit;

		return $this;
	}

	public function setPromoLimitCode(string $promoLimitCode): self
	{
		if (!empty($promoLimitCode))
		{
			$this->promoLimitCode = $promoLimitCode;
		}

		return $this;
	}

	public function getTypeLimit(): TypeLimit
	{
		return $this->typeLimit;
	}

	public function getErrorLimit(): ErrorLimit
	{
		return $this->errorLimit;
	}

	public function getPromoLimitCode(): string
	{
		return $this->promoLimitCode;
	}

	public function getLimiter(): Usage
	{
		return $this->limiter;
	}

	public function getCost(): int
	{
		return $this->cost;
	}

	public function isSuccess(): bool
	{
		return empty($this->errorLimit);
	}
}
