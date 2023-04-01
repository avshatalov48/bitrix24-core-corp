<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Currency;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Main\ArgumentOutOfRangeException;

class Money extends ContentBlock implements TextPropertiesInterface
{
	use TextPropertiesMixin;

	private ?float $opportunity = null;
	private ?string $currencyId = null;

	public function getRendererName(): string
	{
		return 'Money';
	}

	public function getOpportunity(): ?float
	{
		return $this->opportunity;
	}

	public function setOpportunity(?float $opportunity): self
	{
		$this->opportunity = $opportunity;

		return $this;
	}

	public function getCurrencyId(): ?string
	{
		return $this->currencyId;
	}

	public function setCurrencyId(?string $currencyId): self
	{
		if (!is_null($currencyId) && !Currency::isCurrencyIdDefined($currencyId))
		{
			throw new ArgumentOutOfRangeException('currencyId', Currency::getCurrencyIds());
		}

		$this->currencyId = $currencyId;

		return $this;
	}

	protected function getProperties(): array
	{
		return array_merge(
			$this->getTextProperties(),
			[
				'opportunity' => $this->getOpportunity(),
				'currencyId' => $this->getCurrencyId(),
			]
		);
	}
}
