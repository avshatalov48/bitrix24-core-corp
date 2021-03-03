<?php
namespace Bitrix\ImOpenLines\Integrations\Report\VisualConstructor\Helper;

use Bitrix\Main\Localization\Loc;

/**
 * Class Filter
 * @package Bitrix\ImOpenLines\VisualConstructor\Helper
 */
class Filter extends \Bitrix\Report\VisualConstructor\Helper\Filter
{
	/**
	 * @return array
	 */
	public static function getFieldsList()
	{
		$excludeDate = [
			\Bitrix\Main\UI\Filter\DateType::CURRENT_DAY,
			\Bitrix\Main\UI\Filter\DateType::TOMORROW,
			\Bitrix\Main\UI\Filter\DateType::LAST_MONTH,
			\Bitrix\Main\UI\Filter\DateType::NEXT_WEEK,
			\Bitrix\Main\UI\Filter\DateType::NEXT_MONTH,
			\Bitrix\Main\UI\Filter\DateType::NEXT_DAYS,
		];

		return [
			'TIME_PERIOD' => [
				'id' => 'TIME_PERIOD',
				'name' => Loc::getMessage('REPORTS_TIME_PERIOD'),
				'type' => 'date',
				'exclude' => $excludeDate,
				'default' => true
			]
		];
	}
}