<?php

namespace Bitrix\Crm\Integration\Report\Filter;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\UI\Filter\DateType;
use Bitrix\Report\VisualConstructor\Helper\Filter;

class Base extends Filter
{
	public static function getFieldsList()
	{
		Loader::includeModule('socialnetwork');
		Extension::load([
			'socnetlogdest',
			'crm.report.filterselectors'
		]);
		$fieldsList = parent::getFieldsList();
		$fieldsList['TIME_PERIOD']['required'] = true;
		$fieldsList['TIME_PERIOD']['valueRequired'] = true;
		$fieldsList['TIME_PERIOD']['exclude'] = [
			DateType::NONE,
			DateType::CURRENT_DAY,
			DateType::YESTERDAY,
			DateType::TOMORROW,
			DateType::NEXT_DAYS,
			DateType::NEXT_WEEK,
			DateType::NEXT_MONTH,
			DateType::EXACT,
		];

		return $fieldsList;
	}
}