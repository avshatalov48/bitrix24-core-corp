<?php

namespace Bitrix\Crm\Volume;

use Bitrix\Crm;
use Bitrix\Main\Localization\Loc;


class Other extends Crm\Volume\Base
{
	/**
	 * Returns title of the indicator.
	 * @return string
	 */
	public function getTitle()
	{
		return Loc::getMessage('CRM_VOLUME_OTHER_TITLE');
	}

	/**
	 * Returns table list corresponding to indicator.
	 * @return string[]
	 */
	public static function getSubIndicatorList()
	{
		return array(
			Crm\Volume\Webform::class,
			Crm\Volume\Address::class,
			Crm\Volume\Requisite::class,
			Crm\Volume\Product::class,
		);
	}

	/**
	 * Returns table list corresponding to indicator.
	 * @return string[]
	 */
	public function getTableList()
	{
		Crm\Volume\Base::loadTablesInformation();

		$allTables = array_keys(self::$tablesInformation);
		$ignoreTables = array();

		$indicatorList = Crm\Volume\Base::getListIndicator();
		$otherIndicatorList = Crm\Volume\Other::getSubIndicatorList();

		/** @var \Bitrix\Crm\Volume\IVolumeIndicator $indicatorType */
		foreach ($indicatorList as $indicatorType)
		{
			if (in_array($indicatorType, $otherIndicatorList))
			{
				continue;
			}

			/** @var \Bitrix\Crm\Volume\IVolumeIndicator $indicator */
			$indicator = new $indicatorType();
			if ($indicator instanceof static)
			{
				continue;
			}
			if ($indicator instanceof Crm\Volume\IVolumeIndicator)
			{
				$ignoreTables = array_merge($ignoreTables, $indicator->getTableList());
			}
		}

		return array_diff($allTables, $ignoreTables);
	}
}

