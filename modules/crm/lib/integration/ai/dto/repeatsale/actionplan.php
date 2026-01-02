<?php

namespace Bitrix\Crm\Integration\AI\Dto\RepeatSale;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Crm\Integration\AI\ErrorCode;
use Bitrix\Main\Result;

final class ActionPlan extends Dto
{
	public ?string $bestWayToContact = null;
	public ?string $salesOpportunity = null;
	public ?string $serviceImprovementSuggestions = null;
	public ?string $thinkBeforeServiceImprovementSuggestions = null;

	protected function getValidators(array $fields): array
	{
		return [
			new class($this) extends Validator {
				public function validate(array $fields): Result
				{
					$result = new Result();
					if (
						empty($fields['bestWayToContact'])
						&& empty($fields['salesOpportunity'])
						&& empty($fields['serviceImprovementSuggestions'])
						&& empty($fields['thinkBeforeServiceImprovementSuggestions'])
					)
					{
						$result->addError(ErrorCode::getInvalidPayloadError());
					}

					return $result;
				}
			},
		];
	}
}
