<?php

namespace Bitrix\CrmMobile\Dto;

use Bitrix\Mobile\Dto\Transformer\ToLower;
use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Validator\AllFieldsRequired;

final class VatRate extends Dto
{
	public int $id;

	public string $name;

	public ?float $value;

	protected function getValidators(): array
	{
		return [
			new AllFieldsRequired(),
		];
	}

	protected function getDecoders(): array
	{
		return [
			new ToLower(),
		];
	}
}
