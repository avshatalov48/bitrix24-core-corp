<?php

declare(strict_types=1);


namespace Bitrix\Tasks\Onboarding\Validation\Rule;

use Attribute;
use Bitrix\Main\Validation\Rule\AbstractPropertyValidationAttribute;
use Bitrix\Tasks\Onboarding\Validation\Validator\ArrayOfPositiveNumbersValidator;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ArrayOfPositiveNumbers extends AbstractPropertyValidationAttribute
{
	protected function getValidators(): array
	{
		return [
			new ArrayOfPositiveNumbersValidator(),
		];
	}
}