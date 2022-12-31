<?php

namespace Bitrix\CrmMobile\Dto;

use Bitrix\Mobile\Dto\Transformer\ToLower;
use Bitrix\Mobile\Dto\Type;
use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Validator\AllFieldsRequired;

final class VatRate extends Dto
{
	/** @var int */
	public $id;

	/** @var string */
	public $name;

	/** @var float|null */
	public $value;

	public function getCasts(): array
	{
		return [
			'id' => Type::int(),
			'name' => Type::string(),
			'value' => Type::float()->nullable(),
		];
	}

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
