<?php
if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

if(!function_exists('InEmployeeDrawStructure'))
{
	function InEmployeeDrawStructure($arStructure, $arSections, $key, $name)
	{
		CIntranetUserSelectorHelper::drawEmployeeStructure($arStructure, $arSections, $key, $name);
	}
}

if(!function_exists('FilterViewableUsers'))
{
	function FilterViewableUsers($var)
	{
		return CIntranetUserSelectorHelper::filterViewableUsers($var);
	}
}

if(class_exists('CIntranetUserSelectorHelper'))
{
	return false;
}
class CIntranetUserSelectorHelper
{
	public static function drawEmployeeStructure($arStructure, $arSections, $key, $name, $bSelectSection = false)
	{
		foreach ($arStructure[$key] as $id)
		{
			$arRes = $arSections[$id];

			echo '<div class="company-department'.($key == 0 ? ' company-department-first' : '').'">';
			if ($bSelectSection)
			{
				echo '<span class="company-department-inner" id="'.$name.'_employee_section_'.$id.'">';
				echo '<div class="company-department-arrow" onclick="O_'.$name.'.load(\''.$id.'\', false, false, true)"></div>';
				echo '<div  data-section-id="'.$id.'" class="company-department-text" onclick="O_'.$name.'.selectSection(\''.$name.'_employee_section_'.$id.'\')">'.htmlspecialcharsbx($arRes['NAME']).'</div>';
				echo '</span>';
			}
			else
			{
				echo '<span class="company-department-inner" onclick="O_'.$name.'.load(\''.$id.'\')" id="'.$name.'_employee_section_'.$id.'"><div class="company-department-arrow"></div><div class="company-department-text">'.htmlspecialcharsbx($arRes['NAME']).'</div></span>';
			}
			echo '</div>';

			echo '<div class="company-department-children" id="'.$name.'_children_'.$arRes['ID'].'">';
			if (is_array($arStructure[$id]))
				static::drawEmployeeStructure($arStructure, $arSections, $id, $name, $bSelectSection);
			echo '<div class="company-department-employees" id="'.$name.'_employees_'.$id.'"><span class="company-department-employees-loading">'.GetMessage("INTRANET_EMP_WAIT").'</span></div>';
			echo '</div>';
		}
	}

	public static function drawGroup($groups, $jsObjectName)
	{
		foreach ($groups as $id => $group)
		{
			echo
				'<div class="company-department">
					<span id="' . $jsObjectName . '_group_section_' . $group['GROUP_ID'] . '" class="company-department-inner" onclick="O_' . $jsObjectName .  ' .loadGroup(\''.$group['GROUP_ID'].'\')"><div class="company-department-arrow"></div><div class="company-department-text">' . $group['GROUP_NAME'] .'</div></span>
					<div class="company-department-children" id="'.$jsObjectName.'_gchildren_'.$group['GROUP_ID'].'">
						<div class="company-department-employees" id="'.$jsObjectName.'_gemployees_'.$group['GROUP_ID'].'">
							<span class="company-department-employees-loading">'.GetMessage("INTRANET_EMP_WAIT").'</span>
						</div>
					</div>
				</div>
			';
		}
	}

	public static function filterViewableUsers($var)
	{
		return (!CModule::IncludeModule("extranet")	|| CExtranet::IsIntranetUser() || CExtranet::isProfileViewableByID($var["ID"], $GLOBALS["GROUP_SITE_ID"]));
	}

	public static function getUserGroups($userId, $force = false)
	{
		static $cache = array();
		if(!$force && isset($cache[$userId]))
		{
			return $cache[$userId];
		}
		$cache[$userId] = array();
		if(!CModule::IncludeModule('socialnetwork'))
		{
			return $cache[$userId];
		}
		$userGroupFilter = array(
			'USER_ID' => (int)$userId,
			'<=ROLE' => SONET_ROLES_USER,
		);
		$dbUserGroups = CSocNetUserToGroup::GetList(array('GROUP_NAME' => 'ASC'), $userGroupFilter, false, false, array('GROUP_NAME', 'GROUP_ID'));
		while($row = $dbUserGroups->GetNext())
		{
			$cache[$userId][] = $row;
		}

		return $cache[$userId];
	}

