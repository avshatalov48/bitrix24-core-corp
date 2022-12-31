<?php

namespace Bitrix\CrmMobile\Dto\Robot;

use Bitrix\Mobile\Dto\Type;
use Bitrix\Mobile\Dto\Dto;

final class ConditionGroup extends Dto
{
	/** @var string */
	public $type;

	/** @var Condition[] */
	public $items;

	public function getCasts(): array
	{
		return [
			'type' => Type::string(),
			'items' => Type::collection(Condition::class),
		];
	}
}