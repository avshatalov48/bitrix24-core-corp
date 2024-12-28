<?php

namespace Bitrix\BIConnector\ExternalSource\Internal\Validator;

use Bitrix\Main\ORM\Fields\Field;
use Bitrix\Main\ORM\Fields\Validators\Validator;
use Bitrix\BIConnector\ExternalSource\Type;

class TypeValidator extends Validator
{
	public function validate($value, $primary, array $row, Field $field)
	{
		if (Type::tryFrom($value) === null)
		{
			return $this->getErrorMessage($value, $field);
		}

		return true;
	}
}