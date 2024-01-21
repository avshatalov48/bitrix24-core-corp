<?php

namespace Bitrix\Crm\Integration\AI\Dto;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\NotEmptyField;

final class SummarizeCallTranscriptionPayload extends Dto
{
	public string $summary;

	protected function getValidators(array $fields): array
	{
		return [
			new NotEmptyField($this, 'summary'),
		];
	}
}
