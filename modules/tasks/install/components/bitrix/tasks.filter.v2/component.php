<?php
// DEPRECATED! use tasks.interface.filter
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule("tasks"))
{
	ShowError(GetMessage("TASKS_MODULE_NOT_FOUND"));
	return;
}

if ($arParams['USER_ID'] < 1)
	return;

if (
	isset($arParams['LOAD_TEMPLATE_INSTANTLY'])
	&& (
		($arParams['LOAD_TEMPLATE_INSTANTLY'] === 'Y')
		|| ($arParams['LOAD_TEMPLATE_INSTANTLY'] === true)
	)
)
{
	$this->IncludeComponentTemplate();
	return;
}

$bUseRoleFilter = true;

if (
	isset($arParams['USE_ROLE_FILTER'])
	&& ($arParams['USE_ROLE_FILTER'] === 'N')
)
{
	$bUseRoleFilter = false;
}

$arParams['GROUP_ID'] = intval($arParams['GROUP_ID']);
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$bSkipCounter = false;
if (isset($arParams['HIDE_COUNTERS']))
{
	if ($arParams['HIDE_COUNTERS'] === 'Y')
		$bSkipCounter = true;
}
$arResult['HIDE_COUNTERS'] = $bSkipCounter;

$arResult["TASK_TYPE"] = $taskType = ($arParams["GROUP_ID"] > 0 ? "group" : "user");
$arResult['LOGGED_IN_USER'] = (int) \Bitrix\Tasks\Util\User::getId();

