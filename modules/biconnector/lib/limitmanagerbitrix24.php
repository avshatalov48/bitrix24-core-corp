<?php

namespace Bitrix\BIConnector;

use Bitrix\Bitrix24;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;

class LimitManagerBitrix24 extends LimitManager
{
	/**
	 * Returns maximum allowed records count.
	 * 0 - unlimited.
	 *
	 * @return int
	 */
	public function getLimit(): int
	{
		$variableName = $this->isSuperset() ? 'biconnector_limit_superset' : 'biconnector_limit';

		return (int)Bitrix24\Feature::getVariable($variableName);
	}

	/**
	 * Returns a date when data export will be disabled.
	 *
	 * @return DateTime
	 */
	public function getLimitDate(): DateTime
	{
		$date = $this->getFirstOverLimitDate();
		$date?->add("{$this->getGracePeriodDays()} day");

		return $date;
	}

	/**
	 * Returns true if there is nothing to worry about.
	 *
	 * @return bool
	 */
	public function checkLimitWarning(): bool
	{
		return $this->getFirstOverLimitDate() === null;
	}

	/**
	 * Returns true if data export and some functions is not disabled.
	 *
	 * @return bool
	 */
	public function checkLimit(): bool
	{
		return !$this->isDataConnectionDisabled();
	}

	/**
	 * Event OnAfterSetOption_~controller_group_name handler.
	 *
	 * @param \Bitrix\Main\Event $event Event parameters.
	 * @return void
	 */
	public function licenseChange(\Bitrix\Main\Event $event): void
	{
		Option::delete('biconnector', ['name' => self::FIRST_OVER_LIMIT_OPTION_NAME]);
		Option::delete('biconnector', ['name' => self::LAST_OVER_LIMIT_OPTION_NAME]);
		Option::delete('biconnector', ['name' => self::LOCK_OPTION_NAME]);
		Option::delete('biconnector', ['name' => self::FIRST_OVER_LIMIT_OPTION_NAME_SUPERSET]);
		Option::delete('biconnector', ['name' => self::LAST_OVER_LIMIT_OPTION_NAME_SUPERSET]);
		Option::delete('biconnector', ['name' => self::LOCK_OPTION_NAME_SUPERSET]);
		Option::delete('biconnector', ['name' => self::LOCK_DATE_OPTION_NAME_SUPERSET]);
	}
}
