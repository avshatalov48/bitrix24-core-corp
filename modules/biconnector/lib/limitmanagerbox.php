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
	 * @inheritDoc
	 */
	public function fixLimit(int $rowsCount): bool
	{
		if (!$this->isSuperset())
		{
			return false;
		}

		return parent::fixLimit($rowsCount);
	}

	/**
	 * Returns maximum allowed records count.
	 * 0 - unlimited.
	 *
	 * @return int
	 */
	public function getLimit()
	{
		if ($this->isSuperset())
		{
			return 10000000;
		}

		return 0;
	}

	public function isLimitByLicence(): bool
	{
		$expireDate = $this->getLimitDate();
		$daysLeft = $expireDate->getDiff(new \Bitrix\Main\Type\Date())->days;

		return $daysLeft < self::GRACE_PERIOD_DAYS;
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
		if ($this->isSuperset() && !$this->isLimitByLicence())
		{
			return $this->getFirstOverLimitDate() === null;
		}

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
		if ($this->isSuperset() && !$this->isLimitByLicence())
		{
			return !$this->isDataConnectionDisabled();
		}

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
