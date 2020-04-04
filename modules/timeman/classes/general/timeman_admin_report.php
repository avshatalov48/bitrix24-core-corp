<?
class CTimeManAdminReport
{
	private $bShowAll = true;
	private $page = 1;
	private $amount = 30;
	private $ts = 0;
	private $department = 0;
	private $path_user = null;
	private $nav_handler = '';

	private $arAccessUsers = null;
	private $bCanReadAll = false;
	private $bCanEditAll = false;

	public function __construct($arParams)
	{
		if ($arParams['ts'] <= 0)
			$arParams['ts'] = time();

		$this->bShowAll = $arParams['show_all'];
		$this->ts = date('U', intval($arParams['ts'])+86401);
		$this->path_user = $arParams['path_user'];
		$this->nav_handler = $arParams['nav_handler'];

		if ($arParams['page'] > 0)
			$this->page = intval($arParams['page']);
		if ($arParams['amount'] > 0)
			$this->amount = intval($arParams['amount']);
		if ($arParams['department'] > 0)
			$this->department = intval($arParams['department']);
	}

	public function GetData()
	{
		$data = array('DEPARTMENTS' => array(), 'USERS' => array(), 'NAV' => '');

		if ($this->_checkAccess())
		{
			$arUserIDs = array();
			$data['USERS'] = $this->_getUsersData($arUserIDs);

			if (count($arUserIDs) > 0)
			{
				$data['DEPARTMENTS'] = $this->_getDepartmentsData(array_keys($arUserIDs));
			}

			$old_res = $data;
			$data = array('DEPARTMENTS' => array(), 'USERS' => array(), 'NAV' => '');

			foreach ($arUserIDs as $dpt_id => $arDptUsers)
			{
				$data['DEPARTMENTS'][] = $old_res['DEPARTMENTS'][$dpt_id];
				foreach ($arDptUsers as $user_id)
				{
					if ($old_res['USERS'][$user_id])
					{
						$old_res['USERS'][$user_id]['DEPARTMENT'] = $dpt_id;
						$old_res['USERS'][$user_id]['HEAD'] =
							$old_res['DEPARTMENTS'][$dpt_id]['UF_HEAD'] == $user_id;

						$data['USERS'][] = $old_res['USERS'][$user_id];
					}
				}
			}

			$data['NAV'] = $this->_getNavData();
		}

		return $data;
	}

	private function _checkAccess()
	{
		static $access = null;

		if ($access === null)
		{
			$this->arAccessUsers = CTimeMan::GetAccess();
			if (count($this->arAccessUsers['READ']) > 0)
			{
				$this->bCanReadAll = in_array('*', $this->arAccessUsers['READ']);
				$this->bCanEditAll = in_array('*', $this->arAccessUsers['WRITE']);

				$access = true;
			}
			else
			{
				$access = false;
			}
		}

		return $access;
	}

