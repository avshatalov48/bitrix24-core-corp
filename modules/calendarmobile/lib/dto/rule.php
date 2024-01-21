<?php

namespace Bitrix\CalendarMobile\Dto;

use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Type;

final class Rule extends Dto
{
	/** @var int */
	public $slotSize;

	/** @var int */
	public $maxRanges = 5;

	/** @var string/null */
	public $hash = '';

	/** @var Range[]/null */
	public $ranges = [];

	/** @var int[] */
	public $availableSlots = [30, 45, 60, 90, 120, 180];

	public function getCasts(): array
	{
		return [
			'slotSize' => Type::int(),
			'hash' => Type::string(),
			'ranges' => Type::collection(Range::class),
			'maxRanges' => Type::int(),
			'availableSlots' => Type::collection(Type::int()),
		];
	}
}