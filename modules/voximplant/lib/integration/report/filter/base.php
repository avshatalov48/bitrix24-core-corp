<?php

namespace Bitrix\Voximplant\Integration\Report\Filter;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\UI\Filter\DateType;
use Bitrix\Report\VisualConstructor\Helper\Filter;
use CVoxImplantConfig;

/**
 * Class Base
 * @package Bitrix\Voximplant\Integration\Report\Filter
 */
class Base extends Filter
{
	public static function getFieldsList(): array
	{
		$fieldsList = parent::getFieldsList();

		$fieldsList['PORTAL_USER_ID'] = [
			'id' => 'PORTAL_USER_ID',
			'name' => Loc::getMessage('TELEPHONY_FILTER_USER'),
			'default' => true,
			'type' => 'dest_selector',
			'params' => array (
				'apiVersion' => '3',
				'context' => 'REPORT_TELEPHONY_FILTER_USER',
				'multiple' => 'N',
				'contextCode' => 'U',
				'enableAll' => 'N',
				'enableSonetgroups' => 'N',
				'allowEmailInvitation' => 'N',
				'allowSearchEmailUsers' => 'N',
				'departmentSelectDisable' => 'Y',
			),
		];

		$fieldsList['PORTAL_NUMBER'] = [
			'id' => 'PORTAL_NUMBER',
			'name' => Loc::getMessage('TELEPHONY_FILTER_PORTAL_PHONE'),
			'default' => true,
			'type' => 'list',
			'items' => array_map(
				function($line){return $line['SHORT_NAME'];},
				CVoxImplantConfig::GetLinesEx([
					'showRestApps' => true,
					'showInboundOnly' => true
				])
			),
			'params' => [
				'multiple' => true
			]
		];

		$fieldsList['PHONE_NUMBER'] = [
			'id' => 'PHONE_NUMBER',
			'name' => Loc::getMessage('TELEPHONY_FILTER_PHONE_NUMBER'),
			'default' => true
		];

		$fieldsList['TIME_PERIOD']['required'] = true;
		$fieldsList['TIME_PERIOD']['valueRequired'] = true;
		$fieldsList['TIME_PERIOD']['exclude'] = [
			DateType::NONE,
			DateType::CURRENT_DAY,
			DateType::YESTERDAY,
			DateType::TOMORROW,
			DateType::NEXT_DAYS,
			DateType::NEXT_WEEK,
			DateType::NEXT_MONTH
		];

		return $fieldsList;
	}
}