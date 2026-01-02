<?php

namespace Bitrix\Crm\ItemMiniCard\Layout\Field;

use Bitrix\Crm\Format\Money;

final class MoneyField extends AbstractField
{
	private array $values = [];

	public function __construct(
		public string $title,
	)
	{
	}

	public function addValue(int|float $opportunity, ?string $currencyId): self
	{
		$this->values[] = Money::format($opportunity, $currencyId ?? '');

		return $this;
	}

	public function getName(): string
	{
		return 'MoneyField';
	}

	public function getProps(): array
	{
		return [
			'title' => $this->title,
			'moneyList' => $this->values,
		];
	}
}
