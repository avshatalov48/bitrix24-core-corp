<?
namespace Bitrix\Timeman\Update;

use CIBlockSection;
use CIntranetUtils;
use CModule;
use COption;
use CUser;
use CUserFieldEnum;

class TimemanVersion18User
{
	private static $SECTIONS_SETTINGS_CACHE = null;
	public $SITE_ID = SITE_ID;

	protected $USER_ID;
	protected $SETTINGS = null;
	protected $bTasksEnabled;
	protected $UF_DEPARTMENT;

	protected static $instance = null;

	public static function instance()
	{
		if (!self::$instance)
		{
			self::$instance = new static();
		}

		return self::$instance;
	}

	public function __construct($USER_ID = 0, $site_id = SITE_ID)
	{
		$this->USER_ID = $USER_ID > 0 ? $USER_ID : $GLOBALS['USER']->GetID();
		$this->SITE_ID = $site_id;
		$this->bTasksEnabled = CModule::IncludeModule('tasks');
	}

	public function GetID()
	{
		return $this->USER_ID;
	}

	public function GetSettings($arNeededSettings = null)
	{
		return $this->__GetSettings($arNeededSettings, false);
	}

	public function GetPersonalSettings($arNeededSettings = null)
	{
		$arSettings = $this->__GetSettings($arNeededSettings, true);

		if (isset($arSettings['UF_TIMEMAN']) && $arSettings['UF_TIMEMAN'] !== '')
		{
			$arSettings['UF_TIMEMAN'] = $arSettings['UF_TIMEMAN'] == 'Y';
		}
		if (isset($arSettings['UF_TM_MAX_START']) && $arSettings['UF_TM_MAX_START'] == '0')
		{
			$arSettings['UF_TM_MAX_START'] = '';
		}
		if (isset($arSettings['UF_TM_MIN_FINISH']) && $arSettings['UF_TM_MIN_FINISH'] == '0')
		{
			$arSettings['UF_TM_MIN_FINISH'] = '';
		}
		if (isset($arSettings['UF_TM_MIN_DURATION']) && $arSettings['UF_TM_MIN_DURATION'] == '0')
		{
			$arSettings['UF_TM_MIN_DURATION'] = '';
		}
		if (isset($arSettings['UF_TM_FREE']) && $arSettings['UF_TM_FREE'] !== '')
		{
			$arSettings['UF_TM_FREE'] = $arSettings['UF_TM_FREE'] == 'Y';
		}
		if (isset($arSettings['UF_TM_ALLOWED_DELTA']) && $arSettings['UF_TM_ALLOWED_DELTA'] >= 0)
		{
			$arSettings['UF_TM_ALLOWED_DELTA'] = static::MakeShortTS($arSettings['UF_TM_ALLOWED_DELTA']);
		}

		return $arSettings;
	}

	protected function __GetSettings($arNeededSettings, $bPersonal = false)
	{
		global $CACHE_MANAGER;

		$cat = intval($bPersonal);

		if (!is_array($this->SETTINGS[$cat]))
		{
			$this->SETTINGS[$cat] = [];

			$cache_id = 'timeman|structure_settings|u' . $this->USER_ID . '_' . $cat;

			if (CACHED_timeman_settings !== false
				&& $CACHE_MANAGER->Read(
					CACHED_timeman_settings,
					$cache_id,
					"timeman_structure_" . COption::GetOptionInt('intranet', 'iblock_structure', false)
				)
			)
			{
				$this->SETTINGS[$cat] = $CACHE_MANAGER->Get($cache_id);
			}
			else
			{
				$this->SETTINGS[$cat] = $bPersonal ? $this->_GetPersonalSettings() : $this->_GetSettings();

				if (CACHED_timeman_settings !== false)
				{
					$CACHE_MANAGER->Set($cache_id, $this->SETTINGS[$cat]);
				}
			}
		}

		$arSettings = $this->SETTINGS[$cat];

		if (is_array($arNeededSettings) && count($arNeededSettings) > 0)
		{
			foreach ($arSettings as $set => $value)
			{
				if (!in_array($set, $arNeededSettings))
				{
					unset($arSettings[$set]);
				}
			}
		}

		return $arSettings;
	}

