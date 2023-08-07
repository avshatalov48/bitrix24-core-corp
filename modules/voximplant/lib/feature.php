<?php

namespace Bitrix\Voximplant;

use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Voximplant\Model\CallTable;
use Bitrix\Voximplant\Model\NumberTable;

class Feature
{

	/**
	 * @param DateTime|null $minDate default now minus 1 month
	 * @param DateTime|null $maxDate default now
	 *
	 * @return bool
	 */
	public static function isActive(?DateTime $minDate = null, ?DateTime $maxDate = null): bool
	{
		$maxDate ??= new DateTime();
		$minDate ??= (new DateTime())->add('-1 months');

		$sipCount = SipTable::getCount();
		$numberCount = NumberTable::getCount();

		if ($sipCount === 0 && $numberCount === 0)
		{
			return false;
		}

		$filter = Query::filter()
			->whereNotLike('CALL_ID', 'externalcall%')
			->whereBetween('DATE_CREATE', $minDate, $maxDate)
		;
		$result = CallTable::getCount($filter);

		return $result > 0;
	}
}