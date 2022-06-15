<?php
namespace Bitrix\BIConnector;

class LimitManagerBitrix24 extends LimitManager
{
	/**
	 * Called on data export end.
	 * @param int $rowsCount How many data rows was exported.
	 *
	 * @return void
	 */
	public function fixLimit($rowsCount)
	{
		$limit = $this->getLimit();
		if ($limit > 0 && $rowsCount > $limit)
		{
			\Bitrix\Main\Config\Option::set('biconnector', 'last_limit_ts', time());
			$limitTimestamp = (int)\Bitrix\Main\Config\Option::get('biconnector', 'over_limit_ts');
			if ($limitTimestamp <= 0)
			{
				\Bitrix\Main\Config\Option::set('biconnector', 'over_limit_ts', time());
			}
			elseif (static::GRACE_PERIOD_DAYS * 86400 < (time() - $limitTimestamp))
			{
				$disabled = \Bitrix\Main\Config\Option::get('biconnector', 'disable_data_connection');
				if ($disabled !== 'Y')
				{
					\Bitrix\Main\Config\Option::set('biconnector', 'disable_data_connection', 'Y');
				}
			}
		}
		else
		{
			$lastLimitTimestamp = (int)\Bitrix\Main\Config\Option::get('biconnector', 'last_limit_ts');
			if (static::AUTO_RELEASE_DAYS * 86400 < (time() - $lastLimitTimestamp))
			{
				\Bitrix\Main\Config\Option::delete('biconnector', [ 'name' => 'last_limit_ts' ]);
				\Bitrix\Main\Config\Option::delete('biconnector', [ 'name' => 'over_limit_ts' ]);
			}
		}
	}

	/**
	 * Returns maximum allowed records count.
	 * 0 - unlimited.
	 *
	 * @return int
	 */
	public function getLimit()
	{
		$limit = (int)\Bitrix\Bitrix24\Feature::getVariable('biconnector_limit');

		return $limit;
	}

	/**
	 * Returns a date when data export will be disabled.
	 *
	 * @return \Bitrix\Main\Type\Date
	 */
	public function getLimitDate()
	{
		$limitTimestamp = (int)\Bitrix\Main\Config\Option::get('biconnector', 'over_limit_ts');
		$date = \Bitrix\Main\Type\Date::createFromTimestamp($limitTimestamp + static::GRACE_PERIOD_DAYS * 86400);

		return $date;
	}

	/**
	 * Returns true if there is nothing to worry about.
	 *
	 * @return boolean
	 */
	public function checkLimitWarning()
	{
		$overLimitTime = \Bitrix\Main\Config\Option::get('biconnector', 'over_limit_ts');

		return ($overLimitTime <= 0);
	}

	/**
	 * Returns true if data export and some functions is not disabled.
	 *
	 * @return boolean
	 */
	public function checkLimit()
	{
		$disabled = \Bitrix\Main\Config\Option::get('biconnector', 'disable_data_connection');

		return ($disabled !== 'Y');
	}

	/**
	 * @param \Bitrix\Main\Event $event
	 * @return void
	 */
	public function licenseChange(\Bitrix\Main\Event $event)
	{
		\Bitrix\Main\Config\Option::delete('biconnector', [ 'name' => 'last_limit_ts' ]);
		\Bitrix\Main\Config\Option::delete('biconnector', [ 'name' => 'over_limit_ts' ]);
		\Bitrix\Main\Config\Option::delete('biconnector', [ 'name' => 'disable_data_connection' ]);
	}
}