	protected function _GetPersonalSettings()
	{
		global $USER_FIELD_MANAGER;

		$arPersonalSettings = [];

		$dbRes = CUser::GetByID($this->USER_ID);
		if ($arUser = $dbRes->Fetch())
		{
			$arPersonalSettings = [
				'UF_TIMEMAN' => $arUser['UF_TIMEMAN'],
				'UF_TM_MAX_START' => static::MakeShortTS($arUser['UF_TM_MAX_START']),
				'UF_TM_MIN_FINISH' => static::MakeShortTS($arUser['UF_TM_MIN_FINISH']),
				'UF_TM_MIN_DURATION' => static::MakeShortTS($arUser['UF_TM_MIN_DURATION']),
				'UF_TM_REPORT_REQ' => $arUser['UF_TM_REPORT_REQ'],
				'UF_LAST_REPORT_DATE' => $arUser['UF_LAST_REPORT_DATE'],

				'UF_REPORT_PERIOD' => $arUser['UF_REPORT_PERIOD'],
				'UF_TM_REPORT_DATE' => $arUser['UF_TM_REPORT_DATE'],
				'UF_TM_TIME' => $arUser['UF_TM_TIME'],
				'UF_TM_DAY' => $arUser['UF_TM_DAY'],
				'UF_TM_REPORT_TPL' => $arUser['UF_TM_REPORT_TPL'],
				'UF_TM_FREE' => $arUser['UF_TM_FREE'],
				'UF_TM_ALLOWED_DELTA' => $arUser['UF_TM_ALLOWED_DELTA'],
			];

			$this->UF_DEPARTMENT = $arUser['UF_DEPARTMENT'];

			if ($arPersonalSettings['UF_TIMEMAN'] || $arPersonalSettings['UF_TM_REPORT_REQ']
				|| $arPersonalSettings['UF_TM_FREE'] || $arPersonalSettings['UF_REPORT_PERIOD'])
			{
				$arAllFields = $USER_FIELD_MANAGER->GetUserFields('USER');

				if ($arPersonalSettings['UF_TIMEMAN'])
				{
					$dbRes = CUserFieldEnum::GetList([], [
						'USER_FIELD_ID' => $arAllFields['UF_TIMEMAN']['ID'],
						'ID' => $arPersonalSettings['UF_TIMEMAN'],
					]);

					if ($arRes = $dbRes->Fetch())
					{
						$arPersonalSettings['UF_TIMEMAN'] = $arRes['XML_ID'];
					}
				}
				if ($arPersonalSettings['UF_REPORT_PERIOD'])
				{
					$dbRes = CUserFieldEnum::GetList([], [
						'USER_FIELD_ID' => $arAllFields['UF_REPORT_PERIOD']['ID'],
						'ID' => $arPersonalSettings['UF_REPORT_PERIOD'],
					]);

					if ($arRes = $dbRes->Fetch())
					{
						$arPersonalSettings['UF_REPORT_PERIOD'] = $arRes['XML_ID'];
					}

				}
				if ($arPersonalSettings['UF_TM_REPORT_REQ'])
				{
					$dbRes = CUserFieldEnum::GetList([], [
						'USER_FIELD_ID' => $arAllFields['UF_TM_REPORT_REQ']['ID'],
						'ID' => $arPersonalSettings['UF_TM_REPORT_REQ'],
					]);

					if ($arRes = $dbRes->Fetch())
					{
						$arPersonalSettings['UF_TM_REPORT_REQ'] = $arRes['XML_ID'];
					}
				}

				if ($arPersonalSettings['UF_TM_FREE'])
				{
					$dbRes = CUserFieldEnum::GetList([], [
						'USER_FIELD_ID' => $arAllFields['UF_TM_FREE']['ID'],
						'ID' => $arPersonalSettings['UF_TM_FREE'],
					]);

					if ($arRes = $dbRes->Fetch())
					{
						$arPersonalSettings['UF_TM_FREE'] = $arRes['XML_ID'];
					}
				}
			}
		}

		return $arPersonalSettings;
	}

