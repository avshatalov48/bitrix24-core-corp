<?php

namespace Bitrix\Voximplant\Integration\Report\Filter;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\DateType;
use Bitrix\Report\VisualConstructor\Helper\Filter;
use Bitrix\Voximplant\Integration\Report\CallType;
use CVoxImplantConfig;

/**
 * Class PeriodCompareFilter
 * @package Bitrix\Voximplant\Integration\Report\Filter
 */
class PeriodCompareFilter extends Filter
{
	/**
	 * Returns the filter fields.
	 *
	 * @return array
	 */
	public static function getFieldsList(): array
	{
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

		$fieldsList['PREVIOUS_TIME_PERIOD'] = [
			'id' => 'PREVIOUS_TIME_PERIOD',
			'name' => Loc::getMessage('TELEPHONY_REPORT_FILTER_PERIOD_COMPARE_PREVIOUS_TIME_PERIOD'),
			'required' => true,
			'valueRequired' => true,
			'type' => 'date',
			'default' => true,
			'exclude' => [
				DateType::NONE,
				DateType::CURRENT_DAY,
				DateType::YESTERDAY,
				DateType::TOMORROW,
				DateType::NEXT_DAYS,
				DateType::NEXT_WEEK,
				DateType::NEXT_MONTH,
				DateType::EXACT,
			],
		];

		$fieldsList['INCOMING'] = [
			'id' => 'INCOMING',
			'name' => Loc::getMessage('TELEPHONY_REPORT_FILTER_PERIOD_COMPARE_CALL_TYPE'),
			'required' => true,
			'valueRequired' => true,
			'type' => 'list',
			'items' => [
				'' => Loc::getMessage('TELEPHONY_REPORT_FILTER_PERIOD_COMPARE_ALL_CALLS'),
				CallType::INCOMING => Loc::getMessage('TELEPHONY_REPORT_FILTER_PERIOD_COMPARE_CALL_INCOMING'),
				CallType::OUTGOING => Loc::getMessage('TELEPHONY_REPORT_FILTER_PERIOD_COMPARE_CALL_OUTGOING'),
				CallType::MISSED => Loc::getMessage('TELEPHONY_REPORT_FILTER_PERIOD_COMPARE_CALL_MISSED'),
				CallType::CALLBACK => Loc::getMessage('TELEPHONY_REPORT_FILTER_PERIOD_COMPARE_CALL_CALLBACK'),
			],
			'default' => true
		];

		$fieldsList['PORTAL_USER_ID'] = [
			'id' => 'PORTAL_USER_ID',
			'name' => Loc::getMessage('TELEPHONY_REPORT_FILTER_PERIOD_COMPARE_USER'),
			'default' => true,
			'type' => 'dest_selector',
			'params' => array (
				'apiVersion' => '3',
				'context' => 'TELEPHONY_FILTER_USER',
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
			'name' => Loc::getMessage('TELEPHONY_REPORT_FILTER_PERIOD_COMPARE_PORTAL_PHONE'),
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
			'name' => Loc::getMessage('TELEPHONY_REPORT_FILTER_PERIOD_COMPARE_PHONE_NUMBER'),
			'default' => true
		];

		$fieldsList['PORTAL_NUMBER']['default'] = false;
		$fieldsList['PHONE_NUMBER']['default'] = false;

		return $fieldsList;
	}

	/**
	 * Returns presets for the filter.
	 *
	 * @return array
	 */
	public static function getPresetsList(): array
	{
		$presets['filter_incoming'] = [
			'name' => Loc::getMessage('TELEPHONY_REPORT_FILTER_PERIOD_COMPARE_INCOMING_PRESET_TITLE'),
			'fields' => [
				'TIME_PERIOD_datesel' => DateType::CURRENT_MONTH,
				'PREVIOUS_TIME_PERIOD_datesel' => DateType::LAST_MONTH,
				'INCOMING' => CallType::INCOMING
			],
			'default' => true,
		];

		return $presets;
	}

	public function getStringList(): array
	{
		$result = parent::getStringList();
		$result[] = "<script>BX.ready(function (){BX.Voximplant.Report.Dashboard.Content.PeriodCompare.init('".\CUtil::JSEscape($this->getFilterId())."')});</script>";
		return $result;
	}
}