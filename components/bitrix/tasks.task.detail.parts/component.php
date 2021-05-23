<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("tasks"))
{
	ShowError(GetMessage("TASKS_MODULE_NOT_INSTALLED"));
	return;
}

try
{
	$arResult['LOGGED_IN_USER'] = (int) $USER->getId();
	$arResult['DEFER_LOAD'] = 'N';		// by default

	$alreadyEscaped = array('ALLOWED_ACTIONS', 'CHECKLIST_ITEMS', 'TASK', 'TASK_ID', 'NAME_TEMPLATE', 'TIMER');

	foreach ($alreadyEscaped as $paramName)
	{
		if (isset($arParams['~' . $paramName]))
			$arResult[$paramName] = $arParams['~' . $paramName];
	}

	if ( ! isset($arParams['FIRE_ON_CHANGED_EVENT']) )
		$arParams['FIRE_ON_CHANGED_EVENT'] = 'N';

	if (isset($arParams['DEFER_LOAD']))
		$arResult['DEFER_LOAD'] = $arParams['DEFER_LOAD'];

	$arResult['IS_IFRAME'] = $arParams['~IS_IFRAME'];

	$arParams["PUBLIC_MODE"] = isset($arParams["PUBLIC_MODE"]) &&
		($arParams["PUBLIC_MODE"] === true || $arParams["PUBLIC_MODE"] === "Y");

	$arResult['INNER_HTML'] = 'N';
	if (isset($arParams['INNER_HTML']) && ($arParams['INNER_HTML'] === 'Y'))
		$arResult['INNER_HTML'] = 'Y';

	$arWhiteList = array();
	$arKnownModes = array('VIEW TASK', 'CREATE TASK FORM');

	if ($arParams['MODE'] === 'VIEW TASK')
	{
		$arWhiteList = array(
			'checklist',
			'buttons',
			'right_sidebar',
			'reminder',
			'replication',
			'projectdependence',
			'log',
			'templateselector',
			'time',
			'sidebar',
            'user-view'
		);
	}
	elseif ($arParams['MODE'] === 'CREATE TASK FORM')
	{
		$arWhiteList = array('checklist');
	}
	elseif ($arParams['MODE'] === 'JUST AFTER TASK CREATED' || $arParams['MODE'] === 'JUST AFTER TASK EDITED')
	{
		$arChecklistItems = array();
		if (isset($_POST['CHECKLIST_ITEM_ID']))
		{
			if ( ! is_array($_POST['CHECKLIST_ITEM_ID']) )
				CTaskAssert::logError('[0x17949379] array expected in $_POST[\'CHECKLIST_ITEM_ID\']');
			elseif ( ! empty($_POST['CHECKLIST_ITEM_ID']) )
			{
				if ($arParams['TASK_ID'] > 0)
					$oTask = CTaskItem::getInstance($arParams['TASK_ID'], $USER->getId());

				if (
					($arParams['TASK_ID'] > 0)
					&& ($arParams['MODE'] === 'JUST AFTER TASK EDITED')
				)
				{
					list($arChecklistItemsInDb, $arMetaData) = CTaskCheckListItem::fetchList($oTask, array('SORT_INDEX' => 'ASC'));
					unset($arMetaData);

					$arChecklistItemsInDbIds = array();
					foreach ($arChecklistItemsInDb as $oChecklistItem)
						$arChecklistItemsInDbIds[] = $oChecklistItem->getId();
				}

				$sortIndex = 0;
				$arChecklistItemsInPostIds = array();
				foreach ($_POST['CHECKLIST_ITEM_ID'] as $postId)
				{
					if ( ! (
						isset($_POST['CHECKLIST_ITEM_TITLE'][$postId])
						&& isset($_POST['CHECKLIST_ITEM_IS_CHECKED'][$postId])
					))
					{
						CTaskAssert::logError('[0x513f2d9a] CHECKLIST_ITEM_TITLE[$postId] and CHECKLIST_ITEM_IS_CHECKED[$postId] are expected in $_POST');
						continue;
					}

					$arChecklistItemsInPostIds[] = $postId;
					
					try
					{
						$arFields = array(
							'TITLE'       => (string) $_POST['CHECKLIST_ITEM_TITLE'][$postId],
							'IS_COMPLETE' => (($_POST['CHECKLIST_ITEM_IS_CHECKED'][$postId] === 'Y') ? 'Y' : 'N'),
							'SORT_INDEX'  => $sortIndex
						);

						$sortIndex++;

						if ($arParams['TASK_ID'] > 0)
						{
							if (
								( ! is_numeric($postId) )
								|| ($postId < 0)
							)
							{
								$oCheckListItem = CTaskCheckListItem::add($oTask, $arFields);
								$arFields['ID'] = $oCheckListItem->getId();
							}
							else if (
								($arParams['TASK_ID'] > 0)
								&& ($arParams['MODE'] === 'JUST AFTER TASK EDITED')
							)
							{
								if (in_array($postId, $arChecklistItemsInDbIds))
								{
									foreach ($arChecklistItemsInDb as $oChecklistItem)
									{
										if ($oChecklistItem->getId() == $postId)
										{
											$arItemDataInDb = $oChecklistItem->getData();

											if (
												($arItemDataInDb['~TITLE'] !== $arFields['TITLE'])
												|| ($arItemDataInDb['~IS_COMPLETE'] !== $arFields['IS_COMPLETE'])
												|| ($arItemDataInDb['~SORT_INDEX'] !== $arFields['SORT_INDEX'])
											)
											{
												$oChecklistItem->update($arFields);
											}

											break;
										}
									}
								}
								else
								{
									$oCheckListItem = CTaskCheckListItem::add($oTask, $arFields);
									$arFields['ID'] = $oCheckListItem->getId();
								}
							}
						}
						
						if ( ! isset($arFileds['ID']) )
							$arFields['ID'] = $postId;

						$arFields['~ID']          = $arFields['ID'];
						$arFields['~TITLE']       = $arFields['TITLE'];
						$arFields['~SORT_INDEX']  = $arFields['SORT_INDEX'];
						$arFields['~IS_COMPLETE'] = $arFields['IS_COMPLETE'];
						$arFields['TITLE']        = htmlspecialcharsbx($arFields['~TITLE']);

						$arChecklistItems[] = $arFields;
					}
					catch (Exception $e)
					{
						$arTaskData = $oTask->getData(false);
						CTaskAssert::logError(
							'[0x05b70569] Can\'t create checklist item, exception $e->getCode() = ' . $e->getCode()
							. ', file: ' . $e->getFile() . ', line: ' . $e->getLine() . ', data: ' 
							. serialize(array(
								'LOGGED_USER_ID' => $USER->getId(),
								'TASK_ID' => $arTaskData['ID'],
								'CREATED_BY' => $arTaskData['CREATED_BY'],
								'RESPONSIBLE_ID' => $arTaskData['RESPONSIBLE_ID'],
								'ACCOMPLICES' => $arTaskData['ACCOMPLICES'],
								'AUDITORS' => $arTaskData['AUDITORS'],
								'ALLOWED_ACTIONS' => $oTask->getAllowedActions(true)
							))
							. '___END OF DATA'
						);
					}
				}

				if (
					($arParams['TASK_ID'] > 0)
					&& ($arParams['MODE'] === 'JUST AFTER TASK EDITED')
				)
				{
					$arItemsToRemove = array_diff($arChecklistItemsInDbIds, $arChecklistItemsInPostIds);
					if (is_array($arItemsToRemove) && ! empty($arItemsToRemove))
					{
						foreach ($arChecklistItemsInDb as $oChecklistItem)
						{
							if (in_array($oChecklistItem->getId(), $arItemsToRemove))
								$oChecklistItem->delete();
						}
					}
				}
			}
		}

		return ($arChecklistItems);
	}
	else
		throw new \Bitrix\Main\SystemException();

	$arResult['BLOCKS'] = array_intersect($arWhiteList, $arParams['BLOCKS']);

	if (
		isset($arParams['TASK_ID'])
		&& isset($arParams['LOAD_TASK_DATA'])
		&& ($arParams['LOAD_TASK_DATA'] === 'Y')
	)
	{
		$oTask = CTaskItem::getInstance($arParams['TASK_ID'], $arResult['LOGGED_IN_USER']);
		$arResult['ALLOWED_ACTIONS'] = $oTask->getAllowedActions($asStrings = true);
		$arResult['TASK'] = $oTask->getData();

		$arResult['TASK']['META:ALLOWED_ACTIONS_CODES'] = $oTask->getAllowedTaskActions();
		$arResult['TASK']['META:ALLOWED_ACTIONS'] = $arResult['ALLOWED_ACTIONS'];

		$arResult['TASK']['META:IN_DAY_PLAN'] = 'N';
		$arResult['TASK']['META:CAN_ADD_TO_DAY_PLAN'] = 'N';

		// Was task created from template?
		if ($arResult['TASK']['FORKED_BY_TEMPLATE_ID'])
		{
			$rsTemplate = CTaskTemplates::GetByID($arResult['TASK']['FORKED_BY_TEMPLATE_ID']);

			if ($arTemplate = $rsTemplate->Fetch())
			{
				$arTemplate['REPLICATE_PARAMS'] = unserialize($arTemplate['REPLICATE_PARAMS']);
				$arResult['TASK']['FORKED_BY_TEMPLATE'] = $arTemplate;
			}
		}

		if (
			(
				($arResult['TASK']["RESPONSIBLE_ID"] == $arResult['LOGGED_IN_USER'])
				|| (in_array($arResult['LOGGED_IN_USER'], $arResult['TASK']['ACCOMPLICES']))
			)
			&& CModule::IncludeModule("timeman") 
			&& (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite())
		)
		{
			$arResult['TASK']['META:CAN_ADD_TO_DAY_PLAN'] = 'Y';

			$arTasksInPlan = CTaskPlannerMaintance::getCurrentTasksList();

			// If in day plan already
			if (
				is_array($arTasksInPlan)
				&& in_array($arResult['TASK']["ID"], $arTasksInPlan)
			)
			{
				$arResult['TASK']['META:IN_DAY_PLAN'] = 'Y';
				$arResult['TASK']['META:CAN_ADD_TO_DAY_PLAN'] = 'N';
			}
		}
	}
}
catch (Exception $e)
{
	return (false);
}

$this->IncludeComponentTemplate();
