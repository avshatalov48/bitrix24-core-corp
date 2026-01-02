<?php

namespace Bitrix\Crm\Integration\AI\ContextCollector\StagesQueryModificator;

class ItemsSum
{
	public function __construct(
		private float $sum,
		private ?string $currency,
	)
	{
	}

	public function getSum(): float
	{
		return $this->sum;
	}

	public function setSum(float $sum): ItemsSum
	{
		$this->sum = $sum;

		return $this;
	}

	public function getCurrency(): string
	{
		return $this->currency;
	}

	public function setCurrency(?string $currency): ItemsSum
	{
		$this->currency = $currency;

		return $this;
	}

	public function toArray(): array
	{
		return [
			'sum' => $this->sum,
			'currency' => $this->currency,
		];
	}
}
