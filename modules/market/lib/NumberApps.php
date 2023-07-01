<?php


namespace Bitrix\Market;


class NumberApps
{
	public static function get($responseValue)
	{
		$result = 0;

		if (!empty($responseValue) && isset($responseValue['COUNT_TOTAL'])) {
			$totalApps = (int)$responseValue['COUNT_TOTAL'];
			$result = ($totalApps > 0) ? round($totalApps, -1) : 0;
		}

		return $result;
	}
}