	public static function getDepartmentUsers($sectionId, $siteId, $arSubDeps, $arManagers, $ynShowInactiveUsers, $nameTemplate)
	{
		static $arCacheUsers = array();
		$cacheKey = ((string) $sectionId)
			. '|' . ((string) $siteId)
			. '|' . serialize($arSubDeps)
			. '|' . serialize($arManagers)
			. '|' . $ynShowInactiveUsers;

		static $arCUserRequestedFields = array(
			'ID',
			'PERSONAL_PHOTO',
			'PERSONAL_GENDER',
			'LOGIN',
			'EMAIL',
			'WORK_POSITION',
			'PERSONAL_PROFESSION',
			'UF_DEPARTMENT',
			'NAME',
			'LAST_NAME',
			'SECOND_NAME',
			'EMAIL'
		);

		// Precache data, if need
		if ( ! array_key_exists($cacheKey, $arCacheUsers) )
		{
			$arFilter = array();

			$filterACTIVE = 'Y';
			if ($ynShowInactiveUsers === 'Y')
				$filterACTIVE = '';

			$arFilter['ACTIVE'] = $filterACTIVE;

			// Prevent using users, that doesn't activate it's account
			// http://jabber.bx/view.php?id=29118
			if (IsModuleInstalled('bitrix24'))
			{
				$arFilter['CONFIRM_CODE'] = false;
			}

			if($sectionId == "extranet")
			{
				$arFilter['GROUPS_ID'] = array(COption::GetOptionInt("extranet", "extranet_group", ""));
				//	$arFilter['UF_DEPARTMENT'] = false;
			}
			else
			{
				$arFilter['UF_DEPARTMENT'] = $sectionId;
			}

			$arUsers = array();
			if ($sectionId != "extranet")
			{
				$ufHead = CIntranetUtils::getDepartmentManagerID($sectionId);
				if ($ufHead > 0)
				{
					$arHeadFilter = array('ID' => $ufHead, 'ACTIVE' => $filterACTIVE);
					if (IsModuleInstalled('bitrix24'))
						$arHeadFilter['CONFIRM_CODE'] = false;

					//fetch only one manager by Section
					$dbUsers = CUser::GetList(
						$sort_by  = 'last_name', $sort_dir = 'asc',
						$arHeadFilter,
						array('SELECT' => $arCUserRequestedFields)
					);

					if ($arRes = $dbUsers->GetNext())
					{
						$arFilter['!ID'] = $arRes['ID'];
						$arUsers[] = array(
							'ID'            => $arRes['ID'],
							'NAME'          => CUser::FormatName($nameTemplate, array(
								"NAME" => $arRes["~NAME"],
								"LAST_NAME" => $arRes["~LAST_NAME"],
								"LOGIN" => $arRes["~LOGIN"],
								"SECOND_NAME" => $arRes["~SECOND_NAME"]
							), true, false),
							'LOGIN'         => $arRes['LOGIN'],
							'EMAIL'         => $arRes['EMAIL'],
							'WORK_POSITION' => $arRes['~WORK_POSITION'] ? $arRes['~WORK_POSITION'] : $arRes['~PERSONAL_PROFESSION'],
							'PHOTO'         => (string)CIntranetUtils::createAvatar($arRes, array(), $siteId),
							'HEAD'          => true,
							'UF_DEPARTMENT' => $arRes['UF_DEPARTMENT'],
							'SUBORDINATE'   => is_array($arSubDeps) && is_array($arRes['UF_DEPARTMENT']) && array_intersect($arRes['UF_DEPARTMENT'], $arSubDeps) ? 'Y' : 'N',
							'SUPERORDINATE' => in_array($arRes["ID"], $arManagers) ? 'Y' : 'N'
						);
					}
				}
			}

			$dbRes = CUser::GetList(
				$by = 'last_name',
				$order = 'asc',
				$arFilter,
				array('SELECT' => $arCUserRequestedFields)
			);
			while ($arRes = $dbRes->GetNext())
			{
				$arUsers[] = array(
					'ID'            => $arRes['ID'],
					'NAME'          => CUser::FormatName($nameTemplate, array(
						"NAME" => $arRes["~NAME"],
						"LAST_NAME" => $arRes["~LAST_NAME"],
						"LOGIN" => $arRes["~LOGIN"],
						"SECOND_NAME" => $arRes["~SECOND_NAME"]
					), true, false),
					'LOGIN'         => $arRes['LOGIN'],
					'EMAIL'         => $arRes['EMAIL'],
					'WORK_POSITION' => $arRes['~WORK_POSITION'] ? $arRes['~WORK_POSITION'] : $arRes['~PERSONAL_PROFESSION'],
					'PHOTO'         => (string)CIntranetUtils::createAvatar($arRes, array(), $siteId),
					'HEAD'          => false,
					'UF_DEPARTMENT' => $arRes['UF_DEPARTMENT'],
					'SUBORDINATE'   => is_array($arSubDeps) && is_array($arRes['UF_DEPARTMENT']) && array_intersect($arRes['UF_DEPARTMENT'], $arSubDeps) ? 'Y' : 'N',
					'SUPERORDINATE' => in_array($arRes["ID"], $arManagers) ? 'Y' : 'N'
				);
			}
			$arCacheUsers[$cacheKey] = array_values(array_filter($arUsers, array('CIntranetUserSelectorHelper', 'filterViewableUsers')));
		}

		return ($arCacheUsers[$cacheKey]);
	}

