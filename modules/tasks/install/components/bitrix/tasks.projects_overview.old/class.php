<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

class CTasksDepartmentsOverviewComponent extends CBitrixComponent
{
	public function executeComponent()
	{
		global $APPLICATION;

		$APPLICATION->SetTitle(GetMessage('TASKS_TITLE_TASKS'));

		if ( ! CModule::IncludeModule('tasks') )
		{
			ShowError(GetMessage('TASKS_MODULE_NOT_FOUND'));
			return 0;
		}

		if ( ! CModule::IncludeModule('socialnetwork') )
			return 0;

		$this->arResult['PROJECTS'] = array();
		$this->processParams();		// prepare arResult

		if ( ! ($this->arResult['LOGGED_IN_USER'] >= 1) )
			return 0;

		$isAccessible = ($this->arParams['USER_ID'] == $this->arResult['LOGGED_IN_USER']);

		if ( ! $isAccessible )
		{
			ShowError(GetMessage('TASKS_PROJECTS_ACCESS_DENIED'));
			return 0;			
		}

		// Get groups where user is member
		$arGroupsIds = array();
		$rsGroupMembers = CSocNetUserToGroup::GetList(
			array(),
			array('USER_ID' => $this->arParams['USER_ID']),
			false,
			false,
			array('GROUP_ID')
		);

		while ($arGroupMembers = $rsGroupMembers->getNext())
			$arGroupsIds[] = (int) $arGroupMembers['GROUP_ID'];

		$arGroupsIds = array_unique(array_filter($arGroupsIds));

		$cntAll = $cntInWork = $cntComplete = 0;	// totals
		if ( ! empty($arGroupsIds) )
		{
			$arCounters = $this->getCounts($arGroupsIds);

			// Get extra data for groups
			$rsGroup = CSocNetGroup::GetList(
				array('NAME' => 'ASC'),
				array(
					'ID'     => $arGroupsIds,
					'ACTIVE' => 'Y',
					'CLOSED' => 'N'
				),
				false,
				false,
				array('ID', 'NAME', 'IMAGE_ID', 'NUMBER_OF_MEMBERS', 'CLOSED')
			);

			while ($arGroup = $rsGroup->getNext())
			{
				$groupId = (int) $arGroup['ID'];
				$arGroupCounters = $arCounters[$groupId];

				// Skip groups without tasks
				if ($arGroupCounters['ALL'] == 0)
					continue;

				$groupPath = CComponentEngine::MakePathFromTemplate(
					$this->arResult['PATH_TO_GROUP'],
					array('group_id' => $groupId)
				);
				$groupTasksPath = CComponentEngine::MakePathFromTemplate(
					$this->arResult['PATH_TO_GROUP_TASKS'],
					array('group_id' => $groupId)
				);

				if (strpos($groupTasksPath, '?') !== false)
					$groupTasksPath .= '&';
				else
					$groupTasksPath .= '?';

				$groupTasksPath .= 'F_CANCEL=Y&F_FILTER_SWITCH_PRESET=';

				$cntAll      += $arGroupCounters['ALL'];
				$cntInWork   += $arGroupCounters['IN_WORK'];
				$cntComplete += $arGroupCounters['COMPLETE'];

				$this->arResult['PROJECTS'][$groupId] = array(
					'ID'                => $groupId,
					'TITLE'             => $arGroup['NAME'],
					'~TITLE'            => $arGroup['~NAME'],
					'IMAGE_ID'          => $arGroup['IMAGE_ID'],
					'COUNTERS'          => $arGroupCounters,
					'NUMBER_OF_MEMBERS' => $arGroup['NUMBER_OF_MEMBERS'],
					'PATHES'            => array(
						'TO_GROUP' => $groupPath,
						'ALL'      => $groupTasksPath . CTaskFilterCtrl::STD_PRESET_ALL_MY_TASKS,
						'IN_WORK'  => $groupTasksPath . CTaskFilterCtrl::STD_PRESET_ACTIVE_MY_TASKS,
						'COMPLETE' => $groupTasksPath . CTaskFilterCtrl::STD_PRESET_COMPLETED_MY_TASKS
					),
					'MEMBERS' => array()	// init later
				);
			}

			// Get members of groups
			$rsGroupMembers = CSocNetUserToGroup::GetList(
				array(),
				array('GROUP_ID' => $arGroupsIds),
				false,
				false,
				array(
					'GROUP_ID', 'USER_ID', 'ROLE', 'GROUP_OWNER_ID', 
					'USER_LAST_NAME', 'USER_NAME', 'USER_SECOND_NAME', 
					'USER_PERSONAL_PHOTO', 'USER_LOGIN', 'USER_PERSONAL_PHOTO',
					'USER_WORK_POSITION'
				)
			);

			while ($arGroupMember = $rsGroupMembers->getNext())
			{
				$groupId = (int) $arGroupMember['GROUP_ID'];

				if ( ! isset($this->arResult['PROJECTS'][$groupId]) )
					continue;

				$memberId = (int) $arGroupMember['USER_ID'];
				$bGroupOwner = ($memberId == $arGroupMember['GROUP_OWNER_ID']);
				$bGroupModerator = ($arGroupMember['ROLE'] == SONET_ROLES_MODERATOR);

				$this->arResult['PROJECTS'][$groupId]['MEMBERS'][] = array(
					'ID'                 =>  $memberId,
					'IS_GROUP_OWNER'     => ($bGroupOwner ? 'Y' : 'N'),
					'IS_GROUP_MODERATOR' => ($bGroupModerator ? 'Y' : 'N'),
					'PHOTO_ID'           =>  $arGroupMember['USER_PERSONAL_PHOTO'],
					'USER_NAME'          =>  $arGroupMember['USER_NAME'],
					'~USER_NAME'         =>  $arGroupMember['~USER_NAME'],
					'USER_LAST_NAME'     =>  $arGroupMember['USER_LAST_NAME'],
					'~USER_LAST_NAME'    =>  $arGroupMember['~USER_LAST_NAME'],
					'USER_SECOND_NAME'   =>  $arGroupMember['USER_SECOND_NAME'],
					'~USER_SECOND_NAME'  =>  $arGroupMember['~USER_SECOND_NAME'],
					'USER_LOGIN'         =>  $arGroupMember['USER_LOGIN'],
					'~USER_LOGIN'        =>  $arGroupMember['~USER_LOGIN'],
					'WORK_POSITION'      => (string) $arGroupMember['USER_WORK_POSITION'],
					'~WORK_POSITION'     => (string) $arGroupMember['~USER_WORK_POSITION'],
					'HREF'               => CComponentEngine::MakePathFromTemplate(
						$this->arResult['PATH_TO_USER'],
						array('user_id' => $memberId)
					),
					'USER_GENDER'        => $arGroupMember['USER_PERSONAL_GENDER'],
					'FORMATTED_NAME'     => $f = $this->getFormattedUserName(
						$memberId,
						$arGroupMember['~USER_NAME'],
						$arGroupMember['~USER_SECOND_NAME'],
						$arGroupMember['~USER_LAST_NAME'],
						$arGroupMember['~USER_LOGIN']
					)
				);
			}
		}

		$this->arResult['TOTALS'] = array(
			'ALL'      => $cntAll,
			'IN_WORK'  => $cntInWork,
			'COMPLETE' => $cntComplete
		);

		$this->IncludeComponentTemplate();
	}


