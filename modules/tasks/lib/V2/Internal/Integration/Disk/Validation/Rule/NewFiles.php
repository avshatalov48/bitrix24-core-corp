<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Disk\Validation\Rule;

use Attribute;
use Bitrix\Main\Validation\Rule\AbstractPropertyValidationAttribute;
use Bitrix\Tasks\V2\Internal\Integration\Disk\Validation\Validator\NewFilesValidator;

#[Attribute(Attribute::TARGET_PROPERTY)]
class NewFiles extends AbstractPropertyValidationAttribute
{
	protected function getValidators(): array
	{
		return [
			new NewFilesValidator(),
		];
	}
}