	protected function _GetSettings()
	{
		global $USER_FIELD_MANAGER;

		$arRes = [];

		$arRes = $this->_GetPersonalSettings();
		if ($arRes)
		{
			if ($arRes['UF_TIMEMAN'] === 'N')
			{
				return ['UF_TIMEMAN' => false];
			}

			$cnt = 0;
			if ($arRes['UF_TIMEMAN'] !== 'Y')
			{
				$cnt++;
			}
			foreach ($arRes as $fld => $value)
			{
				if (!$arRes[$fld] || $arRes[$fld] == '00:00')
				{
					$cnt++;
				}
			}

			if ($cnt > 0)
			{
				if (is_array($this->UF_DEPARTMENT) && count($this->UF_DEPARTMENT) > 0)
				{
					$allSet = [
						'UF_TIMEMAN' => $arRes['UF_TIMEMAN'] ? $arRes['UF_TIMEMAN'] : false,
						'UF_TM_MAX_START' => 86401,
						'UF_TM_MIN_FINISH' => false,
						'UF_TM_MIN_DURATION' => false,
						'UF_TM_REPORT_REQ' => false,
						'UF_REPORT_PERIOD' => $arRes['UF_REPORT_PERIOD'],
						'UF_TM_REPORT_DATE' => $arRes['UF_TM_REPORT_DATE'],
						'UF_TM_TIME' => $arRes['UF_TM_TIME'],
						'UF_TM_DAY' => $arRes['UF_TM_DAY'],
						'UF_TM_REPORT_TPL' => [],
						'UF_TM_FREE' => false,
						'UF_TM_ALLOWED_DELTA' => -1,
					];

					foreach ($this->UF_DEPARTMENT as $dpt)
					{
						$dptSet = static::GetSectionSettings($dpt);

						if ($allSet['UF_TIMEMAN'] !== 'Y' && $dptSet['UF_TIMEMAN'])
						{
							$allSet['UF_TIMEMAN'] = $dptSet['UF_TIMEMAN'];
						}
						if ($dptSet['UF_TM_MAX_START'])
						{
							$allSet['UF_TM_MAX_START'] = min($dptSet['UF_TM_MAX_START'], $allSet['UF_TM_MAX_START']);
						}

						$allSet['UF_TM_MAX_START'] = min($dptSet['UF_TM_MAX_START'], $allSet['UF_TM_MAX_START']);
						$allSet['UF_TM_MIN_FINISH'] = max($dptSet['UF_TM_MIN_FINISH'], $allSet['UF_TM_MIN_FINISH']);
						$allSet['UF_TM_MIN_DURATION'] = max($dptSet['UF_TM_MIN_DURATION'], $allSet['UF_TM_MIN_DURATION']);

						if ($dptSet['UF_TM_REPORT_REQ'])
						{
							$allSet['UF_TM_REPORT_REQ'] = $dptSet['UF_TM_REPORT_REQ'];
						}

						if ((!is_array($allSet['UF_TM_REPORT_TPL']) || count($allSet['UF_TM_REPORT_TPL']) <= 0) && $dptSet['UF_TM_REPORT_TPL'])
						{
							$allSet['UF_TM_REPORT_TPL'] = $dptSet['UF_TM_REPORT_TPL'];
						}

						if ($dptSet['UF_TM_FREE'])
						{
							$allSet['UF_TM_FREE'] = $dptSet['UF_TM_FREE'];
						}

						if ($dptSet['UF_TM_ALLOWED_DELTA'])
						{
							if ($allSet['UF_TM_ALLOWED_DELTA'] == -1 || $dptSet['UF_TM_ALLOWED_DELTA'] < $allSet['UF_TM_ALLOWED_DELTA'])
							{
								$allSet['UF_TM_ALLOWED_DELTA'] = $dptSet['UF_TM_ALLOWED_DELTA'];
							}
						}
					}

					//report fields
					$allSet["UF_REPORT_PERIOD"] = (!$allSet["UF_REPORT_PERIOD"] && $dptSet["UF_REPORT_PERIOD"]) ? $dptSet["UF_REPORT_PERIOD"] : $allSet["UF_REPORT_PERIOD"];
					$allSet["UF_TM_TIME"] = (!$allSet["UF_TM_TIME"] && $dptSet["UF_TM_TIME"]) ? $dptSet["UF_TM_TIME"] : $allSet["UF_TM_TIME"];
					$allSet["UF_TM_DAY"] = (!$allSet["UF_TM_DAY"] && $dptSet["UF_TM_DAY"]) ? $dptSet["UF_TM_DAY"] : $allSet["UF_TM_DAY"];
					$allSet["UF_TM_REPORT_DATE"] = (!$allSet["UF_TM_REPORT_DATE"] && $dptSet["UF_TM_REPORT_DATE"]) ? $dptSet["UF_TM_REPORT_DATE"] : $allSet["UF_TM_REPORT_DATE"];

					if ($arRes['UF_TM_ALLOWED_DELTA'] === '0')
					{
						unset($allSet['UF_TM_ALLOWED_DELTA']);
					}
					foreach ($allSet as $key => $value)
					{
						if (!$arRes[$key] || $arRes[$key] === '00:00')
						{
							$arRes[$key] = $value;
						}
					}

					if ($arRes['UF_TIMEMAN'] === 'N')
					{
						return ($arRes = ['UF_TIMEMAN' => false]);
					}
				}
				elseif ($arRes['UF_TIMEMAN'] !== 'Y')
				{
					// if user is not attached to company structure tm can be allowed only in his own profile
					return ($arRes = ['UF_TIMEMAN' => false]);
				}
			} //if ($cnt > 0)

			$arRes['UF_TIMEMAN'] = true; // it can be only Y|null at this moment
			$arRes['UF_TM_MAX_START'] = $arRes['UF_TM_MAX_START'];
			$arRes['UF_TM_MAX_START'] = $arRes['UF_TM_MAX_START'] > 0
				? $arRes['UF_TM_MAX_START']
				: COption::GetOptionInt('timeman', 'workday_max_start', 33300);
			$arRes['UF_TM_MIN_FINISH'] = $arRes['UF_TM_MIN_FINISH'];
			$arRes['UF_TM_MIN_FINISH'] = $arRes['UF_TM_MIN_FINISH'] > 0
				? $arRes['UF_TM_MIN_FINISH']
				: COption::GetOptionInt('timeman', 'workday_min_finish', 63900);
			$arRes['UF_TM_MIN_DURATION'] = $arRes['UF_TM_MIN_DURATION'];
			$arRes['UF_TM_MIN_DURATION'] = $arRes['UF_TM_MIN_DURATION'] > 0
				? $arRes['UF_TM_MIN_DURATION']
				: COption::GetOptionInt('timeman', 'workday_min_duration', 28800);
			$arRes['UF_TM_REPORT_REQ'] = $arRes['UF_TM_REPORT_REQ']
				? $arRes['UF_TM_REPORT_REQ']
				: COption::GetOptionString('timeman', 'workday_report_required', 'A');
			$arRes['UF_TM_REPORT_TPL'] = $arRes['UF_TM_REPORT_TPL']
				? $arRes['UF_TM_REPORT_TPL']
				: [];
			$arRes['UF_TM_FREE'] = $arRes['UF_TM_FREE']
				? $arRes['UF_TM_FREE'] == 'Y'
				: false;
			$arRes['UF_TM_ALLOWED_DELTA'] = $arRes['UF_TM_ALLOWED_DELTA'] > -1
				? $arRes['UF_TM_ALLOWED_DELTA']
				: COption::GetOptionInt('timeman', 'workday_allowed_delta', '900');
		}
		else
		{
			return ['UF_TIMEMAN' => false];
		}

		return $arRes;
	}

