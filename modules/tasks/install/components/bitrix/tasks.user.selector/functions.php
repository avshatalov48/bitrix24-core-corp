<?php
if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
function TasksEmployeeDrawStructure($arStructure, $arSections, $key, $name)
{
	foreach ($arStructure[$key] as $ID)
	{
		$arRes = $arSections[$ID];

		echo '<div class="company-department'.($key == 0 ? ' company-department-first' : '').'">';
		echo '<span class="company-department-inner" onclick="O_'.$name.'.load(\''.$ID.'\')" id="'.$name.'_employee_section_'.$ID.'"><div class="company-department-arrow"></div><div class="company-department-text">'.htmlspecialcharsbx($arRes['NAME']).'</div></span>';
		echo '</div>';

		echo '<div class="company-department-children" id="'.$name.'_children_'.$arRes['ID'].'">';
		if (is_array($arStructure[$ID]))
			TasksEmployeeDrawStructure($arStructure, $arSections, $ID, $name);

		echo '<div class="company-department-employees" id="'.$name.'_employees_'.$ID.'"><span class="company-department-employees-loading">'.GetMessage("TASKS_EMP_WAIT").'</span></div>';
		echo '</div>';

	}
}

if ( ! function_exists('FilterViewableUsers'))
{
	function FilterViewableUsers($var)
	{
		if (!CModule::IncludeModule("extranet") || CExtranet::IsIntranetUser() || CExtranet::IsProfileViewableByID($var["ID"], $GLOBALS["GROUP_SITE_ID"]))
		{
			return true;
		}
		else
		{
			return false;
		}
	}	
}


function TasksGetDepartmentUsers($SECTION_ID, $SITE_ID, $arSubDeps, $arManagers, $ynShowInactiveUsers, $nameTemplate)
{
	static $arCacheUsers = array();
	$cacheKey = ((string) $SECTION_ID) 
		. '|' . ((string) $SITE_ID)
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
		{
			$filterACTIVE = '';
		}
		else
		{
			$arFilter['CONFIRM_CODE'] = false;
		}

		$arFilter['ACTIVE'] = $filterACTIVE;

		// Prevent using users, that doesn't activate it's account
		// http://jabber.bx/view.php?id=29118
		if (IsModuleInstalled('bitrix24'))
			$arFilter['!LAST_LOGIN'] = false;

		if($SECTION_ID == "extranet")
		{
			$arFilter['GROUPS_ID'] = array(COption::GetOptionInt("extranet", "extranet_group", ""));
		//	$arFilter['UF_DEPARTMENT'] = false;
		}
		else
		{
			$arFilter['UF_DEPARTMENT'] = $SECTION_ID;
		}

		$arUsers = array();

		if ($SECTION_ID != "extranet")
		{
			if(CModule::IncludeModule('iblock'))
			{
				$dbRes = CIBlockSection::GetList(
					array('ID' => 'ASC'),
					array('ID' => $SECTION_ID, 'IBLOCK_ID' => COption::GetOptionInt('intranet', 'iblock_structure')),
					false,
					array('UF_HEAD')
				);
				if (($arSection = $dbRes->Fetch()) && $arSection['UF_HEAD'] > 0)
				{
					$dbUsers = CUser::GetList(
						$sort_by  = 'last_name',
						$sort_dir = 'asc',
						array(
							'ID'     => $arSection['UF_HEAD'],
							'ACTIVE' => $filterACTIVE),
						array('SELECT' => $arCUserRequestedFields)
					);

					if ($arRes = $dbUsers->Fetch())
					{
						$arFilter['!ID'] = $arRes['ID'];

						$arPhoto = array('IMG' => '');

						if (!$arRes['PERSONAL_PHOTO'])
						{
							switch ($arRes['PERSONAL_GENDER'])
							{
								case "M":
									$suffix = "male";
									break;
								case "F":
									$suffix = "female";
									break;
								default:
									$suffix = "unknown";
							}
							$arRes['PERSONAL_PHOTO'] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, $SITE_ID);
						}

						if ($arRes['PERSONAL_PHOTO'] > 0)
							$arPhoto = CIntranetUtils::InitImage($arRes['PERSONAL_PHOTO'], 30, 0, BX_RESIZE_IMAGE_EXACT);

						$arUsers[] = array(
							'ID'            => $arRes['ID'],
							'NAME'          => CUser::FormatName($nameTemplate, $arRes),
							'LOGIN'         => $arRes['LOGIN'],
							'EMAIL'         => $arRes['EMAIL'],
							'WORK_POSITION' => htmlspecialcharsBack($arRes['WORK_POSITION'] ? $arRes['WORK_POSITION'] : $arRes['PERSONAL_PROFESSION']),
							'PHOTO'         => $arPhoto['CACHE']['src'],
							'HEAD'          => true,
							'UF_DEPARTMENT' => $arRes['UF_DEPARTMENT'],
							'SUBORDINATE'   => is_array($arSubDeps) && is_array($arRes['UF_DEPARTMENT']) && array_intersect($arRes['UF_DEPARTMENT'], $arSubDeps) ? 'Y' : 'N',
							'SUPERORDINATE' => in_array($arRes["ID"], $arManagers) ? 'Y' : 'N'
						);
					}
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
			$arPhoto = array('IMG' => '');

			if (!$arRes['PERSONAL_PHOTO'])
			{
				switch ($arRes['PERSONAL_GENDER'])
				{
					case "M":
						$suffix = "male";
						break;
					case "F":
						$suffix = "female";
						break;
					default:
						$suffix = "unknown";
				}
				$arRes['PERSONAL_PHOTO'] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, $SITE_ID);
			}

			if ($arRes['PERSONAL_PHOTO'] > 0)
			{
				$arPhoto = CIntranetUtils::InitImage($arRes['PERSONAL_PHOTO'], 30, 0, BX_RESIZE_IMAGE_EXACT);
			}

			$arUsers[] = array(
				'ID' => $arRes['ID'],
				'NAME' => CUser::FormatName($nameTemplate, $arRes, true, false),
				'LOGIN' => $arRes['LOGIN'],
				'EMAIL' => $arRes['EMAIL'],
				'WORK_POSITION' => htmlspecialcharsBack($arRes['WORK_POSITION'] ? $arRes['WORK_POSITION'] : $arRes['PERSONAL_PROFESSION']),
				'PHOTO' => $arPhoto['CACHE']['src'],
				'HEAD' => false,
				'UF_DEPARTMENT' => $arRes['UF_DEPARTMENT'],
				'SUBORDINATE' => is_array($arSubDeps) && is_array($arRes['UF_DEPARTMENT']) && array_intersect($arRes['UF_DEPARTMENT'], $arSubDeps) ? 'Y' : 'N',
				'SUPERORDINATE' => in_array($arRes["ID"], $arManagers) ? 'Y' : 'N'
			);
		}
		$arCacheUsers[$cacheKey] = array_values(array_filter($arUsers, "FilterViewableUsers"));
	}

	return ($arCacheUsers[$cacheKey]);
}
