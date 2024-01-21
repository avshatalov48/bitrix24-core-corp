<?php

namespace Bitrix\Crm\Dto\Validator;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Main\Result;

final class ScalarCollectionField extends Validator
{
	protected string $fieldToCheck;
	protected ?int $maxCount;
	protected bool $onlyNotEmptyValues;

	public function __construct(Dto $dto, string $fieldToCheck, int $maxCount = null, bool $onlyNotEmptyValues = false)
	{
		parent::__construct($dto);

		$this->fieldToCheck = $fieldToCheck;
		$this->maxCount = $maxCount;
		$this->onlyNotEmptyValues = $onlyNotEmptyValues;
	}

	public function validate(array $fields): Result
	{
		$result = new Result();

		if (array_key_exists($this->fieldToCheck, $fields))
		{
			if (!is_array($fields[$this->fieldToCheck]))
			{
				$result->addError($this->getWrongFieldError($this->fieldToCheck, $this->dto->getName()));
			}
			else
			{
				foreach ($fields[$this->fieldToCheck] as $fieldKey => $fieldValue)
				{
					if ($keyValidationError = $this->getKeyValidationError($fieldKey, $this->dto->getName()))
					{
						$result->addError($keyValidationError);
					}
					if (!is_scalar($fieldValue) || ($this->onlyNotEmptyValues && empty($fieldValue)))
					{
						$result->addError($this->getWrongFieldError($this->fieldToCheck . '[' . $fieldKey . ']', $this->dto->getName()));
					}
				}
				if (!is_null($this->maxCount) && count($fields[$this->fieldToCheck]) > $this->maxCount)
				{
					$result->addError($this->getTooManyItemsError($this->fieldToCheck, $this->dto->getName(), $this->maxCount));
				}
			}
		}

		return $result;
	}
}
