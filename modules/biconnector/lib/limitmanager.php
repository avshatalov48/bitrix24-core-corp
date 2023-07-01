<?php
namespace Bitrix\BIConnector;

abstract class LimitManager
{
	const GRACE_PERIOD_DAYS = 14;
	const AUTO_RELEASE_DAYS = 7;

	protected static $instance = null;

	/**
	 * Singleton instance production.
	 *
	 * @return \Bitrix\BIConnector\LimitManager
	 */
	public static function getInstance(): LimitManager
	{
		if (static::$instance === null)
		{
			if (\Bitrix\Main\Loader::includeModule('bitrix24'))
			{
				static::$instance = new LimitManagerBitrix24();
			}
			else
			{
				static::$instance = new LimitManagerBox();
			}
		}

		return static::$instance;
	}

	/**
	 * Called on data export end.
	 *
	 * @param int $rowsCount How many data rows was exported.
	 *
	 * @return void
	 */
	abstract public function fixLimit($rowsCount);

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
