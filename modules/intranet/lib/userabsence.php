<?
namespace Bitrix\Intranet;

use Bitrix\Main\Data\Cache;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;

class UserAbsence
{
	const CACHE_TTL = 2678400; // 1 month
	const CACHE_PATH = '/bx/intranet/absence/';

	public static $defaultVacationTypes = [
		'VACATION',
		'LEAVESICK',
		'LEAVEMATERINITY',
		'LEAVEUNPAYED'
	];

	private static $activeVacationTypes = null;

	/**
	 * @return int
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function getIblockId()
	{
		$iblockId = Option::get('intranet', 'iblock_absence', 0);

		return intval($iblockId);
	}

	/**
	 * @param $xmlId
	 * @param string $default
	 * @return string
	 */
	public static function getTypeCaption($xmlId, $default = '')
	{
		return Loc::getMessage('INTR_USER_ABSENCE_TYPE_' . $xmlId) ?: $default;
	}

	/**
	 * @param array $types
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function saveActiveVacationTypes($types = [])
	{
		$list = array_keys(self::getVacationTypes());

		$save = [];
		foreach ($types as $type)
		{
			if (in_array($type, $list, true))
			{
				$save[] = $type;
			}
		}

		Option::set('intranet', 'vacation_types', serialize($save));
		self::$activeVacationTypes = $save;

		self::cleanCache();

		return true;
	}

	public static function getActiveVacationTypes()
	{
		if (is_array(self::$activeVacationTypes))
		{
			return self::$activeVacationTypes;
		}

		$defaultVacationTypes = self::$defaultVacationTypes;

		$vacationTypesOption = Option::get('intranet', 'vacation_types', null);
		if ($vacationTypesOption)
		{
			$defaultVacationTypes = unserialize($vacationTypesOption, ["allowed_classes" => false]);
		}

		self::$activeVacationTypes = $defaultVacationTypes;

		return self::$activeVacationTypes;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function getVacationTypes()
	{
		$defaultVacationTypes = self::getActiveVacationTypes();

		$types = [];

		if (Loader::includeModule('iblock'))
		{
			$res = \CIBlockPropertyEnum::GetList(Array('DEF'=>'DESC', 'SORT'=>'ASC'), Array('IBLOCK_ID'=>self::getIblockId(), 'CODE'=>'ABSENCE_TYPE'));
			while ($row = $res->GetNext())
			{
				$types[$row['EXTERNAL_ID']] = [
					'ID' => $row['EXTERNAL_ID'],
					'ENUM_ID' => $row['ID'],
					'NAME' => self::getTypeCaption($row['EXTERNAL_ID']),
					'ACTIVE' => in_array($row['EXTERNAL_ID'], $defaultVacationTypes),
				];
			}
		}

		return $types;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ObjectException
	 */
	public static function getCurrentMonth()
	{
		static $result;

		if (!is_null($result))
		{
			return $result;
		}

		$cache = Cache::createInstance();
		if($cache->initCache(self::CACHE_TTL, 'list_v7_'.date('Y-m-01'), self::CACHE_PATH))
		{
			$result = $cache->getVars();
		}
		else
		{
			$iblockId = self::getIblockId();
			if ($iblockId <= 0 || !Loader::includeModule('iblock'))
			{
				$cache->startDataCache();
				$cache->endDataCache([]);

				return [];
			}

			$typesList = Array();
			$vacationTypes = Array();
			$enums = \CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>self::getIblockId(), "CODE"=>"ABSENCE_TYPE"));
			while ($enum_fields = $enums->GetNext())
			{
				$typesList[(int)$enum_fields['ID']] = $enum_fields['EXTERNAL_ID'];

				if(!self::isVacation($enum_fields['EXTERNAL_ID']))
				{
					continue;
				}

				$vacationTypes[(int)$enum_fields['ID']] = $enum_fields['EXTERNAL_ID'];
			}

			$timeZoneEnabled = \CTimeZone::Enabled();

			if ($timeZoneEnabled)
			{
				\CTimeZone::Disable();
			}

			$absenceData = \CIntranetUtils::GetAbsenceData(
				array(
					'PER_USER' => true,
					'SELECT' => array('ID', 'DATE_ACTIVE_FROM', 'DATE_ACTIVE_TO', 'PROPERTY_ABSENCE_TYPE'),
					'ABSENCE_IBLOCK_ID' => self::getIblockId(),
					'DATE_START' => (new Date(null, 'Y-m-01'))->add('-1 day'),
					'DATE_FINISH' => (new Date(null, 'Y-m-01'))->add('1 month'),
				),
				BX_INTRANET_ABSENCE_HR
			);

			if ($timeZoneEnabled)
			{
				\CTimeZone::Enable();
			}

