<?php

namespace Bitrix\CrmMobile\Dto\Robot;

use Bitrix\Mobile\Dto\Type;
use Bitrix\Mobile\Dto\Dto;

final class Condition extends Dto
{
	/** @var string */
	public $joiner;

	/** @var ConditionProperties|null */
	public $properties;

	public function getCasts(): array
	{
		return [
			'joiner' => Type::string(),
			'properties' => Type::object(ConditionProperties::class),
		];
	}
}