	public static function GetSectionPersonalSettings($section_id, $bHideParentLinks = false, $arNeededSettings = null)
	{
		if (null == self::$SECTIONS_SETTINGS_CACHE)
		{
			self::_GetTreeSettings();
		}

		if (!$bHideParentLinks)
		{
			if (!is_array($arNeededSettings))
			{
				return self::$SECTIONS_SETTINGS_CACHE[$section_id];
			}
			else
			{
				$ar = self::$SECTIONS_SETTINGS_CACHE[$section_id];
				foreach ($ar as $key => $value)
				{
					if (!in_array($key, $arNeededSettings))
					{
						unset($ar[$key]);
					}
				}
				return $ar;
			}
		}
		else
		{
			$res = self::$SECTIONS_SETTINGS_CACHE[$section_id];
			foreach ($res as $key => $value)
			{
				if (is_array($arNeededSettings) && !in_array($key, $arNeededSettings))
				{
					unset($res[$key]);
				}
				elseif (mb_substr($res[$key], 0, 8) == '_PARENT_')
				{
					$res[$key] = null;
				}
			}
			return $res;
		}
	}

	public static function GetModuleSettings($arNeededSettings = false)
	{
		$arOptionsSettings = [
			'UF_TIMEMAN' => true,
			'UF_TM_MAX_START' => COption::GetOptionInt('timeman', 'workday_max_start', 33300),
			'UF_TM_MIN_FINISH' => COption::GetOptionInt('timeman', 'workday_min_finish', 63900),
			'UF_TM_MIN_DURATION' => COption::GetOptionInt('timeman', 'workday_min_duration', 28800),
			'UF_TM_REPORT_REQ' => COption::GetOptionString('timeman', 'workday_report_required', 'A'),
			'UF_TM_ALLOWED_DELTA' => COption::GetOptionInt('timeman', 'workday_allowed_delta', '900'),
			'UF_TM_REPORT_TPL' => [],
			'UF_TM_FREE' => false,
		];

		if (!$arNeededSettings)
		{
			return $arOptionsSettings;
		}
		else
		{
			$res = [];
			foreach ($arNeededSettings as $k)
			{
				$res[$k] = $arOptionsSettings[$k];
			}

			return $res;
		}
	}

