<?php

namespace Bitrix\Crm\Integration\AI\Dto;

use Bitrix\Crm\Dto\Caster;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Crm\Dto\Validator\NotEmptyField;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

class MultipleFieldFillPayload extends Dto
{
	public string $name;

	/** @var string[] */
	public array $aiValues;

	public bool $isApplied = false;
	public bool $isConflict = false;

	public function getCastByPropertyName(string $propertyName): ?Caster
	{
		return match ($propertyName) {
			'aiValues' => new Caster\CollectionCaster(new Caster\StringCaster()),
			default => null,
		};
	}

	protected function getValidators(array $fields): array
	{
		return [
			new NotEmptyField($this, 'name'),
			new NotEmptyField($this, 'aiValues'),
			new class($this) extends Validator {
				public function validate(array $fields): Result
				{
					$result = new Result();

					if (array_key_exists('aiValues', $fields))
					{
						if (!is_array($fields['aiValues']))
						{
							$result->addError($this->getWrongFieldError('aiValues', $this->dto->getName()));
						}
						else
						{
							foreach ($fields['aiValues'] as $fieldKey => $fieldValue)
							{
								if ($keyValidationError = $this->getKeyValidationError($fieldKey, $this->dto->getName()))
								{
									$result->addError($keyValidationError);
								}

								$isAllowedFieldType = is_scalar($fieldValue)
									|| $fieldValue instanceof DateTime
								;

								if (empty($fieldValue) || !$isAllowedFieldType)
								{
									$result->addError($this->getWrongFieldError('aiValues' . '[' . $fieldKey . ']', $this->dto->getName()));
								}
							}
						}
					}

					return $result;
				}
			},
		];
	}
}
