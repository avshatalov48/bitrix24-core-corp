<?php

namespace Bitrix\Tasks\Flow\Controllers\Dto;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Internals\Trait\FromArrayTrait;
use Bitrix\Tasks\Internals\Trait\ToArrayTrait;
use Bitrix\Tasks\Internals\Attribute\Min;
use Bitrix\Tasks\Internals\Attribute\Nullable;
use Bitrix\Tasks\Internals\Attribute\Required;
use Bitrix\Tasks\Internals\Dto\AbstractBaseDto;

/**
 * @method self setDate(DateTime $date)
 */
class DaysAgoDto extends AbstractBaseDto
{
	use FromArrayTrait;
	use ToArrayTrait;

	#[Required]
	#[Min(0)]
	public int $days;

	#[Nullable]
	public DateTime $date;
}