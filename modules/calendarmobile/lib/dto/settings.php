<?php

namespace Bitrix\CalendarMobile\Dto;

use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Transformer\ToCamelCase;
use Bitrix\Mobile\Dto\Type;

final class Settings extends Dto
{
	/** @var string/null */
	public $weekStart;

	/** @var int/null */
	public $workTimeStart;

	/** @var int/null */
	public $workTimeEnd;

	/** @var array/null */
	public $weekHolidays = [];

	/** @var Rule */
	public $rule;

	public function getCasts(): array
	{
		return [
			'weekStart' => Type::string(),
			'workTimeStart' => Type::float(),
			'workTimeEnd' => Type::float(),
			'weekHolidays' => Type::collection(Type::string()),
			'rule' => Type::object(Rule::class),
		];
	}
}