if ($bUseRoleFilter)
{
	$arResult['USE_ROLE_FILTER']      = 'Y';

	$arResult['SELECTED_PRESET_ID']   = null;
	$arResult['SELECTED_PRESET_NAME'] = null;

	$oListState = CTaskListState::getInstance($arParams['USER_ID']);
	$viewState = $oListState->getState();

	$arResult['COUNTS'] = array();

	$arCountsAll = array();
	$obCache = new CPHPCache();
	$lifeTime = CTasksTools::CACHE_TTL_UNLIM;
	$cacheID = md5($arResult['LOGGED_IN_USER'] . "user" . $arParams["USER_ID"]);
	$cacheDir = "/tasks/filter_tt_v2roles_"
		. '/' . substr($cacheID, -4, 2)
		. '/' . substr($cacheID, -2)
		. '/' . $cacheID;
	$bNeedCacheData = false;
	if (defined('BX_COMP_MANAGED_CACHE') && $obCache->InitCache($lifeTime, $cacheID, $cacheDir))
		$arCountsAll = $obCache->GetVars();
	else
		$arCountsAll = array();

	$arResult['ROLES_LIST'] = array();
	$counter = \Bitrix\Tasks\Internals\Counter::getInstance($arParams['USER_ID']);

	foreach ($viewState['ROLES'] as $roleCodename => $arRoleData)
	{
		if ( ! isset($arCountsAll[$arRoleData['ID']]) )
		{
			if (
				($arRoleData['ID'] == CTaskListState::VIEW_ROLE_AUDITOR)
				|| ($arRoleData['ID'] == CTaskListState::VIEW_ROLE_ACCOMPLICE)
			)
			{
				$rs = CTasks::GetCount(
					CTaskListCtrl::getFilterFor(
						$arParams['USER_ID'],
						$arRoleData['ID'],
						CTaskListState::VIEW_TASK_CATEGORY_IN_PROGRESS
					),
					array(
						'bSkipUserFields'       => true,
						'bSkipExtraTables'      => true,
						'bSkipJoinTblViewed'    => true,
						'bNeedJoinMembersTable' => true,

						'bUseRightsCheck'       => false,
					)
				);

				if ($arTmp = $rs->fetch())
					$arCountsAll[$arRoleData['ID']] = (int) $arTmp['CNT'];
				else
					$arCountsAll[$arRoleData['ID']] = 0;
			}
			else
			{
				$arCountsAll[$arRoleData['ID']] = CTasks::GetCountInt(
					CTaskListCtrl::getFilterFor(
						$arParams['USER_ID'],
						$arRoleData['ID'],
						CTaskListState::VIEW_TASK_CATEGORY_IN_PROGRESS
					),
					array(
						'bSkipUserFields'    => true,
						'bSkipExtraTables'   => true,
						'bSkipJoinTblViewed' => true,

						'bUseRightsCheck'    => false,
					)
				);
			}
			$bNeedCacheData = true;
		}
		$arRoleData['CNT_ALL'] = $arCountsAll[$arRoleData['ID']];

		$counters = $counter->getCounters($roleCodename);
		$arRoleData['CNT_NOTIFY'] = $counters['total']['counter'];
		
		$arRoleData['HREF'] = $arParams['PATH_TO_TASKS'] . '?F_CANCEL=Y&F_STATE=sR' . base_convert($arRoleData['ID'], 10, 32);
		$arResult["ROLES_LIST"][$roleCodename] = $arRoleData;
	}

	if ($bNeedCacheData && defined('BX_COMP_MANAGED_CACHE') && $obCache->StartDataCache())
	{
		global $CACHE_MANAGER;
		$CACHE_MANAGER->StartTagCache($cacheDir);
		$CACHE_MANAGER->RegisterTag("tasks_user_" . $arResult['LOGGED_IN_USER']);
		$CACHE_MANAGER->EndTagCache();
		$obCache->EndDataCache($arCountsAll);
	}
}
else
{
	$arResult['USE_ROLE_FILTER'] = 'N';

	$arResult["ADVANCED_STATUSES"] = array(
		array("TITLE" => GetMessage("TASKS_FILTER_ALL"), "FILTER" => array()),
		array(
			"TITLE" => GetMessage("TASKS_FILTER_ACTIVE"),
			"FILTER" => array("STATUS" => array(
				CTasks::METASTATE_VIRGIN_NEW,
				CTasks::METASTATE_EXPIRED,
				CTasks::STATE_NEW,
				CTasks::STATE_PENDING,
				CTasks::STATE_IN_PROGRESS
		))),
		array(
			"TITLE" => GetMessage("TASKS_FILTER_NEW"),
			"FILTER" => array("STATUS" => array(
				CTasks::METASTATE_VIRGIN_NEW,
				CTasks::STATE_NEW
		))),
		array(
			"TITLE" => GetMessage("TASKS_FILTER_IN_CONTROL"),
			"FILTER" => array("STATUS" => array(
				CTasks::STATE_SUPPOSEDLY_COMPLETED,
				CTasks::STATE_DECLINED
		))),
		array("TITLE" => GetMessage("TASKS_FILTER_IN_PROGRESS"), "FILTER" => array("STATUS" => CTasks::STATE_IN_PROGRESS)),
		array("TITLE" => GetMessage("TASKS_FILTER_ACCEPTED"), "FILTER" => array("STATUS" => CTasks::STATE_PENDING)),
		array("TITLE" => GetMessage("TASKS_FILTER_OVERDUE"), "FILTER" => array("STATUS" => CTasks::METASTATE_EXPIRED)),
		array("TITLE" => GetMessage("TASKS_FILTER_DELAYED"), "FILTER" => array("STATUS" => CTasks::STATE_DEFERRED)),
		array("TITLE" => GetMessage("TASKS_FILTER_DECLINED"), "FILTER" => array("STATUS" => CTasks::STATE_DECLINED)),
		array(
			"TITLE" => GetMessage("TASKS_FILTER_CLOSED"),
			"FILTER" => array("STATUS" => array(
				CTasks::STATE_SUPPOSEDLY_COMPLETED,
				CTasks::STATE_COMPLETED
		)))
	);

	$bGroupMode = ($taskType === 'group');
	$oFilter = CTaskFilterCtrl::GetInstance($arResult['LOGGED_IN_USER'], $bGroupMode);
	$arResult['PRESETS_TREE']         = $oFilter->ListFilterPresets($bTreeMode = true);
	$arResult['PRESETS_LIST']         = $oFilter->ListFilterPresets($bTreeMode = false);
	$arResult['SELECTED_PRESET_NAME'] = $oFilter->GetSelectedFilterPresetName();
	$arResult['SELECTED_PRESET_ID']   = $oFilter->GetSelectedFilterPresetId();

	// We must use built-in filters of given user, 
	// so replace built-in filters of logged-in user with filters of given user
	if ($arResult['LOGGED_IN_USER'] != $arParams["USER_ID"])
	{
		$oFilter = CTaskFilterCtrl::GetInstance($arParams['USER_ID'], $bGroupMode);
		$arPresetsTree = $oFilter->ListFilterPresets($bTreeMode = true);
		$arPresetsList = $oFilter->ListFilterPresets($bTreeMode = false);

		if ( ! function_exists('tasks_23233455sf4_functionDropPresetsInTree') )
		{
			function tasks_23233455sf4_functionDropPresetsInTree (&$arPresetsIn, &$arDropIds)
			{
				$arPresetsOut = array();

				foreach ($arPresetsIn as $presetId => $arData)
				{
					if ( ! in_array($presetId, $arDropIds) )
						$arPresetsOut[$presetId] = $arData;

					if (isset($arData['#Children']))
					{
						$arPresetsOut[$presetId]['#Children'] = tasks_23233455sf4_functionDropPresetsInTree(
							$arData['#Children'],
							$arDropIds
						);

						if (empty($arPresetsOut[$presetId]['#Children']))
							unset($arPresetsOut[$presetId]['#Children']);
					}
				}

				return ($arPresetsOut);
			}
		}

		// Drop built-in filters of logged in user
		$arDrop = array();
		foreach (array_keys($arResult['PRESETS_LIST']) as $presetId)
		{
			if ($presetId <= 0)
				$arDrop[] = $presetId;
		}

		foreach ($arDrop as $presetId)
			unset($arResult['PRESETS_LIST'][$presetId]);

		$arResult['PRESETS_TREE'] = tasks_23233455sf4_functionDropPresetsInTree($arResult['PRESETS_TREE'], $arDrop);

		foreach ($arPresetsList as $presetId => $presetData)
		{
			// Replace only builted-in filters
			if ($presetId <= 0)
				$arResult['PRESETS_LIST'][$presetId] = $presetData;
		}

		foreach ($arPresetsTree as $presetId => $presetData)
		{
			// Replace only builted-in filters
			if ($presetId <= 0)
				$arResult['PRESETS_TREE'][$presetId] = $presetData;
		}
	}

	$arParentDepths = array(0 => -1);
	foreach ($arResult['PRESETS_LIST'] as $key => $value)
	{
		$depth = $arParentDepths[(int)$value['Parent']] + 1;
		$arResult['PRESETS_LIST'][$key]['#DEPTH'] = $depth;
		$arParentDepths[$key] = $depth;
	}

	// calculate items count for each filter
	if ( ! $bSkipCounter )
	{
		$obCache = new CPHPCache();
		$lifeTime = CTasksTools::CACHE_TTL_UNLIM;

		$arFiltersCount = array();
		$bDataNotCached = true;
		$arCacheTags = array('tasks_filter_presets');

		$cacheID = md5($arResult['LOGGED_IN_USER'] . "user" . $arParams["USER_ID"]);
		$arCacheTags[] = "tasks_user_" . $arResult['LOGGED_IN_USER'];

		if ($taskType == "group")
		{
			$cacheID .= '_' . (int) $arParams["GROUP_ID"];
			$arCacheTags[] = "tasks_group_" . (int) $arParams["GROUP_ID"];
		}

		$cacheDir = "/tasks/filter_tt_v2_" . $taskType 
			. '/' . substr($cacheID, -4, 2) 
			. '/' . substr($cacheID, -2) 
			. '/' . $cacheID;

		if(defined('BX_COMP_MANAGED_CACHE') && $obCache->InitCache($lifeTime, $cacheID, $cacheDir))
		{
			$arFiltersCount = $obCache->GetVars();
			$bDataNotCached = false;
		}
		else
		{
			foreach ($arResult['PRESETS_LIST'] as $presetId => $presetData)
			{
				// We can't cache counters for user-defined filters
				if ($presetId > 0)
					continue;

				$arFilter = $oFilter->GetFilterPresetConditionById($presetId);

				if (($taskType === 'group') && ($presetId <= 0))
					$arFilter['GROUP_ID'] = (int) $arParams['GROUP_ID'];

				if ($arFilter === false)
					$arFiltersCount[$presetId] = 0;
				else
				{
					$count = 0;

					$rsCount = CTasks::GetCount(
						$arFilter,
						array(
							'bIgnoreDbErrors'  => true,
							'bSkipExtraTables' => true,
							'bSkipUserFields'  => true,

							'bUseRightsCheck'  => false,
						)
					);
					if ($rsCount !== false)
					{
						if ($arCount = $rsCount->fetch())
							$count = (int) $arCount['CNT'];
					}

					$arFiltersCount[$presetId] = $count;
				}
			}
		}

		$arResult['COUNTS'] = $arFiltersCount;

		if ($bDataNotCached)
		{
			if (defined('BX_COMP_MANAGED_CACHE') && $obCache->StartDataCache())
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->StartTagCache($cacheDir);

				foreach ($arCacheTags as $cacheTag)
					$CACHE_MANAGER->RegisterTag($cacheTag);

				$CACHE_MANAGER->EndTagCache();
				$obCache->EndDataCache($arFiltersCount);
			}
		}
	}
}

$this->IncludeComponentTemplate();
