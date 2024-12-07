<?php

namespace Bitrix\BIConnector;

use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;

abstract class LimitManager
{
	public const GRACE_PERIOD_DAYS = 14;
	public const AUTO_RELEASE_DAYS = 7;
	public const GRACE_PERIOD_DAYS_SUPERSET = 1;
	public const AUTO_RELEASE_DAYS_SUPERSET = 1;
	public const LOCK_DURATION_DAYS_SUPERSET = 1.5;

	protected const FIRST_OVER_LIMIT_OPTION_NAME = 'over_limit_ts';
	protected const LAST_OVER_LIMIT_OPTION_NAME = 'last_limit_ts';
	protected const LOCK_OPTION_NAME = 'disable_data_connection';

	protected const FIRST_OVER_LIMIT_OPTION_NAME_SUPERSET = 'over_limit_ts_superset';
	protected const LAST_OVER_LIMIT_OPTION_NAME_SUPERSET = 'last_limit_ts_superset';
	protected const LOCK_OPTION_NAME_SUPERSET = 'disable_data_connection_superset';
	protected const LOCK_DATE_OPTION_NAME_SUPERSET = 'disable_data_connection_ts_superset';

	protected bool $isSuperset = false;

	/**
	 * @return LimitManager
	 */
	public static function getInstance(): LimitManager
	{
		if (Loader::includeModule('bitrix24'))
		{
			$instance = new LimitManagerBitrix24();
		}
		else
		{
			$instance = new LimitManagerBox();
		}

		return $instance;
	}

	/**
	 * Called on data export end.
	 *
	 * @param int $rowsCount How many data rows was exported.
	 * @return bool Limit was exceeded or not.
	 */
	public function fixLimit(int $rowsCount): bool
	{
		$limit = $this->getLimit();
		if ($limit > 0 && $rowsCount > $limit)
		{
			$this->setLastOverLimitDate();
			$firstOverLimitDate = $this->getFirstOverLimitDate();
			if (!$firstOverLimitDate)
			{
				$this->setFirstOverLimitDate();
			}
			elseif (new DateTime() > $firstOverLimitDate->add("{$this->getGracePeriodDays()} days"))
			{
				if (!$this->isDataConnectionDisabled())
				{
					$this->setDisabledDataConnection();
				}
			}

			return true;
		}

		$lastOverLimitDate = $this->getLastOverLimitDate();
		if (new DateTime() > $lastOverLimitDate?->add($this->getAutoReleaseDays() * 24 . ' hours'))
		{
			$this->clearOverLimitTimestamps();
		}

		return false;
	}

	public function isLimitByLicence(): bool
	{
		return false;
	}

	/**
	 * Returns maximum allowed records count.
	 * 0 - unlimited.
	 *
	 * @return int
	 */
	abstract public function getLimit();

	/**
	 * Returns a date when data export will be disabled.
	 *
	 * @return \Bitrix\Main\Type\Date
	 */
	abstract public function getLimitDate();

	/**
	 * Returns true if there is nothing to worry about.
	 *
	 * @return bool
	 */
	abstract public function checkLimitWarning();

	/**
	 * Returns true if data export and some functions is not disabled.
	 *
	 * @return bool
	 */
	abstract public function checkLimit();

	/**
	 * Event OnAfterSetOption_~controller_group_name handler.
	 *
	 * @param \Bitrix\Main\Event $event Event parameters.
	 *
	 * @return void
	 */
	abstract public function licenseChange(\Bitrix\Main\Event $event);

	/**
	 * Sets superset key to use the proper limits.
	 *
	 * @param string $supersetKey
	 * @return $this
	 */
	public function setSupersetKey(string $supersetKey): LimitManager
	{
		$configParams = Configuration::getValue('biconnector');
		if (
			isset($configParams['superset_key'])
			&& $configParams['superset_key'] === $supersetKey
		)
		{
			$this->setIsSuperset();
		}

		return $this;
	}

	/**
	 * Check whether the query is from superset or not to use proper limits.
	 *
	 * @return bool
	 */
	public function isSuperset(): bool
	{
		return $this->isSuperset;
	}

