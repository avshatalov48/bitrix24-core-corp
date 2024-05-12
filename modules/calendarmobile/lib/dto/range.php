<?php

namespace Bitrix\CalendarMobile\Dto;

use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Transformer\ToCamelCase;
use Bitrix\Mobile\Dto\Type;

final class Range extends Dto
{
	/** @var int/null */
	public $from = 600;

	/** @var int/null */
	public $to = 600;

	/** @var array/null */
	public $weekdays = [];

	public function getCasts(): array
	{
		return [
			'from' => Type::int(),
			'to' => Type::int(),
			'weekdays' => Type::collection(Type::int()),
		];
	}
}