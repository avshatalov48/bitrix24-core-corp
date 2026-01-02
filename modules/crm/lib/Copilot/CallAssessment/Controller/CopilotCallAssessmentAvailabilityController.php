<?php

namespace Bitrix\Crm\Copilot\CallAssessment\Controller;

use Bitrix\Crm\Copilot\CallAssessment\Entity\CopilotCallAssessmentAvailabilityTable;
use Bitrix\Crm\Copilot\CallAssessment\Enum\AvailabilityWeekdayType;
use Bitrix\Crm\Settings\WorkTime;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\Result;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\Type\DateTime;

final class CopilotCallAssessmentAvailabilityController
{
	use Singleton;
	
	public function deleteByAssessmentId(int $assessmentId): ?Result
	{
		if ($assessmentId <= 0)
		{
			return null;
		}
		
		$sqlHelper = Application::getConnection()->getSqlHelper();
		
		$sql =
			'DELETE FROM b_crm_copilot_call_assessment_availability '
			. ' WHERE ASSESSMENT_ID =' . $sqlHelper->convertToDbInteger($assessmentId)
		;
		
		return Application::getConnection()->query($sql);
	}
	
	public function add(int $assessmentId, array $data): AddResult
	{
		if ($assessmentId <= 0)
		{
			throw new ArgumentException('Invalid assessment ID');
		}
		
		if (!isset($data['startPoint'], $data['endPoint']))
		{
			throw new ArgumentException('Start and end points are required');
		}
		
		$weekdayType = $data['weekdayType'] ?? null;
		$startPoint = DateTime::createFromUserTime($data['startPoint']);
		$endPoint = DateTime::createFromUserTime($data['endPoint']);

		if (isset($weekdayType)) {
			// modify date to 9999-12-31
			$startPoint->setDate(9999, 12, 31);
			$endPoint->setDate(9999, 12, 31);
		}
		
		if ($startPoint === $endPoint)
		{
			throw new ArgumentException('Start point cannot be same as end point');
		}
		
		return CopilotCallAssessmentAvailabilityTable::add([
			'ASSESSMENT_ID' => $assessmentId,
			'START_POINT' => $startPoint,
			'END_POINT' => $endPoint,
			'WEEKDAY_TYPE' => $weekdayType,
		]);
	}
	
	public function getCurrentAvailableAssessmentIds(): array
	{
		$weekdayType = AvailabilityWeekdayType::WEEKENDS->value;
		if ((new WorkTime())->isWorkDay(new DateTime()))
		{
			$weekdayType = AvailabilityWeekdayType::WORKING->value;
		}
		
		$sqlHelper = Application::getConnection()->getSqlHelper();

		$dayOfWeek = AvailabilityWeekdayType::getCurrentDayOfWeek();
		$tableName = CopilotCallAssessmentAvailabilityTable::getTableName();
		$sql = "
			SELECT DISTINCT ASSESSMENT_ID
			FROM {$tableName}
			WHERE (CURRENT_TIMESTAMP BETWEEN START_POINT AND END_POINT)
			OR (
				WEEKDAY_TYPE = " . $sqlHelper->convertToDbString($dayOfWeek) . "
				AND CURRENT_TIME BETWEEN CAST(START_POINT AS TIME) AND CAST(END_POINT AS TIME)
			)
			OR (
				WEEKDAY_TYPE = " . $sqlHelper->convertToDbString($weekdayType) . "
				AND CURRENT_TIME BETWEEN CAST(START_POINT AS TIME) AND CAST(END_POINT AS TIME)
			)
		";
		$res = Application::getConnection()->query($sql);
		$result = [];
		while($fields = $res->fetch())
		{
			$result[] = (int)$fields['ASSESSMENT_ID'];
		}
		
		return $result;
	}
}