	public static function getLastSelectedUsers($arManagers, $bSubordinateOnly = false, $nameTemplate = '', $siteId = SITE_ID)
	{
		/** @var CAllUser $USER */
		static $arLastUsers;
		global $USER, $arParams;

		$cacheKey = md5(serialize($arManagers) . (string)$bSubordinateOnly . '|' . $nameTemplate . '|' . (string)$siteId);
		if (!isset($arLastUsers[$cacheKey]))
		{
			$arSubDeps = CIntranetUtils::getSubordinateDepartments($USER->GetID(), true);
			if (!class_exists('CUserOptions'))
			{
				include_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/classes/" . $GLOBALS['DBType'] . "/favorites.php");
			}

			$arLastSelected = CUserOptions::GetOption("intranet", "user_search", array());
			if (is_array($arLastSelected) && strlen($arLastSelected['last_selected']) > 0)
			{
				$arLastSelected = array_unique(explode(',', $arLastSelected['last_selected']));
			}
			else
			{
				$arLastSelected = false;
			}

			if (is_array($arLastSelected))
			{
				$currentUser = array_search($USER->getID(), $arLastSelected);
				if ($currentUser !== false)
				{
					unset($arLastSelected[$currentUser]);
				}
				array_unshift($arLastSelected, $USER->getID());
			}
			elseif($USER->getID())
			{
				$arLastSelected = array($USER->getID());
			}

			$arFilter = array('ACTIVE' => 'Y');
			if ($bSubordinateOnly)
			{
				$arFilter["UF_DEPARTMENT"] = $arSubDeps;
			}
			else
			{
				$arFilter['!UF_DEPARTMENT'] = false;
			}

			// Prevent using users, that doesn't activate it's account
			// http://jabber.bx/view.php?id=29118
			if (IsModuleInstalled('bitrix24'))
			{
				$arFilter['CONFIRM_CODE'] = false;
			}

			$arFilter['ID'] = is_array($arLastSelected) ? implode('|', array_slice($arLastSelected, 0, 10)) : '-1';
			$dbRes = CUser::GetList($by = 'last_name', $order = 'asc', $arFilter, array('SELECT' => array('UF_DEPARTMENT')));
			$arLastUsers[$cacheKey] = array();
			while ($arRes = $dbRes->GetNext())
			{
				$arLastUsers[$cacheKey][$arRes['ID']] = array(
					'ID' => $arRes['ID'],
					'NAME' => CUser::FormatName(empty($nameTemplate) ? CSite::GetNameFormat() : $nameTemplate, $arRes, true, false),
					'~NAME' => CUser::FormatName(empty($nameTemplate) ? CSite::GetNameFormat() : $nameTemplate, array(
							"NAME" => $arRes["~NAME"],
							"LAST_NAME" => $arRes["~LAST_NAME"],
							"LOGIN" => $arRes["~LOGIN"],
							"SECOND_NAME" => $arRes["~SECOND_NAME"],
						), true, false),
					'LOGIN' => $arRes['LOGIN'],
					'EMAIL' => $arRes['EMAIL'],
					'WORK_POSITION' => $arRes['WORK_POSITION'] ? $arRes['WORK_POSITION'] : $arRes['PERSONAL_PROFESSION'],
					'~WORK_POSITION' => $arRes['~WORK_POSITION'] ? $arRes['~WORK_POSITION'] : $arRes['~PERSONAL_PROFESSION'],
					'PHOTO' => (string)CIntranetUtils::createAvatar($arRes, array(), $siteId),
					'HEAD' => false,
					'SUBORDINATE' => is_array($arSubDeps) && is_array($arRes['UF_DEPARTMENT']) && array_intersect($arRes['UF_DEPARTMENT'], $arSubDeps) ? 'Y' : 'N',
					'SUPERORDINATE' => in_array($arRes["ID"], $arManagers) ? 'Y' : 'N',
				);
			}

			$listOrder = array_flip(array_values($arLastSelected));
			uksort($arLastUsers[$cacheKey], function ($a, $b) use ($listOrder)
			{
				return $listOrder[$a]-$listOrder[$b];
			});
		}

		return $arLastUsers[$cacheKey];
	}

