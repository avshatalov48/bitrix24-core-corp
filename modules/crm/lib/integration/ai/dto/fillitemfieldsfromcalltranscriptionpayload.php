<?php

namespace Bitrix\Crm\Integration\AI\Dto;

use Bitrix\Crm\Dto\Caster;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Crm\Dto\Validator\ObjectCollectionField;
use Bitrix\Crm\Integration\AI\ErrorCode;
use Bitrix\Main\Result;

class FillItemFieldsFromCallTranscriptionPayload extends Dto
{
	/** @var SingleFieldFillPayload[] */
	public array $singleFields = [];
	/** @var MultipleFieldFillPayload[] */
	public array $multipleFields = [];
	public ?string $unallocatedData = null;

	public function getCastByPropertyName(string $propertyName): ?Caster
	{
		return match ($propertyName) {
			'singleFields' => new Caster\CollectionCaster(new Caster\ObjectCaster(SingleFieldFillPayload::class)),
			'multipleFields' => new Caster\CollectionCaster(new Caster\ObjectCaster(MultipleFieldFillPayload::class)),
			default => null,
		};
	}

	protected function getValidators(array $fields): array
	{
		return [
			new ObjectCollectionField($this, 'singleFields'),
			new ObjectCollectionField($this, 'multipleFields'),
			new class($this) extends Validator {
				public function validate(array $fields): Result
				{
					$result = new Result();

					if (
						empty($fields['singleFields'])
						&& empty($fields['multipleFields'])
						&& empty($fields['unallocatedData'])
					)
					{
						$result->addError(ErrorCode::getInvalidPayloadError());
					}

					return $result;
				}
			}
		];
	}
}