	private function _getUsersData(&$arUserIDs)
	{
		$arResult = array();

		$arFilter = $this->__getFilter();

		if (!$this->bShowAll)
		{
			$arActiveUsers = array();
			$dbRes = CTimeManEntry::GetList(
				array('USER_ID' => 'ASC'), $arFilter,
				array('USER_ID'), false,
				array('USER_ID')
			);
			while ($arRes = $dbRes->GetNext())
			{
				$arActiveUsers[] = $arRes['USER_ID'];
			}

			if (!$this->bCanReadAll)
			{
				$this->arAccessUsers['READ'] = array_intersect($this->arAccessUsers['READ'], $arActiveUsers);
			}
			else
			{
				$this->arAccessUsers['READ'] = $arActiveUsers;
			}

			$this->bCanReadAll = false;

			if (count($this->arAccessUsers['READ']) <= 0)
				return $arResult;
		}

		$arUserIDs = CIntranetUtils::GetEmployeesForSorting($this->page, $this->amount, $this->department, $this->bCanReadAll ? false : $this->arAccessUsers['READ']);

		$arUsers = array();
		foreach ($arUserIDs as $ar)
			$arUsers = array_merge($arUsers, $ar);

		if(count($arUsers) > 0)
		{
			$arFilter['USER_ID'] = $arUsers;

			if ($this->bShowAll)
			{
				$dbRes = CUser::GetList($by = 'LAST_NAME', $order  = 'asc', array('ID' => implode('|', $arUsers), 'ACTIVE' => 'Y'), array('SELECT' => array('*', 'UF_DEPARTMENT')));
				while ($arRes = $dbRes->GetNext())
				{
					$arResult[$arRes['ID']] = $this->__getUserRow($arRes, '', $arFilter);
				}
			}

			$dbRes = CTimeManEntry::GetList(
				array(
					'USER_LAST_NAME' => 'ASC',
					'DATE_START' => 'ASC'
				), $arFilter, false, false, array('*', 'UF_DEPARTMENT', 'ACTIVATED')
			);

			$arEntriesMap = array();
			while($arRes = $dbRes->GetNext())
			{
				if (!$arResult[$arRes['USER_ID']])
				{
					$arResult[$arRes['USER_ID']] = $this->__getUserRow($arRes, 'USER_', $arFilter);
				}
				elseif ($arEntriesMap[$arRes['ID']])
				{
					continue;
				}

				$ts_start = MakeTimeStamp($arRes['DATE_START']);

				$arEntry = array(
					'ID' => $arRes['ID'],
					'USER_ID' => $arRes['USER_ID'],
					'DAY' => date('j', $ts_start),
					'ACTIVE' => $arRes['ACTIVE'] == 'Y',
					'PAUSED' => $arRes['PAUSED'] == 'Y',
					'ACTIVATED' => $arRes['ACTIVATED'] == 'Y',
					'DATE_START' => MakeTimeStamp($arRes['DATE_START'])-CTimeZone::GetOffset(), // unchanged time
					'DATE_FINISH' => $arRes['DATE_FINISH'] ? MakeTimeStamp($arRes['DATE_FINISH'])-CTimeZone::GetOffset() : '', // unchanged time
					'TIME_START' => $arRes['TIME_START'], // unchanged time
					'TIME_FINISH' => $arRes['TIME_FINISH'], // unchanged time
					'DURATION' => $arRes['DURATION'],
					'TIME_LEAKS' => $arRes['TIME_LEAKS'],
					'CAN_EDIT' => ($this->bCanEditAll || in_array($arRes['USER_ID'], $this->arAccessUsers['WRITE'])),
				);

				if ($arRes['DATE_FINISH'] && $arRes['PAUSED'] !== 'Y')
				{
					if ($arRes['ACTIVE'] == 'Y')
					{
						$arResult[$arRes['USER_ID']]['TOTAL'] += $arRes['DURATION'];
						$arResult[$arRes['USER_ID']]['TOTAL_DAYS']++;

						$arSettings = $arResult[$arRes['USER_ID']]['SETTINGS'];

						if (
							!$arSettings['UF_TM_FREE'] &&
							(
							$arSettings['UF_TM_MAX_START'] < $arEntry['TIME_START']
							|| $arSettings['UF_TM_MIN_FINISH'] > $arEntry['TIME_FINISH']
							|| $arSettings['UF_TM_MIN_DURATION'] > $arEntry['DURATION']
							)
						)
							$arResult[$arRes['USER_ID']]['TOTAL_VIOLATIONS']++;
					}
					else
					{
						$arResult[$arRes['USER_ID']]['TOTAL_INACTIVE']++;
					}
				}

				$arEntriesMap[$arRes['ID']] = true;
				$arResult[$arRes['USER_ID']]['ENTRIES'][] = $arEntry;
			}
		}

		return $arResult;
	}

	private function _getDepartmentsData($arSections)
	{
		$arResult = array();
		$arChains = array();

		$section_url = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";
		$iblockId = COption::GetOptionInt('intranet', 'iblock_structure', 0);

		$arSectionFilter = array(
			'CHECK_PERMISSIONS' => 'N',
			'IBLOCK_ID' => $iblockId,
			'ID' => array_unique($arSections)
		);

		$dbRes = CIBlockSection::GetList(
			array('LEFT_MARGIN' => 'DESC'),
			$arSectionFilter,
			false,
			array('ID', 'NAME', 'IBLOCK_SECTION_ID', 'UF_HEAD')
		);

		$chain_root = null;
		while ($arRes = $dbRes->Fetch())
		{
			if ($arRes['IBLOCK_SECTION_ID'] <= 0)
				$arRes['TOP_SECTION'] = true;

			$arRes['CHAIN'] = array();
			if (isset($arChains[$arRes['ID']]))
			{
				$arRes['CHAIN'] = $arChains[$arRes['ID']];
			}
			elseif ($arRes['IBLOCK_SECTION_ID'] > 0
					&& isset($arChains[$arRes['IBLOCK_SECTION_ID']]))
			{
				$arRes['CHAIN'] = $arChains[$arRes['IBLOCK_SECTION_ID']];
				$arRes['CHAIN'][] = array(
					'ID' => $arRes['ID'],
					'NAME' => $arRes['NAME'],
					'URL' => str_replace('#ID#', $arRes['ID'], $section_url)
				);
			}
			else
			{
				$db1 = CIBlockSection::GetNavChain($iblockId, $arRes['ID']);
				while ($sect = $db1->Fetch())
				{
					$arRes['CHAIN'][] = array(
						'ID' => $sect['ID'],
						'NAME' => $sect['NAME'],
						'URL' => str_replace('#ID#', $sect['ID'], $section_url)
					);
				}
			}

			if (!isset($arChains[$sect['ID']]))
			{
				$arChains[$sect['ID']] = $arRes['CHAIN'];
			}

			if (null === $chain_root)
				$chain_root = $arRes['CHAIN'][0]['ID'];
			elseif (
				false !== $chain_root
				&& $chain_root != $arRes['CHAIN'][0]['ID']
			)
				$chain_root = false;

			$arResult[$arRes['ID']] = $arRes;
		}

		if ($chain_root)
		{
			foreach ($arResult as &$dpt)
			{
				if (count($dpt['CHAIN']) > 1)
					array_shift($dpt['CHAIN']);
			}
		}

		return $arResult;
	}

