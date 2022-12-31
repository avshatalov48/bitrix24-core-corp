<?php

namespace Bitrix\Mobile\UI\SimpleList\Dto;

use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Type;
use Bitrix\Mobile\Dto\Validator\AllFieldsRequired;

abstract class Data extends Dto
{
	/** @var int|string|null */
	public $id;

	/** @var string|null */
	public $name;

	/** @var int|null */
	public $date;

	public $fields = [];

	public function getCasts(): array
	{
		return [
			'id' => Type::int(),
			'name' => Type::string(),
			'date' => Type::int(),
			//'fields' => Type::collection(Type::string()), // a semi-structured array not yet described in dto
		];
	}

	public function getValidators(): array
	{
		return [
			new AllFieldsRequired(),
		];
	}
}
