<?php

namespace Bitrix\Crm\Integration\AI\Function\Category\Dto\Stage;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\EnumField;
use Bitrix\Crm\Dto\Validator\NotEmptyField;
use Bitrix\Crm\Dto\Validator\RequiredField;
use Bitrix\Crm\Dto\Validator\StringStartsWith;
use Bitrix\Crm\PhaseSemantics;

final class StageFields extends Dto
{
	public string $name;
	public string $semantics;
	public ?string $color = null;
	public ?int $sort = null;

	protected function getValidators(array $fields): array
	{
		return [
			new NotEmptyField($this, 'name'),

			new RequiredField($this, 'semantics'),
			new EnumField($this, 'semantics', [
				PhaseSemantics::PROCESS,
				PhaseSemantics::FAILURE,
			]),

			new StringStartsWith($this, 'color', '#'),
		];
	}
}