	private function _getNavData()
	{
		$res = '';

		if (is_array($this->arAccessUsers['READ']) && count($this->arAccessUsers['READ']) > 0)
		{
			$item_count = CIntranetUtils::GetEmployeesCountForSorting($this->department, 0, $this->bCanReadAll ? false : $this->arAccessUsers['READ']);
			$page_count = intval($item_count/$this->amount)+($item_count%$this->amount>0?1:0);

			$navResult = new CDBResult();
			$navResult->NavNum = 'STRUCTURE';
			$navResult->NavPageSize = $this->amount;
			$navResult->NavRecordCount = $item_count;
			$navResult->NavPageCount = $page_count;
			$navResult->NavPageNomer = $this->page;

			ob_start();
			$GLOBALS['APPLICATION']->IncludeComponent(
					'bitrix:system.pagenavigation',
					'js',
					array(
						'NAV_RESULT' => $navResult,
						'HANDLER' => $this->nav_handler,
					)
			);
			$res = ob_get_contents();
			ob_end_clean();
		}

		return $res;
	}

	private function __getFilter()
	{
		$arFilter = null;

		if ($this->_checkAccess())
		{
			$ts = strtotime(date('Y-m-01', $this->ts));
			$ts_finish = strtotime('+1 month', $ts);

			$date_start = ConvertTimeStamp($ts, 'FULL');
			$date_finish = ConvertTimeStamp($ts_finish, 'FULL');

			$arFilter = array(
				'>DATE_START' => $date_start,
				'<DATE_START' => $date_finish,
				'+<DATE_FINISH' => $date_finish,
				'USER_ACTIVE' => 'Y',
			);

			if ($this->department > 0)
			{
				$arFilter['UF_DEPARTMENT'] = CIntranetUtils::GetIBlockSectionChildren($this->department);
			}
		}

		return $arFilter;
	}

	private function __getUserRow($arRes, $prefix, $arFilter)
	{
		if ($this->department)
		{
			$arRes['UF_DEPARTMENT'] = array_values(array_intersect($arRes['UF_DEPARTMENT'], $arFilter['UF_DEPARTMENT']));
		}

		$res = array(
			'ID' => $arRes[$prefix.'ID'],
			'NAME' => CUser::FormatName(
				CSite::GetNameFormat(false), array(
					'USER_ID' => $arRes[$prefix.'ID'],
					'NAME' => $arRes[$prefix.'NAME'],
					'LAST_NAME' => $arRes[$prefix.'LAST_NAME'],
					'SECOND_NAME' => $arRes[$prefix.'SECOND_NAME'],
					'LOGIN' => $arRes[$prefix.'LOGIN'],
					'EMAIL' => $arRes[$prefix.'EMAIL'],
				),
				true, false
			),
			'DEPARTMENT' => $arRes['UF_DEPARTMENT'][0],
			'URL' => str_replace(
				array('#ID#', '#USER_ID#'),
				$arRes[$prefix.'ID'],
				$this->path_user
			),
			'TOTAL' => 0,
			'TOTAL_DAYS' => 0,
			'TOTAL_VIOLATIONS' => 0,
			'TOTAL_INACTIVE' => 0,
			'SETTINGS' => array(),
			'ENTRIES' => array()
		);

		$TMUSER = new CTimeManUser($arRes[$prefix.'ID']);
		$res['SETTINGS'] = $TMUSER->GetSettings();
		unset($res['SETTINGS']['UF_TM_REPORT_TPL']);

		return $res;
	}
}
?>