<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\View;

use Bitrix\Rest\Integration\View\Attributes;

class FieldsFilter
{
	private array $ignoredAttributes = [];

	public function setIgnoredAttributes(array $ignoredAttributes): static
	{
		$this->ignoredAttributes = $ignoredAttributes;

		return $this;
	}

	public function toArray(): array
	{
		return [
			'filter' => [
				'ignoredAttributes' =>
					[
						Attributes::HIDDEN,
						...$this->ignoredAttributes,
					],
			]
		];
	}
}
