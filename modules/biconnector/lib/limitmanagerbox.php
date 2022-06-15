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
	 * @param int $rowsCount How many data rows was exported.
	 *
	 * @return void
	 */
	public function fixLimit($rowsCount)
	{
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
	 * @return boolean
	 */
	public function checkLimitWarning()
	{
		$expireDate = $this->getLimitDate();
		$daysLeft = $expireDate->getDiff(new \Bitrix\Main\Type\Date())->d;

		return ($daysLeft >= static::GRACE_PERIOD_DAYS);
	}

	/**
	 * Returns true if data export and some functions is not disabled.
	 *
	 * @return boolean
	 */
	public function checkLimit()
	{
		$expireDate = $this->getLimitDate();
		$daysLeft = $expireDate->getDiff(new \Bitrix\Main\Type\Date())->d;

		return ($daysLeft >= 0);
	}

	/**
	 * @param \Bitrix\Main\Event $event
	 * @return void
	 */
	public function licenseChange(\Bitrix\Main\Event $event)
	{
	}
}
