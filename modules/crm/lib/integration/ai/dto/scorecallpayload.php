<?php

namespace Bitrix\Crm\Integration\AI\Dto;

use Bitrix\Crm\Dto\Caster;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Crm\Dto\Validator\ObjectCollectionField;
use Bitrix\Crm\Integration\AI\ErrorCode;
use Bitrix\Main\Result;

class ScoreCallPayload extends Dto
{
	/** @var ScoringCriteria[] */
	public array $criteria = [];
	public ?string $overallSummary = null;
	public ?string $recommendations = null;

	public function getCastByPropertyName(string $propertyName): ?Caster
	{
		return match ($propertyName) {
			'criteria' => new Caster\CollectionCaster(new Caster\ObjectCaster(ScoringCriteria::class)),
			default => null,
		};
	}

	protected function getValidators(array $fields): array
	{
		return [
			new ObjectCollectionField($this, 'criteria'),
			new class($this) extends Validator {
				public function validate(array $fields): Result
				{
					$result = new Result();

					if (empty($fields['criteria']) && empty($fields['recommendations']))
					{
						$result->addError(ErrorCode::getInvalidPayloadError());
					}

					return $result;
				}
			},
		];
	}
}
