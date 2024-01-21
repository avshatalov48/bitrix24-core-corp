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
	public $weekDays = [];

	public function getCasts(): array
	{
		return [
			'from' => Type::int(),
			'to' => Type::int(),
			'weekDays' => Type::collection(Type::int()),
		];
	}

	protected function getDecoders(): array
	{
		return [
			function (array $fields)
			{
				$fields['weekDays'] = $fields['weekdays'];
				unset($fields['weekdays']);
				return $fields;
			},
		];
	}
}