			$result = Array();
			foreach ($absenceData as $userId => $record)
			{
				foreach ($record as $index => $data)
				{
					$data['PROPERTY_ABSENCE_TYPE_ENUM_ID'] = (int)$data['PROPERTY_ABSENCE_TYPE_ENUM_ID'];

					$dateFrom = new \Bitrix\Main\Type\DateTime($data['DATE_FROM']);
					$dateTo = new \Bitrix\Main\Type\DateTime($data['DATE_TO']);
					$result[$userId][$index] = Array(
						'ID' => $data['ID'],
						'USER_ID' => $data['USER_ID'],
						'ENTRY_TYPE' => $typesList[$data['PROPERTY_ABSENCE_TYPE_ENUM_ID']] ?? null,
						'ENTRY_TYPE_ID' => $data['PROPERTY_ABSENCE_TYPE_ENUM_ID'],
						'ENTRY_TYPE_VALUE' => $data['PROPERTY_ABSENCE_TYPE_VALUE'],
						'IS_VACATION' => in_array($data['PROPERTY_ABSENCE_TYPE_ENUM_ID'], array_keys($vacationTypes)),
						'DATE_FROM_TS' => $dateFrom->getTimestamp(),
						'DATE_TO_TS' => $dateTo->getTimestamp(),
					);
				}
			}

			$cache->startDataCache();
			$cache->endDataCache($result);
		}

		return $result;
	}

	/**
	 * @param $userId
	 * @param bool $returnToDate
	 * @return bool|mixed
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ObjectException
	 */
	public static function isAbsent($userId, bool $returnToDate = false)
	{
		$result = self::getCurrentMonth();

		if (isset($result[$userId]))
		{
			$now = new \Bitrix\Main\Type\DateTime();
			$nowTs = $now->getTimestamp();

			foreach ($result[$userId] as $vacation)
			{
				if (isset($vacation['IS_VACATION']) && !$vacation['IS_VACATION'])
				{
					continue;
				}

				if ($nowTs >= $vacation['DATE_FROM_TS'] && $nowTs < $vacation['DATE_TO_TS'])
				{
					if ($returnToDate)
					{
						return $vacation;
					}

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @param $userId
	 * @param bool $returnToDate
	 * @return bool|mixed
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ObjectException
	 */
	public static function isAbsentOnVacation($userId, bool $returnToDate = false)
	{
		$result = self::getCurrentMonth();

		if (isset($result[$userId]))
		{
			$now = new \Bitrix\Main\Type\DateTime();
			$nowTs = $now->enableUserTime()->getTimestamp();

			foreach ($result[$userId] as $vacation)
			{
				if (!$vacation['IS_VACATION'])
				{
					continue;
				}

				$dateFrom = $vacation['DATE_FROM_TS'] + \CTimeZone::GetOffset();
				$dateTo = $vacation['DATE_TO_TS'] + \CTimeZone::GetOffset();
				$isCounterpart = $dateFrom === $dateTo;
				$isLastDayWithoutTime = !\CIntranetUtils::IsDateTime($dateTo);

				if ($isCounterpart || $isLastDayWithoutTime)
				{
					$dateTo += 86400;
				}

				if ($nowTs >= $dateFrom && $nowTs < $dateTo)
				{
					if ($isCounterpart || $isLastDayWithoutTime)
					{
						$vacation['DATE_TO_TS'] += 1;
					}

					if ($returnToDate)
					{
						return $vacation;
					}

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @return void
	 */
	public static function cleanCache()
	{
		\CIBlock::clearIblockTagCache(Option::get('intranet', 'iblock_absence'));
		Cache::createInstance()->cleanDir(UserAbsence::CACHE_PATH);
	}

	/**
	 * @param $userId
	 * @return array|bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ObjectException
	 */
	public static function onUserOnlineStatusGetCustomOfflineStatus($userId)
	{
		if (self::isAbsentOnVacation($userId))
		{
			return Array(
				'STATUS' => 'vacation',
				'STATUS_TEXT' => Loc::getMessage('USER_ABSENCE_STATUS_VACATION')
			);
		}

		return false;
	}

	/**
	 * @deprecated
	 *
	 * @param $fields
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function onAfterIblockElementModify($fields)
	{
		$iblockId = UserAbsence::getIblockId();
		if ($iblockId > 0 && intval($fields['IBLOCK_ID']) == $iblockId)
		{
			self::cleanCache();
		}

		return true;
	}

	/**
	 * Checks whether the absence type is vacation-related.
	 *
	 * @param $type
	 * @return bool
	 */
	public static function isVacation($type)
	{
		$result = false;

		if (in_array($type, self::getActiveVacationTypes()))
		{
			$result = true;
		}

		return $result;
	}
}
