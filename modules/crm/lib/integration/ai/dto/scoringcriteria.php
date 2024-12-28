<?php

namespace Bitrix\Crm\Integration\AI\Dto;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\NotEmptyField;

class ScoringCriteria extends Dto
{
	public string $criterion;
	public ?bool $status = null;
	public ?string $explanation;

	protected function getValidators(array $fields): array
	{
		return [
			new NotEmptyField($this, 'criterion'),
		];
	}
}
