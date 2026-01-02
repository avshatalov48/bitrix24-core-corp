<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\CRM\Validation\Rule;

use Attribute;
use Bitrix\Main\Validation\Rule\AbstractPropertyValidationAttribute;
use Bitrix\Tasks\V2\Internal\Integration\CRM\Validation\Validator\CrmItemsValidator;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class CrmItems extends AbstractPropertyValidationAttribute
{
	protected function getValidators(): array
	{
		return [
			new CrmItemsValidator(),
		];
	}
}