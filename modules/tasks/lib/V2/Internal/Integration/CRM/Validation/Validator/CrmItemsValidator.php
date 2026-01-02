<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\CRM\Validation\Validator;

use Bitrix\Main\Loader;
use Bitrix\Main\Validation\ValidationError;
use Bitrix\Main\Validation\ValidationResult;
use Bitrix\Main\Validation\Validator\ValidatorInterface;
use CCrmOwnerType;
use CCrmOwnerTypeAbbr;

class CrmItemsValidator implements ValidatorInterface
{
	public function validate(mixed $value): ValidationResult
	{
		$result = new ValidationResult();

		if (!is_array($value))
		{
			return $result->addError(new ValidationError(
				message: 'Not an array',
				failedValidator: $this
			));
		}

		foreach ($value as $item)
		{
			$itemResult = $this->validateItem($item);
			if (!$itemResult->isSuccess())
			{
				return $result->addErrors($itemResult->getErrors());
			}
		}

		return $result;
	}

	private function validateItem(mixed $value): ValidationResult
	{
		$result = new ValidationResult();

		if (!is_string($value))
		{
			return $result->addError(new ValidationError(
				message: 'Not a string',
				failedValidator: $this
			));
		}

		if (!Loader::includeModule('crm'))
		{
			return $result->addError(new ValidationError(
				message: 'CRM module is not installed',
				failedValidator: $this
			));
		}

		[$entityType, $entityId] = explode('_', $value);

		if ((int)$entityId <= 0)
		{
			return $result->addError(new ValidationError(
				message: 'Invalid CRM entity ID',
				failedValidator: $this
			));
		}

		$typeId = CCrmOwnerType::ResolveID(CCrmOwnerTypeAbbr::ResolveName($entityType));
		if ($typeId === CCrmOwnerType::Undefined)
		{
			return $result->addError(new ValidationError(
				message: 'Invalid CRM entity type',
				failedValidator: $this
			));
		}

		return $result;
	}
}