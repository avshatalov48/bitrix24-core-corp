<?php

namespace Bitrix\Crm\Dto\Validator;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Main\Result;

final class ObjectCollectionField extends Validator
{
	protected string $fieldToCheck;
	protected ?int $maxCount;

	public function __construct(Dto $dto, string $fieldToCheck, int $maxCount = null)
	{
		parent::__construct($dto);

		$this->fieldToCheck = $fieldToCheck;
		$this->maxCount = $maxCount;
	}

	public function validate(array $fields): Result
	{
		$result = new Result();

		if (array_key_exists($this->fieldToCheck, $fields))
		{
			if (is_array($fields[$this->fieldToCheck]))
			{
				foreach ($fields[$this->fieldToCheck] as $fieldKey => $fieldValue)
				{
					if ($keyValidationError = $this->getKeyValidationError($fieldKey, $this->dto->getName()))
					{
						$result->addError($keyValidationError);
					}
					if (!is_array($fieldValue))
					{
						$result->addError($this->getWrongFieldError($this->fieldToCheck . '[' . $fieldKey . ']', $this->dto->getName()));
					}
				}
				if (!is_null($this->maxCount) && count($fields[$this->fieldToCheck]) > $this->maxCount)
				{
					$result->addError($this->getTooManyItemsError($this->fieldToCheck, $this->dto->getName(), $this->maxCount));
				}
			}
			else
			{
				$result->addError($this->getWrongFieldError($this->fieldToCheck, $this->dto->getName()));
			}
		}

		return $result;
	}
}
