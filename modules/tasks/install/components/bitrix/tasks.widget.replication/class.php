<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Tasks\Item\Task\Template\Field\ReplicateParams;
use Bitrix\Tasks\Internals\Task\Template\ReplicateParamsCorrector;

//use Bitrix\Main\Localization\Loc;
//
//Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksWidgetReplicationComponent extends TasksBaseComponent
{
	protected function checkParameters()
	{
		$this->arParams['DATA'] = ReplicateParams::createValueStructure($this->arParams['DATA'])->get();
		$this->tryParseIntegerParameter($this->arParams['USER_ID'], 0, true);
		$this->tryParseArrayParameter($this->arParams['COMPANY_WORKTIME'], static::getCompanyWorkTime());
		return $this->errors->checkNoFatals();
	}

	protected function getData()
	{
		// week day order
		$weekStart = $this->arParams['COMPANY_WORKTIME']['WEEK_START'];

		$wdMap = array(
			0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6
		);

		// wee need mapping because of different week start
		if((string) $weekStart != '')
		{
			$wdStrMap = array(
				'MO' => 0,
				'TU' => 1,
				'WE' => 2,
				'TH' => 3,
				'FR' => 4,
				'SA' => 5,
				'SU' => 6,
			);

			$offs = $wdStrMap[$weekStart];

			$wdMap = array();
			for($k = 0; $k < 7; $k++)
			{
				$wdMap[$k] = ($k + $offs) % 7;
			}
		}

		$this->arResult['AUX_DATA']['WEEKDAY_MAP'] = $wdMap;

		$currentTimeZoneOffset = \Bitrix\Tasks\Util\User::getTimeZoneOffset($this->arParams['USER_ID']);
		$serverTimeZoneOffset = \Bitrix\Tasks\Util::getServerTimeZoneOffset();
		$resultTimeZoneOffset = $currentTimeZoneOffset + $serverTimeZoneOffset;

		$this->arResult['CURRENT_TIMEZONE_OFFSET'] = 0;
		$this->arResult['AUX_DATA']['UTC_TIME_ZONE_OFFSET'] = $resultTimeZoneOffset;

		$data = $this->arParams['DATA'];

		$time = strtotime($data['TIME']);
		$timeZoneOffset = $data['TIMEZONE_OFFSET'];
		$creator = $this->arParams['TEMPLATE_CREATED_BY'];

		$creatorTimeZoneOffset = (isset($timeZoneOffset)? $timeZoneOffset : \Bitrix\Tasks\Util\User::getTimeZoneOffset($creator));

		$serverTime = date('H:i', $time - $creatorTimeZoneOffset);
		$serverStartDate = MakeTimeStamp($data['START_DATE']);
		$serverEndDate = MakeTimeStamp($data['END_DATE']);

		$this->arParams['DATA']['TIME'] = ReplicateParamsCorrector::correctTime($serverTime, $currentTimeZoneOffset, 'user');
		$this->arParams['DATA']['START_DATE'] = ReplicateParamsCorrector::correctStartDate($serverTime, $serverStartDate, $currentTimeZoneOffset, 'user');
		$this->arParams['DATA']['END_DATE'] = ReplicateParamsCorrector::correctEndDate($serverTime, $serverEndDate, $currentTimeZoneOffset, 'user');
	}
}