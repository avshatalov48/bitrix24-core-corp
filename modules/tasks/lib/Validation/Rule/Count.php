<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Validation\Rule;

use Attribute;
use Bitrix\Main\Localization\LocalizableMessageInterface;
use Bitrix\Tasks\Validation\Validator\CountValidator;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Count
{
	public function __construct(
		private readonly ?int $min = null,
		private readonly ?int $max = null,
		protected string|LocalizableMessageInterface|null $errorMessage = null,
	)
	{
	}

	protected function getValidators(): array
	{
		return [
			(new CountValidator($this->min, $this->max)),
		];
	}
}