	public static function GetSectionSettings($section_id, $arNeededSettings = null)
	{
		if (null == self::$SECTIONS_SETTINGS_CACHE)
		{
			self::_GetTreeSettings();
		}

		if ($section_id > 0)
		{
			$res = self::GetSectionPersonalSettings($section_id);

			$arSettings = is_array($arNeededSettings) ? $arNeededSettings : ['UF_TIMEMAN', 'UF_TM_MAX_START', 'UF_TM_MIN_FINISH', 'UF_TM_MIN_DURATION',
				'UF_TM_REPORT_REQ', 'UF_TM_REPORT_TPL', 'UF_TM_FREE', 'UF_TM_REPORT_DATE', 'UF_TM_DAY', 'UF_REPORT_PERIOD', 'UF_TM_TIME', 'UF_TM_ALLOWED_DELTA'];

			if (is_array($res) && count($arSettings) > 0)
			{
				$parent = 0;
				foreach ($res as $key => $v)
				{
					if (!in_array($key, $arSettings))
					{
						unset($res[$key]);
					}
				}

				foreach ($arSettings as $k => $key)
				{
					if (!is_array($res[$key]) && mb_substr($res[$key], 0, 8) == '_PARENT_')
					{
						$parent = intval(mb_substr($res[$key], 9));
						unset($res[$key]);
					}
					else
					{
						unset($arSettings[$k]);
					}
				}

				if (count($arSettings) > 0 && $parent > 0)
				{
					$res = array_merge($res, self::GetSectionSettings($parent, $arSettings));
				}

				if ($arNeededSettings === null)
				{
					foreach ($res as $key => $value)
					{
						if (!is_array($res[$key]) && mb_substr($res[$key], 0, 8) == '_PARENT_')
						{
							$res[$key] = '';
						}
					}
				}

				if (isset($res['UF_TIMEMAN']) && !$res['UF_TIMEMAN'])
				{
					$res['UF_TIMEMAN'] = 'Y';
				}
				if (isset($res['UF_TM_REPORT_TPL']) && !is_array($res['UF_TM_REPORT_TPL']))
				{
					$res['UF_TM_REPORT_TPL'] = [];
				}

				return $res;
			}
		}

		return [];
	}

