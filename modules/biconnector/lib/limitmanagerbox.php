<?php
namespace Bitrix\BIConnector;

class LimitManagerBox extends LimitManager
{
	protected $license = null;

	public function __construct()
	{
		$this->license = \Bitrix\Main\Application::getInstance()->getLicense();
	}

	/**
	 * Called on data export end.
	 *
	 * @param int $rowsCount How many data rows was exported.
	 * @param string $supersetKey Check for alternate limits.
	 *
	 * @return bool
	 */
	public function fixLimit($rowsCount, $supersetKey = '')
	{
		return false;
	}

	/**
	 * Returns maximum allowed records count.
	 * 0 - unlimited.
	 *
	 * @return int
	 */
	public function getLimit()
	{
		return 0;
	}

	/**
	 * Returns a date when data export will be disabled.
	 *
	 * @return \Bitrix\Main\Type\Date
	 */
	public function getLimitDate()
	{
		if ($this->license->isTimeBound())
		{
			$date = $this->license->getExpireDate();
			if ($date)
			{
				return $date;
			}
		}

		$date = new \Bitrix\Main\Type\Date();
		$date->add(static::GRACE_PERIOD_DAYS . ' days');

		return $date;
	}

	/**
	 * Returns true if there is nothing to worry about.
	 *
	 * @return bool
	 */
	public function checkLimitWarning()
	{
		$expireDate = $this->getLimitDate();
		$daysLeft = $expireDate->getDiff(new \Bitrix\Main\Type\Date())->days;

		return ($daysLeft >= static::GRACE_PERIOD_DAYS);
	}

	/**
	 * Returns true if data export and some functions is not disabled.
	 *
	 * @return bool
	 */
	public function checkLimit()
	{
		$expireDate = $this->getLimitDate();
		$daysLeft = $expireDate->getDiff(new \Bitrix\Main\Type\Date())->d;

		return ($daysLeft >= 0);
	}

	/**
	 * Event OnAfterSetOption_~controller_group_name handler.
	 *
	 * @param \Bitrix\Main\Event $event Event parameters.
	 *
	 * @return void
	 */
	public function licenseChange(\Bitrix\Main\Event $event)
	{
	}
}