	/**
	 * Should be used only if ensured this is superset but key is unknown.
	 * If the key is known, use setSupersetKey method.
	 * @see LimitManager::setSupersetKey
	 *
	 * @return $this
	 */
	public function setIsSuperset(): self
	{
		$this->isSuperset = true;
		$unlockDate = $this->getSupersetUnlockDate();
		if (
			$unlockDate !== null
			&& new DateTime() > $unlockDate
		)
		{
			$this->clearOverLimitTimestamps();
			$this->setDisabledDataConnection(false);
		}

		return $this;
	}

	protected function isDataConnectionDisabled(): bool
	{
		return Option::get('biconnector', $this->getLockOptionName(), 'N') === 'Y';
	}

	protected function getFirstOverLimitDate(): ?DateTime
	{
		$timestamp = (int)Option::get('biconnector', $this->getFirstOverLimitOptionName());
		if ($timestamp > 0)
		{
			return DateTime::createFromTimestamp($timestamp);
		}

		return null;
	}

	protected function getLastOverLimitDate(): ?DateTime
	{
		$timestamp = (int)Option::get('biconnector', $this->getLastOverLimitOptionName());
		if ($timestamp > 0)
		{
			return DateTime::createFromTimestamp($timestamp);
		}

		return null;
	}

	protected function getGracePeriodDays(): int
	{
		return $this->isSuperset() ? self::GRACE_PERIOD_DAYS_SUPERSET : self::GRACE_PERIOD_DAYS;
	}

	protected function getAutoReleaseDays(): int
	{
		return $this->isSuperset() ? self::AUTO_RELEASE_DAYS_SUPERSET : self::AUTO_RELEASE_DAYS;
	}

	protected function getSupersetLockDate(): ?DateTime
	{
		if (!$this->isSuperset())
		{
			return null;
		}
		$time = (int)Option::get('biconnector', self::LOCK_DATE_OPTION_NAME_SUPERSET);
		if ($time)
		{
			return DateTime::createFromTimestamp($time);
		}

		return null;
	}

	public function getSupersetUnlockDate(): ?DateTime
	{
		if (!$this->isSuperset())
		{
			return null;
		}

		$lockDate = $this->getSupersetLockDate();
		if (
			$lockDate === null
			|| !$this->isDataConnectionDisabled()
		)
		{
			return null;
		}

		return $lockDate->add(self::LOCK_DURATION_DAYS_SUPERSET * 24 . " hours");
	}

	protected function setFirstOverLimitDate(DateTime $date = null): void
	{
		if (!$date)
		{
			$date = DateTime::createFromTimestamp(time());
		}

		Option::set('biconnector', $this->getFirstOverLimitOptionName(), $date->getTimestamp());
	}

	protected function setLastOverLimitDate(DateTime $date = null): void
	{
		if (!$date)
		{
			$date = DateTime::createFromTimestamp(time());
		}

		Option::set('biconnector', $this->getLastOverLimitOptionName(), $date->getTimestamp());
	}

	protected function setDisabledDataConnection(bool $disabled = true): void
	{
		$optionValue = $disabled ? 'Y' : 'N';
		Option::set('biconnector', $this->getLockOptionName(), $optionValue);
		if ($this->isSuperset())
		{
			Option::set('biconnector', self::LOCK_DATE_OPTION_NAME_SUPERSET, time());
		}
	}

	protected function clearOverLimitTimestamps(): void
	{
		Option::delete('biconnector', ['name' => $this->getFirstOverLimitOptionName()]);
		Option::delete('biconnector', ['name' => $this->getLastOverLimitOptionName()]);
	}

	protected function getFirstOverLimitOptionName(): string
	{
		return $this->isSuperset()
			? self::FIRST_OVER_LIMIT_OPTION_NAME_SUPERSET
			: self::FIRST_OVER_LIMIT_OPTION_NAME;
	}

	protected function getLastOverLimitOptionName(): string
	{
		return $this->isSuperset()
			? self::LAST_OVER_LIMIT_OPTION_NAME_SUPERSET
			: self::LAST_OVER_LIMIT_OPTION_NAME;
	}

	protected function getLockOptionName(): string
	{
		return $this->isSuperset()
			? self::LOCK_OPTION_NAME_SUPERSET
			: self::LOCK_OPTION_NAME;
	}

	/**
	 * Event OnAfterSetOption_~controller_group_name handler.
	 *
	 * @param \Bitrix\Main\Event $event Event parameters.
	 *
	 * @return void
	 */
	public static function onBitrix24LicenseChange(\Bitrix\Main\Event $event)
	{
		static::getInstance()->licenseChange($event);
	}
}