	public static function getDepartmentManagersId($arDepartments, $skipUserId = false, $bRecursive = false)
	{
		if (!is_array($arDepartments) || empty($arDepartments))
		{
			return array();
		}

		static $structure = array();
		if(!$structure)
		{
			$structure  = CIntranetUtils::getStructure();
		}
		$arManagers = array();
		foreach ($arDepartments as $sectionId)
		{
			$arSection = $structure['DATA'][$sectionId];
			if ($arSection['UF_HEAD'] && $arSection['UF_HEAD'] != $skipUserId)
			{
				$arManagers[$arSection['UF_HEAD']] = array(
					'ID'  => $arSection['UF_HEAD'],
					'~ID' => $arSection['UF_HEAD'],
				);
			}

			if ($arSection['UF_HEAD'] && $bRecursive && $arSection['IBLOCK_SECTION_ID'])
			{
				$ar         = static::getDepartmentManagersId(array($arSection['IBLOCK_SECTION_ID']), $skipUserId, $bRecursive);
				$arManagers = $arManagers + $ar;
			}
		}

		return $arManagers;
	}

	public static function getExtranetUsers($id)
	{
		static $cache = array();
		static $groupId = null;

		if(method_exists('CExtranet', 'GetExtranetUserGroupID'))
		{
			if($groupId === null)
			{
				$groupId = CExtranet::GetExtranetUserGroupID();
			}
			if ($groupId !== false)
			{
				$filterExtranetUsers = array(
					'ID'        => $id,
					'GROUPS_ID' => (array)(int)$groupId,
				);

				// Prevent using users, that doesn't activate it's account
				// http://jabber.bx/view.php?id=29118
				if (IsModuleInstalled('bitrix24'))
				{
					$filterExtranetUsers['CONFIRM_CODE'] = false;
				}

				$cacheKey = md5(serialize($filterExtranetUsers));
				if(!array_key_exists($cacheKey, $cache))
				{
					$cache[$cacheKey] = array();
					$dbRes = CUser::GetList($by = 'last_name', $order = 'asc',
						$filterExtranetUsers,
						array('SELECT' => array('UF_DEPARTMENT'))
					);
					while ($row = $dbRes->getNext())
					{
						$cache[$cacheKey][] = $row;
					}
				}

				return $cache[$cacheKey];
			}
		}

		return array();
	}
}
