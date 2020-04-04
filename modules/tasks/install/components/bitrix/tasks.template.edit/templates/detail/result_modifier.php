<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arResult['TASK'] = $arResult['DATA'];
if(is_array($arResult['DATA']))
	$arResult['CHECKLIST_ITEMS'] = $arResult['DATA']['CHECKLIST_ITEMS'];

if(!is_array($arResult['CHECKLIST_ITEMS']))
	$arResult['CHECKLIST_ITEMS'] = array();

foreach($arResult['CHECKLIST_ITEMS'] as &$item)
{
	$item['~TITLE'] = $item['TITLE'];
}
unset($item);

//$arResult["PREV_TASKS"] = $arResult["DEPENDS_ON"];

$arResult['USERS_DATA']  = array();
$arResult['GROUPS_DATA'] = array();
$arResult['TASKS_DATA']  = array();
$loggedInUserId = $USER->GetID();
if ( ! empty($arResult['LOG']) )
{
	$arUsersIds   = array();	// Collect all users' ids (to do only one SQL-query for users)
	$arGroupsIds  = array();	// Collect all groups' ids (to do only one SQL-query for groups)
	$arTasksIds = array();		// Collect all parent tasks' ids (to do only one SQL-query for parent tasks)

	foreach ($arResult['LOG'] as &$record)
	{
		switch ($record['FIELD'])
		{
			case 'CREATED_BY':
			case 'RESPONSIBLE_ID':
				if ($record['FROM_VALUE'])
					$arUsersIds[] = $record['FROM_VALUE'];

				if ($record['TO_VALUE'])
					$arUsersIds[] = $record['TO_VALUE'];
			break;

			case 'AUDITORS':
			case 'ACCOMPLICES':
				if ($record['FROM_VALUE'])
				{
					$arUsersIds = array_merge(
						$arUsersIds,
						explode(',', $record['FROM_VALUE'])
					);
				}

				if ($record['TO_VALUE'])
				{
					$arUsersIds = array_merge(
						$arUsersIds,
						explode(',', $record['TO_VALUE'])
					);
				}
			break;

			case 'GROUP_ID':
				if (
					$record['FROM_VALUE']
					&& ( ! isset($arGroupsIds[(int)$record['FROM_VALUE']]) )
					&& CSocNetGroup::CanUserViewGroup($loggedInUserId, $record['FROM_VALUE'])
				)
				{
					$arGroupsIds[$record['FROM_VALUE']] = (int) $record['FROM_VALUE'];
				}

				if (
					$record['TO_VALUE']
					&& ( ! isset($arGroupsIds[(int)$record['TO_VALUE']]) )
					&& CSocNetGroup::CanUserViewGroup($loggedInUserId, $record['TO_VALUE'])
				)
				{
					$arGroupsIds[$record['FROM_VALUE']] = (int) $record['TO_VALUE'];
				}
			break;

			case 'PARENT_ID':
				if ($record['FROM_VALUE'])
					$arTasksIds[] = (int) $record['FROM_VALUE'];

				if ($record['TO_VALUE'])
					$arTasksIds[] = (int) $record['TO_VALUE'];
			break;

			case 'DEPENDS_ON':
				if ($record['FROM_VALUE'])
				{
					$arTasksIds = array_merge(
						$arTasksIds,
						explode(',', $record['FROM_VALUE'])
					);
				}

				if ($record['TO_VALUE'])
				{
					$arTasksIds = array_merge(
						$arTasksIds,
						explode(',', $record['TO_VALUE'])
					);
				}
			break;

			default:
				continue;
			break;
		}
	}
	unset($record);

	if ( ! empty($arUsersIds) )
	{
		$arUsersIds = array_unique(array_filter($arUsersIds));

		if ( ! empty($arUsersIds) )
		{
			$rsUsers = CUser::GetList(
				$by = 'ID', 
				$order = 'ASC',
				array(
					'ID' => implode('|', $arUsersIds)
				),
				array(
					'FIELDS' => array(
						'ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN'
					)
				)
			);

			while ($arUser = $rsUsers->getNext())
				$arResult['USERS_DATA'][$arUser['ID']] = $arUser;
		}
	}

	if ( ! empty($arGroupsIds) )
	{
		$arGroupsIds = array_unique(array_filter(array_values($arGroupsIds)));

		if ( ! empty($arGroupsIds) )
		{
			$rsGroups = CSocNetGroup::GetList(
				array('ID' => 'ASC'),
				array('ID' => $arGroupsIds),
				false,		// group by
				false,		// nav params
				array('ID', 'NAME')
			);

			while($arGroup = $rsGroups->getNext())
			{
				$arGroup['URL'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_GROUP'], array('group_id' => $arGroup['ID']));
				$arResult['GROUPS_DATA'][$arGroup['ID']] = $arGroup;
			}
		}
	}

	if ( ! empty($arTasksIds) )
	{
		$arTasksIds = array_unique(array_filter($arTasksIds));

		if ( ! empty($arTasksIds) )
		{
			$rsTasks = CTasks::GetList(
				array('ID' => 'ASC'),
				array('ID' => $arTasksIds),
				array('ID', 'TITLE')
			);

			while($arTask = $rsTasks->getNext())
			{
				$arTask['URL'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_TASKS_TASK'], array('task_id' => $arTask['ID'], 'action' => 'view'));
				$arResult['TASKS_DATA'][$arTask['ID']] = $arTask;
			}
		}
	}
}

// check out which sections we need to show
$arResult['DISPLAY_BLOCKS'] = array(
	'CONNECTED_ENTITIES' =>		false,
	'PROPERTIES' =>				false,
);

if(
	!empty($arResult["TASK"]["TAGS"])
	||
	($arResult["TASK"]["GROUP_ID"] && CSocNetGroup::CanUserViewGroup($USER->GetID(), $arResult["TASK"]["GROUP_ID"]))
	||
	intval($arResult["TASK"]["BASE_TEMPLATE_ID"])
	||
	intval($arResult["TASK"]["PARENT_ID"])
)
{
	$arResult['DISPLAY_BLOCKS']['CONNECTED_ENTITIES'] = true;
}


$arResult["TASK"]["CREATED_BY_NAME"] = htmlspecialcharsbx($arResult["TASK"]["CREATED_BY_NAME"]);
$arResult["TASK"]["CREATED_BY_LAST_NAME"] = htmlspecialcharsbx($arResult["TASK"]["CREATED_BY_LAST_NAME"]);
$arResult["TASK"]["CREATED_BY_LOGIN"] = htmlspecialcharsbx($arResult["TASK"]["CREATED_BY_LOGIN"]);
$arResult["TASK"]["CREATED_BY_SECOND_NAME"] = htmlspecialcharsbx($arResult["TASK"]["CREATED_BY_SECOND_NAME"]);

if(is_array($arResult["USER_FIELDS"]) && !empty($arResult["USER_FIELDS"]))
{
	foreach($arResult["USER_FIELDS"] as $fld)
	{
		if(!empty($fld['VALUE']))
		{
			if($arUserField['FIELD_NAME'] == 'UF_TASK_WEBDAV_FILES')
				$arResult['DISPLAY_BLOCKS']['CONNECTED_ENTITIES'] = true; // files lies at "CONNECTED_ENTITIES" section
			else
				$arResult['DISPLAY_BLOCKS']['PROPERTIES'] = true; // other user fields at "PROPERTIES"
		}
	}
}


