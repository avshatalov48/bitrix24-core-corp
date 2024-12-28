<?php

namespace Bitrix\Crm\Integration\AI\Dto;

use Bitrix\Crm\Dto\Caster;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Crm\Dto\Validator\NotEmptyField;
use Bitrix\Crm\Dto\Validator\ScalarCollectionField;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

final class ExtractScoringCriteriaPayload extends Dto
{
	public bool $status;
	/** @var string[] */
	public array $criteria = [];

	public function getCastByPropertyName(string $propertyName): ?Caster
	{
		return match ($propertyName) {
			'criteria' => new Caster\CollectionCaster(new Caster\StringCaster()),
			default => null,
		};
	}

	protected function getValidators(array $fields): array
	{
		return [
			new ScalarCollectionField($this, 'criteria', null, true),
			new NotEmptyField($this, 'criteria'),
			new class($this) extends Validator {
				public function validate(array $fields): Result
				{
					$result = new Result();

					if (empty($fields['status']))
					{
						$result->addError(new Error('Payload was terminated with an error', 'PAYLOAD_FAILED'));
					}

					return $result;
				}
			},
		];
	}
}
