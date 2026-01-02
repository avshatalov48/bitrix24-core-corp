<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field\Validator;

use Bitrix\Main\Type\Contract\Arrayable;

interface ValidatorInterface extends Arrayable
{
	public function validate(mixed $value): bool;
	public function getErrorMessage(): string;
}
