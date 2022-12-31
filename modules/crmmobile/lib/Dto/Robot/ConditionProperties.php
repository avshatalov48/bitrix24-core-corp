<?php

namespace Bitrix\CrmMobile\Dto\Robot;

use Bitrix\Mobile\Dto\Type;
use Bitrix\Mobile\Dto\Dto;

final class ConditionProperties extends Dto
{
	/** @var string */
	public $operator;

	/** @var string */
	public $value;

	/** @var string */
	public $field;

	/** @var string */
	public $object;

	public function getCasts(): array
	{
		return [
			'operator' => Type::string(),
			'value' => Type::string(),
			'field' => Type::string(),
			'object' => Type::string(),
			'joiner' => Type::string(),
		];
	}
}