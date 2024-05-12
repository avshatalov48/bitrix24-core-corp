<?php
namespace Bitrix\Timeman\Repository;

use Bitrix\Timeman\Helper\TimeHelper;
use COption;

class AbsenceRepository
{
	public function findAbsences($dateStart, $dateFinish, $users = false)
	{
		if (!\Bitrix\Main\Loader::includeModule('intranet'))
		{
			return [];
		}
		if (is_numeric($users))
		{
			$users = [$users];
		}
		$absenceData = \CIntranetUtils::getAbsenceData([
			'DATE_START' => $dateStart,
			'DATE_FINISH' => $dateFinish,
			'USERS' => $users,
			'CALENDAR_IBLOCK_ID' => COption::getOptionInt('intranet', 'iblock_calendar'),
			'ABSENCE_IBLOCK_ID' => COption::getOptionInt('intranet', 'iblock_absence'),
			'PER_USER' => true,
		]);
		foreach ($absenceData as $userId => $userAbsences)
		{
			foreach ($userAbsences as $index => $absenceItem)
			{
				$userTimeAbsStart = null;
				$userTimeAbsEnd = null;

				if (isset($absenceItem['DATE_ACTIVE_FROM']))
				{
					$userTimeAbsStart = \Bitrix\Main\Type\DateTime::createFromUserTime($absenceItem['DATE_ACTIVE_FROM']);

				}
				if (isset($absenceItem['DATE_ACTIVE_TO']))
				{
					$userTimeAbsEnd = \Bitrix\Main\Type\DateTime::createFromUserTime($absenceItem['DATE_ACTIVE_TO']);
				}

				$absenceData[$userId][$index]['tm_absStartDateTime'] = $userTimeAbsStart;
				$absenceData[$userId][$index]['tm_absEndDateTime'] = $userTimeAbsEnd;
			}
		}

		return $absenceData;
	}
}