	public function getUserPictureSrc($photoId, $gender = '?', $width = 100, $height = 100)
	{
		static $arCache = array();

		$photoId = (int) $photoId;
		$key = $photoId . " $width $height";

		if (array_key_exists($key, $arCache))
			$src = $arCache[$key];
		else
		{
			$src = false;

			if ($photoId > 0)
			{
				$imageFile = CFile::GetFileArray($photoId);
				if ($imageFile !== false)
				{
					$arFileTmp = CFile::ResizeImageGet(
						$imageFile,
						array("width" => $width, "height" => $height),
						BX_RESIZE_IMAGE_EXACT,
						false
					);
					$src = $arFileTmp["src"];
				}

				$arCache[$key] = $src;
			}
		}

		return ($src);
	}


	private function getFormattedUserName($id, $name, $secondName, $lastName, $login)
	{
		static $arCache = array();

		$id = (int) $id;

		if (array_key_exists($id, $arCache))
			$formattedName = $arCache[$id];
		else
		{
			$formattedName = CUser::FormatName(
				$this->arResult['NAME_TEMPLATE'], 
				array(
					'NAME'        => $name,
					'LAST_NAME'   => $lastName,
					'SECOND_NAME' => $secondName,
					'LOGIN'       => $login
				),
				$bUseLogin = true,
				$bHtmlSpecialChars = true
			);

			$arCache[$id] = $formattedName;
		}

		return ($formattedName);
	}

