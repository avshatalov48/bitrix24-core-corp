<?php

namespace Bitrix\CrmMobile\Dto\Robot;

use Bitrix\Mobile\Dto\Type;
use Bitrix\Mobile\Dto\Dto;

final class Robot extends Dto
{
	/** @var string */
	public $name;

	/** @var Delay|null */
	public $delay;

	/** @var RobotProperties|null */
	public $properties;

	/** @var ConditionGroup|null */
	public $conditionGroup;

	/** @var Responsible|null */
	public $responsible;

	public function getCasts(): array
	{
		return [
			'name' => Type::string(),
			'delay' => Type::object(Delay::class),
			'properties' => Type::object(RobotProperties::class),
			'conditionGroup' => Type::object(ConditionGroup::class),
			'responsible' => Type::object(Responsible::class),
		];
	}
}