	private static function _GetTreeSettings()
	{
		global $USER_FIELD_MANAGER, $CACHE_MANAGER;

		self::$SECTIONS_SETTINGS_CACHE = [];

		$ibDept = COption::GetOptionInt('intranet', 'iblock_structure', false);

		$cache_id = 'timeman|structure_settings|' . $ibDept;

		if (CACHED_timeman_settings !== false
			&& $CACHE_MANAGER->Read(CACHED_timeman_settings, $cache_id, "timeman_structure_" . $ibDept))
		{
			self::$SECTIONS_SETTINGS_CACHE = $CACHE_MANAGER->Get($cache_id);
		}
		else
		{
			$arAllFields = $USER_FIELD_MANAGER->GetUserFields('IBLOCK_' . $ibDept . '_SECTION');

			$arUFValues = [];

			$arEnumFields = ['UF_TIMEMAN', 'UF_TM_REPORT_REQ', 'UF_TM_FREE', 'UF_REPORT_PERIOD'];
			foreach ($arEnumFields as $fld)
			{
				$dbRes = CUserFieldEnum::GetList([], [
					'USER_FIELD_ID' => $arAllFields[$fld]['ID'],
				]);
				while ($arRes = $dbRes->Fetch())
				{
					$arUFValues[$arRes['ID']] = $arRes['XML_ID'];
				}
			}

			$arSettings = ['UF_TIMEMAN', 'UF_TM_MAX_START', 'UF_TM_MIN_FINISH', 'UF_TM_MIN_DURATION', 'UF_TM_REPORT_REQ',
				'UF_TM_REPORT_TPL', 'UF_TM_FREE', 'UF_TM_REPORT_DATE', 'UF_TM_DAY', 'UF_REPORT_PERIOD', 'UF_TM_TIME', 'UF_TM_ALLOWED_DELTA'];
			$arReportSettings = ['UF_TM_REPORT_DATE', 'UF_TM_DAY', 'UF_TM_TIME'];
			$dbRes = CIBlockSection::GetList(
				["LEFT_MARGIN" => "ASC"],
				['IBLOCK_ID' => $ibDept, 'ACTIVE' => 'Y'],
				false,
				['ID', 'IBLOCK_SECTION_ID', 'UF_TIMEMAN', 'UF_TM_MAX_START', 'UF_TM_MIN_FINISH', 'UF_TM_MIN_DURATION', 'UF_TM_REPORT_REQ',
					'UF_TM_REPORT_TPL', 'UF_TM_FREE', 'UF_REPORT_PERIOD', 'UF_TM_REPORT_DATE', 'UF_TM_DAY', 'UF_TM_TIME', 'UF_TM_ALLOWED_DELTA']
			);
			while ($arRes = $dbRes->Fetch())
			{
				$arSectionSettings = [];
				foreach ($arSettings as $key)
				{
					$arSectionSettings[$key] = ($arRes[$key] && $arRes[$key] != '00:00'
						? (
						isset($arUFValues[$arRes[$key]]) && !in_array($key, $arReportSettings)
							? $arUFValues[$arRes[$key]]
							: (
						in_array($key, $arReportSettings)
							? $arRes[$key]
							: (
						is_array($arRes[$key])
							? $arRes[$key]
							: self::MakeShortTS($arRes[$key])
						)

						)
						)
						: (
						$arRes['IBLOCK_SECTION_ID'] > 0
							? '_PARENT_|' . $arRes['IBLOCK_SECTION_ID']
							: ''
						)
					);
				}

				self::$SECTIONS_SETTINGS_CACHE[$arRes['ID']] = $arSectionSettings;
			}

			if (CACHED_timeman_settings !== false)
			{
				$CACHE_MANAGER->Set($cache_id, self::$SECTIONS_SETTINGS_CACHE);
			}
		}
	}

	public static function MakeShortTS($time)
	{
		static $arCoefs = [3600, 60, 1];

		if ($time === intval($time))
		{
			return $time % 86400;
		}

		$amPmTime = explode(' ', $time);
		if (count($amPmTime) > 1)
		{
			$time = $amPmTime[0];
			$mt = $amPmTime[1];
		}

		$arValues = explode(':', $time);

		$cnt = count($arValues);
		if ($cnt <= 1)
		{
			return 0;
		}
		elseif ($cnt <= 2)
		{
			$arValues[] = 0;
		}

		// if time as AmPm
		if (!empty($mt) && strcasecmp($mt, 'pm') === 0)
		{
			if ($arValues[0] < 12)
			{
				$arValues[0] = $arValues[0] + 12;
			}
		}

		$ts = 0;
		for ($i = 0; $i < 3; $i++)
		{
			$ts += intval($arValues[$i] * $arCoefs[$i]);
		}

		return $ts % 86400;
	}

	public static function GetUserManagers($USER_ID, $bCheckExistance = true)
	{
		$arStruct = CIntranetUtils::GetStructure();

		$arHeads = [];

		foreach ($arStruct['DATA'] as $dpt => $arDpt)
		{
			if (in_array($USER_ID, $arDpt['EMPLOYEES']))
			{
				$arCurDpt = $arDpt;

				while (
					(
						!$arCurDpt['UF_HEAD']
						|| $arCurDpt['UF_HEAD'] == $USER_ID
						|| (
							$bCheckExistance
							&& (
								!($arUser = CUser::GetByID($arCurDpt['UF_HEAD'])->Fetch())
								|| $arUser['ACTIVE'] == 'N'
							)
						)
					)
					&& $arCurDpt['IBLOCK_SECTION_ID'] > 0
				)
				{
					$arCurDpt = $arStruct['DATA'][$arCurDpt['IBLOCK_SECTION_ID']];
				}

				if ($arCurDpt['UF_HEAD'])
				{
					$arHeads[] = $arCurDpt['UF_HEAD'];
				}
			}
		}

		return array_unique($arHeads);
	}
}