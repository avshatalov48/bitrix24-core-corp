<?php

namespace Bitrix\Tasks\Internals\Task\Template;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Replication\Repository\TemplateRepository;
use Bitrix\Tasks\Replication\Template\Repetition\Time\Service\ExecutionService;
use \Bitrix\Tasks\UI;
use \Bitrix\Tasks\Util\User;

/**
 * Corrects replicate parameters
 *
 * Class ReplicateParamsCorrector
 * @package Bitrix\Tasks\Internals\Task\Template
 */
final class ReplicateParamsCorrector
{
	private $userId;

	public function __construct(int $userId = 0)
	{
		$this->userId = $userId;
	}

	/**
	 * Corrects replicate parameters (time, start date, end date) if replicate == 'Y'
	 *
	 * @param $templateData
	 * @return mixed replicateParams
	 */
	public function correctReplicateParamsByTemplateData($templateData)
	{
		$replicateParams = $templateData['REPLICATE_PARAMS'];

		if ($templateData['REPLICATE'] !== 'Y')
		{
			return $replicateParams;
		}

		$userId = $this->userId;
		if (!$userId)
		{
			$userId = $templateData['CREATED_BY'];
		}

		$userTime = $replicateParams['TIME'];
		$userOffset = User::getTimeZoneOffset($userId);
		$userStartDate = MakeTimeStamp($replicateParams['START_DATE']);
		$userEndDate = MakeTimeStamp($replicateParams['END_DATE']);

		$replicateParams['TIME'] = static::correctTime($userTime, $userOffset);
		$replicateParams['START_DATE'] = static::correctStartDate($userTime, $userStartDate, $userOffset);
		$replicateParams['END_DATE'] = static::correctEndDate($userTime, $userEndDate, $userOffset);

		$replicateParams['NEXT_EXECUTION_TIME'] = static::getNextExecutionTime(array(
			'CREATED_BY' => $templateData['CREATED_BY'],
			'REPLICATE_PARAMS' => $replicateParams
		));

		return $replicateParams;
	}

	/**
	 * Corrects time based on $resultTimeType
	 *
	 * @param $time
	 * @param $offset
	 * @param string $resultTimeType
	 * @return false|string
	 */
	public static function correctTime($time, $offset, $resultTimeType = 'server')
	{
		switch ($resultTimeType)
		{
			case 'server':
				$result = static::getServerTime($time, $offset);
				break;

			case 'user':
				$result = static::getUserTime($time, $offset);
				break;

			default:
				$result = static::getServerTime($time, $offset);
				break;
		}

		return $result;
	}

	/**
	 * Corrects start date based on $resultStartDateType
	 *
	 * @param $time
	 * @param $startDate
	 * @param $offset
	 * @param string $resultStartDateType
	 * @return false|string
	 */
	public static function correctStartDate($time, $startDate, $offset, $resultStartDateType = 'server')
	{
		if (!$startDate)
		{
			return '';
		}

		switch ($resultStartDateType)
		{
			case 'server':
				$result = static::getServerStartDate($time, $startDate, $offset);
				break;

			case 'user':
				$result = static::getUserStartDate($time, $startDate, $offset);
				break;

			default:
				$result = static::getServerStartDate($time, $startDate, $offset);
				break;
		}

		return $result;
	}

	/**
	 * Correct end date based on $resultEndDateType
	 *
	 * @param $time
	 * @param $endDate
	 * @param $offset
	 * @param string $resultEndDateType
	 * @return false|string
	 */
	public static function correctEndDate($time, $endDate, $offset, $resultEndDateType = 'server')
	{
		if (!$endDate)
		{
			return '';
		}

		switch ($resultEndDateType)
		{
			case 'server':
				$result = static::getServerEndDate($time, $endDate, $offset);
				break;

			case 'user':
				$result = static::getUserEndDate($time, $endDate, $offset);
				break;

			default:
				$result = static::getServerEndDate($time, $endDate, $offset);
				break;
		}

		return $result;
	}

	/**
	 * Converts user time to server time
	 *
	 * @param $userTime
	 * @param $userOffset
	 * @return false|string
	 */
	private static function getServerTime($userTime, $userOffset)
	{
		return date('H:i', strtotime($userTime) - $userOffset);
	}

