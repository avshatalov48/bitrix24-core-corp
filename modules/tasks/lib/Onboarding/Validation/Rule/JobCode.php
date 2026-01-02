<?php

declare(strict_types=1);


namespace Bitrix\Tasks\Onboarding\Validation\Rule;

use Attribute;
use Bitrix\Main\Validation\Rule\AbstractPropertyValidationAttribute;
use Bitrix\Tasks\Onboarding\Validation\Validator\JobCodeValidator;

#[Attribute(Attribute::TARGET_PROPERTY)]
class JobCode extends AbstractPropertyValidationAttribute
{
	protected function getValidators(): array
	{
		return [
			new JobCodeValidator(),
		];
	}
}