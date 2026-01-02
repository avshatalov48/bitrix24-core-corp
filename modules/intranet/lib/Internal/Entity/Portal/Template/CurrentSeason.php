<?php

namespace Bitrix\Intranet\Internal\Entity\Portal\Template;

use Bitrix\Main\Type\Date;
use Bitrix\Intranet\Internal\Enum\TemplateSeason;

class CurrentSeason
{
	private Date $date;

	public function __construct(?Date $date = null)
	{
		$this->date = $date ?? new Date();
	}

	public function getSeasonFromCurrentDate(): TemplateSeason
	{
		$monthNumber = (int)$this->date->format('n');

		return match($monthNumber) {
				12, 1, 2 => TemplateSeason::WINTER,
				6, 7, 8 => TemplateSeason::SUMMER,
				default => TemplateSeason::SPRING,
			};
	}
}
