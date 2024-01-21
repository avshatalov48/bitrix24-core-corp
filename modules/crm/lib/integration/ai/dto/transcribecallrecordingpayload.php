<?php

namespace Bitrix\Crm\Integration\AI\Dto;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\NotEmptyField;

final class TranscribeCallRecordingPayload extends Dto
{
	public string $transcription;

	protected function getValidators(array $fields): array
	{
		return [
			new NotEmptyField($this, 'transcription'),
		];
	}
}
