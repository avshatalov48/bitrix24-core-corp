<?php

namespace Bitrix\Crm\ItemMiniCard\Layout\Field;

use Bitrix\Crm\ItemMiniCard\Layout\Field\Value\Phone;

final class PhoneField extends AbstractField
{
	public function __construct(
		public string $title,
		public array $phones = [],
	)
	{
	}

	public function addValue(Phone $phone): self
	{
		$this->phones[] = $phone;

		return $this;
	}

	public function getName(): string
	{
		return 'PhoneField';
	}

	public function getProps(): array
	{
		return [
			'title' => $this->title,
			'phones' => array_map(static fn (Phone $phone) => $phone->toArray(), $this->phones),
		];
	}
}