	/**
	 * Converts server time to user time
	 *
	 * @param $serverTime
	 * @param $currentTimeZoneOffset
	 * @return false|string
	 */
	private static function getUserTime($serverTime, $currentTimeZoneOffset)
	{
		return date('H:i', strtotime($serverTime) + $currentTimeZoneOffset);
	}

	/**
	 * Converts user start date to server start date
	 *
	 * @param $userTime
	 * @param $userStartDate
	 * @param $userOffset
	 * @return false|string
	 */
	private static function getServerStartDate($userTime, $userStartDate, $userOffset)
	{
		$userTime = UI::parseTimeAmount($userTime, 'HH:MI');
		$serverStartDateTime = $userStartDate + $userTime - $userOffset;
		$serverStartDate = UI::formatDateTime(static::stripTime($serverStartDateTime));

		return ($serverStartDate? $serverStartDate : '');
	}

	/**
	 * Convert server start date to user start date
	 *
	 * @param $serverTime
	 * @param $serverStartDate
	 * @param $currentTimeZoneOffset
	 * @return false|string
	 */
	private static function getUserStartDate($serverTime, $serverStartDate, $currentTimeZoneOffset)
	{
		$serverTime = UI::parseTimeAmount($serverTime, 'HH:MI');
		$userStartDateTime = $serverStartDate + $serverTime + $currentTimeZoneOffset;
		$userStartDate = UI::formatDateTime(static::stripTime($userStartDateTime));

		return ($userStartDate? $userStartDate : '');
	}

	/**
	 * Convert user end date to server end date
	 *
	 * @param $userTime
	 * @param $userEndDate
	 * @param $userOffset
	 * @return false|string
	 */
	private static function getServerEndDate($userTime, $userEndDate, $userOffset)
	{
		$userTime = UI::parseTimeAmount($userTime, 'HH:MI');
		$serverEndDateTime = $userEndDate + $userTime - $userOffset;
		$serverEndDate = UI::formatDateTime(static::stripTime($serverEndDateTime));

		return ($serverEndDate? $serverEndDate : '');
	}

	/**
	 * Convert server end date to user end date
	 *
	 * @param $serverTime
	 * @param $serverEndDate
	 * @param $currentTimeZoneOffset
	 * @return false|string
	 */
	private static function getUserEndDate($serverTime, $serverEndDate, $currentTimeZoneOffset)
	{
		$serverTime = UI::parseTimeAmount($serverTime, 'HH:MI');
		$userEndDateTime = $serverEndDate + $serverTime + $currentTimeZoneOffset;
		$userEndDate = UI::formatDateTime(static::stripTime($userEndDateTime));

		return ($userEndDate? $userEndDate : '');
	}

	/**
	 * Returns next execution time in server timezone
	 */
	public static function getNextExecutionTime($templateData, string $baseTime = ''): string
	{
		$template = TemplateObject::wakeUpObject([
			'ID' => $templateData['ID'] ?? 0,
			'CREATED_BY' => $templateData['CREATED_BY'],
			'REPLICATE_PARAMS' => is_array($templateData['REPLICATE_PARAMS'])
				? serialize($templateData['REPLICATE_PARAMS'])
				: $templateData['REPLICATE_PARAMS'],
		]);

		$repository = (new TemplateRepository($template->getId()))
			->inject($template);
		$service = new ExecutionService($repository);
		$result = $service->getTemplateNextExecutionTime($baseTime);
		if (!$result->isSuccess())
		{
			return '';
		}
		$nextExecutionTime = $result->getData()['time'];
		return DateTime::createFromTimestamp($nextExecutionTime)->disableUserTime()->toString();
	}

	/**
	 * Strips time (hours, minutes, seconds)
	 *
	 * @param $date
	 * @return false|int
	 */
	private static function stripTime($date)
	{
		$m = (int)date("n", $date);
		$d = (int)date("j", $date);
		$y = (int)date("Y", $date);

		return mktime(0, 0, 0, $m, $d, $y);
	}
}