	/**
	 * @param $groupPath
	 * @param $imageId
	 * @param int $imageSize
	 * @return array
	 * @deprecated This function is a design-related one, it should not be here, but left for compatibility
	 */
	public function initGroupImage($groupPath, $imageId, $imageSize = 100)
	{
		$defaultGroupImageId = null;

		if ($imageId <= 0)
		{
			if ($defaultGroupImageId === null)
				$defaultGroupImageId = COption::GetOptionInt("socialnetwork", "default_group_picture", false, SITE_ID);

			$imageId = $defaultGroupImageId;
		}

		$arImage = CSocNetTools::InitImage($imageId, $imageSize, "/bitrix/images/socialnetwork/nopic_group_100.gif", $imageSize, $groupPath, true);

		return ($arImage);
	}


	private function getCounts($arGroupsIds)
	{
		$arCounters = array();

		if (empty($arGroupsIds))
			return;

		foreach ($arGroupsIds as $groupId)
		{
			$arCounters[$groupId] = array(
				'ALL'      => 0,
				'IN_WORK'  => 0,
				'COMPLETE' => 0
			);
		}

		$oFilter = CTaskFilterCtrl::getInstance(
			$this->arParams['USER_ID'],
			true	// $bGroupMode
		);

		$arFilterAll      = $oFilter->getFilterPresetConditionById(CTaskFilterCtrl::STD_PRESET_ALL_MY_TASKS);
		$arFilterInWork   = $oFilter->getFilterPresetConditionById(CTaskFilterCtrl::STD_PRESET_ACTIVE_MY_TASKS);
		$arFilterComplete = $oFilter->getFilterPresetConditionById(CTaskFilterCtrl::STD_PRESET_COMPLETED_MY_TASKS);
		$arFilterInWorkExpired = $arFilterInWork;
		$arFilterInWorkExpired['<DEADLINE'] = ConvertTimeStamp(time() + CTasksTools::getTimeZoneOffset(), 'FULL');

		$arFilterAll['GROUP_ID']           = $arGroupsIds;
		$arFilterInWork['GROUP_ID']        = $arGroupsIds;
		$arFilterComplete['GROUP_ID']      = $arGroupsIds;
		$arFilterInWorkExpired['GROUP_ID'] = $arGroupsIds;

		$arMap = array(
			'ALL'      => &$arFilterAll,
			'IN_WORK'  => &$arFilterInWork,
			'COMPLETE' => &$arFilterComplete,
			'EXPIRED'  => &$arFilterInWorkExpired
		);

		foreach ($arMap as $key => &$arFilter)
		{
			$rs = CTasks::GetCount(
				$arFilter,
				array(
					'bSkipUserFields'    => true,
					'bSkipExtraTables'   => true,
					'bSkipJoinTblViewed' => false
				),
				array('GROUP_ID')		// group by
			);

			while ($ar = $rs->fetch())
			{
				$groupId = (int) $ar['GROUP_ID'];

				if ($groupId)
					$arCounters[$groupId][$key] = (int) $ar['CNT'];
			}
		}
		unset($arFilter);

		return ($arCounters);
	}


	private function processParams()
	{
		$this->arResult['LOGGED_IN_USER'] = false;
		$user = \Bitrix\Tasks\Util\User::getId();
		if($user)
		{
			$this->arResult['LOGGED_IN_USER'] = $user;
		}

		if (isset($this->arParams['NAME_TEMPLATE']))
			$this->arResult['NAME_TEMPLATE'] = $this->arParams['NAME_TEMPLATE'];
		else
			$this->arResult['NAME_TEMPLATE'] = CSite::GetNameFormat(false);

		$this->arResult['PATH_TO_USER']        = $this->arParams['PATH_TO_USER'];
		$this->arResult['PATH_TO_GROUP']       = $this->arParams['PATH_TO_GROUP'];
		$this->arResult['PATH_TO_USER_TASKS']  = $this->arParams['PATH_TO_USER_TASKS'];
		$this->arResult['PATH_TO_GROUP_TASKS'] = $this->arParams['PATH_TO_GROUP_TASKS'];
	}
}
