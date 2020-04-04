<?php
namespace Bitrix\Crm;
use Bitrix\Main;
class BirthdayReminder
{
	public static function prepareSorting($date)
	{
		$site = new \CSite();
		$time = $date !== '' ? MakeTimeStamp($date, $site->GetDateFormat('SHORT')) : false;
		if($time === false)
		{
			return 1024;
		}

		return self::internalPrepareSorting($time);
	}
	private static function internalPrepareSorting($time)
	{
		$day = (int)date('d', $time);
		$month = (int)date('n', $time);
		return (($month - 1) << 5) + $day;
	}

	public static function getNearestEntities($entityID, $currentDate, $startDate = '', $responsibleID = 0, $intervalInDays = 7, $checkPermissions = true, $limit = 5)
	{
		if(!is_string($startDate) || $startDate === '')
		{
			$startDate = $currentDate;
		}

		$site = new \CSite();
		$dateFormat = $site->GetDateFormat('SHORT');
		$curretTime = $currentDate !== '' ? MakeTimeStamp($currentDate, $dateFormat) : false;
		$startTime = $startDate !== '' ? MakeTimeStamp($startDate, $dateFormat) : false;

		if($startTime === false)
		{
			return array();
		}

		$dt = new \DateTime();
		$dt->setTimestamp($startTime);
		$dt->add(new \DateInterval("P{$intervalInDays}D"));
		$endTime = $dt->getTimeStamp();

		$currentSorting = self::internalPrepareSorting($curretTime);
		$startSorting = self::internalPrepareSorting($startTime);
		$endSorting = self::internalPrepareSorting($endTime);

		$filterData = array();
		if($startSorting <= $endSorting)
		{
			$filterData[] = array(
				'FIELDS' => array(
					'>=BIRTHDAY_SORT' => $startSorting,
					'<=BIRTHDAY_SORT' => $endSorting
				)
			);
		}
		else
		{
			$startYear = (int)date('Y', $startTime);
			$endYear = (int)date('Y', $endTime);

			$filterData[] = array(
				'FIELDS' => array(
					'>=BIRTHDAY_SORT' => $startSorting,
					'<=BIRTHDAY_SORT' => self::internalPrepareSorting(mktime(0, 0, 0, 12, 31, $startYear))
				),
				'SORT_OFFSET' => 0
			);

			$filterData[] = array(
				'FIELDS' => array(
					'>=BIRTHDAY_SORT' => self::internalPrepareSorting(mktime(0, 0, 0, 1, 1, $endYear)),
					'<=BIRTHDAY_SORT' => $endSorting
				),
				'SORT_OFFSET' => self::internalPrepareSorting(mktime(0, 0, 0, 12, 31, $startYear))
			);
		}

		$result = array();
		if($entityID === \CCrmOwnerType::Lead)
		{
			foreach($filterData as $filterItem)
			{
				$filter = $filterItem['FIELDS'];
				$filter['CHECK_PERMISSIONS'] = $checkPermissions ? 'Y' : 'N';
				if($responsibleID > 0)
				{
					$filter['=ASSIGNED_BY_ID'] = $responsibleID;
				}

				$dbResult = \CCrmLead::GetListEx(
					array(),
					$filter,
					false,
					array('nTopCount' => $limit),
					array('ID', 'BIRTHDATE', 'BIRTHDAY_SORT', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME')
				);

				$sortOffset = isset($filterItem['SORT_OFFSET']) ? $filterItem['SORT_OFFSET'] : 0;
				while($fields = $dbResult->Fetch())
				{
					self::prepareEntity(\CCrmOwnerType::Lead, $fields, $currentSorting, $sortOffset);
					$result[] = $fields;
				}
			}
		}
		elseif($entityID === \CCrmOwnerType::Contact)
		{
			foreach($filterData as $filterItem)
			{
				$filter = $filterItem['FIELDS'];
				$filter['CHECK_PERMISSIONS'] = $checkPermissions ? 'Y' : 'N';
				if($responsibleID > 0)
				{
					$filter['=ASSIGNED_BY_ID'] = $responsibleID;
				}

				$dbResult = \CCrmContact::GetListEx(
					array(),
					$filter,
					false,
					array('nTopCount' => $limit),
					array('ID', 'BIRTHDATE', 'BIRTHDAY_SORT', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'PHOTO')
				);

				$sortOffset = isset($filterItem['SORT_OFFSET']) ? $filterItem['SORT_OFFSET'] : 0;
				while($fields = $dbResult->Fetch())
				{
					self::prepareEntity(\CCrmOwnerType::Contact, $fields, $currentSorting, $sortOffset);
					$result[] = $fields;
				}
			}
		}

		return $result;
	}

	private static function prepareEntity($entityTypeID, array &$fields, $currentSorting, $sortOffset = 0)
	{
		$fields['ENTITY_TYPE_ID'] = $entityTypeID;
		$fields['IMAGE_ID'] = $entityTypeID === \CCrmOwnerType::Contact && isset($fields['PHOTO'])
			? (int)$fields['PHOTO'] : 0;

		$sorting = isset($fields['BIRTHDAY_SORT']) ? (int)$fields['BIRTHDAY_SORT'] : 512;
		if($sortOffset > 0)
		{
			$sorting += $sortOffset;
		}
		$fields['BIRTHDAY_SORT'] = $sorting;
		$fields['IS_BIRTHDAY'] = $sorting === $currentSorting;
	}
}