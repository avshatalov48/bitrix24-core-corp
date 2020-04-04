<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksTaskDetailPartsComponent extends TasksBaseComponent
{
	public function executeComponent()
	{
		if(!CModule::IncludeModule("tasks"))
		{
			ShowError(GetMessage("TASKS_MODULE_NOT_INSTALLED"));
			return false;
		}
		else
		{
			if(array_key_exists('BLOCK', $this->arParams))
			{
				$blockName = preg_replace('#[^a-z0-9_-]*#', '', ToLower(trim((string) $this->arParams['BLOCK'])));
				if($blockName == '')
				{
					ShowError('No block specified');
					return false;
				}

				$this->arResult['BLOCK'] = $blockName;
			}
			else // legacy functionality, deprecated
			{
				$result = $this->includeLegacy();
				if($result !== true)
				{
					return $result; // error or smth worse
				}
			}

			$this->includeComponentTemplate();
		}
	}

	/**
	 * @return bool
	 * @deprecated
	 */
	private function includeLegacy()
	{
		$cUserId = \Bitrix\Tasks\Util\User::getId();

		try
		{
			$this->arResult['LOGGED_IN_USER'] = (int) $cUserId;
			$this->arResult['DEFER_LOAD'] = 'N';		// by default

			$alreadyEscaped = array('ALLOWED_ACTIONS', 'CHECKLIST_ITEMS', 'TASK', 'TASK_ID', 'NAME_TEMPLATE', 'TIMER');

			foreach ($alreadyEscaped as $paramName)
			{
				if (isset($this->arParams['~' . $paramName]))
					$this->arResult[$paramName] = $this->arParams['~' . $paramName];
			}

			if ( ! isset($this->arParams['FIRE_ON_CHANGED_EVENT']) )
				$this->arParams['FIRE_ON_CHANGED_EVENT'] = 'N';

			if (isset($this->arParams['DEFER_LOAD']))
				$this->arResult['DEFER_LOAD'] = $this->arParams['DEFER_LOAD'];

			$this->arResult['IS_IFRAME'] = $this->arParams['~IS_IFRAME'];

			$this->arParams["PUBLIC_MODE"] = isset($this->arParams["PUBLIC_MODE"]) &&
				($this->arParams["PUBLIC_MODE"] === true || $this->arParams["PUBLIC_MODE"] === "Y");

			$this->arResult['INNER_HTML'] = 'N';
			if (isset($this->arParams['INNER_HTML']) && ($this->arParams['INNER_HTML'] === 'Y'))
				$this->arResult['INNER_HTML'] = 'Y';

			$arWhiteList = array();
			$arKnownModes = array('VIEW TASK', 'CREATE TASK FORM');

			if ($this->arParams['MODE'] === 'VIEW TASK')
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
					'effective',
					'sidebar',
					'user-view'
				);
			}
			elseif ($this->arParams['MODE'] === 'CREATE TASK FORM')
			{
				$arWhiteList = array('checklist');
			}
			elseif ($this->arParams['MODE'] === 'JUST AFTER TASK CREATED' || $this->arParams['MODE'] === 'JUST AFTER TASK EDITED')
			{
				$arChecklistItems = array();
				if (isset($_POST['CHECKLIST_ITEM_ID']))
				{
					if ( ! is_array($_POST['CHECKLIST_ITEM_ID']) )
						CTaskAssert::logError('[0x17949379] array expected in $_POST[\'CHECKLIST_ITEM_ID\']');
					elseif ( ! empty($_POST['CHECKLIST_ITEM_ID']) )
					{
						if ($this->arParams['TASK_ID'] > 0)
							$oTask = CTaskItem::getInstance($this->arParams['TASK_ID'], $cUserId);

						if (
							($this->arParams['TASK_ID'] > 0)
							&& ($this->arParams['MODE'] === 'JUST AFTER TASK EDITED')
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

								if ($this->arParams['TASK_ID'] > 0)
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
										($this->arParams['TASK_ID'] > 0)
										&& ($this->arParams['MODE'] === 'JUST AFTER TASK EDITED')
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
										'LOGGED_USER_ID' => $cUserId,
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
							($this->arParams['TASK_ID'] > 0)
							&& ($this->arParams['MODE'] === 'JUST AFTER TASK EDITED')
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

			$this->arResult['BLOCKS'] = array_intersect($arWhiteList, $this->arParams['BLOCKS']);

			if (
				isset($this->arParams['TASK_ID'])
				&& isset($this->arParams['LOAD_TASK_DATA'])
				&& ($this->arParams['LOAD_TASK_DATA'] === 'Y')
			)
			{
				$oTask = CTaskItem::getInstance($this->arParams['TASK_ID'], $this->arResult['LOGGED_IN_USER']);
				$this->arResult['ALLOWED_ACTIONS'] = $oTask->getAllowedActions($asStrings = true);
				$this->arResult['TASK'] = $oTask->getData();

				$this->arResult['TASK']['META:ALLOWED_ACTIONS_CODES'] = $oTask->getAllowedTaskActions();
				$this->arResult['TASK']['META:ALLOWED_ACTIONS'] = $this->arResult['ALLOWED_ACTIONS'];

				$this->arResult['TASK']['META:IN_DAY_PLAN'] = 'N';
				$this->arResult['TASK']['META:CAN_ADD_TO_DAY_PLAN'] = 'N';

				// Was task created from template?
				if ($this->arResult['TASK']['FORKED_BY_TEMPLATE_ID'])
				{
					$rsTemplate = CTaskTemplates::GetByID($this->arResult['TASK']['FORKED_BY_TEMPLATE_ID']);

					if ($arTemplate = $rsTemplate->Fetch())
					{
						$arTemplate['REPLICATE_PARAMS'] = unserialize($arTemplate['REPLICATE_PARAMS']);
						$this->arResult['TASK']['FORKED_BY_TEMPLATE'] = $arTemplate;
					}
				}

				if (
					(
						($this->arResult['TASK']["RESPONSIBLE_ID"] == $this->arResult['LOGGED_IN_USER'])
						|| (in_array($this->arResult['LOGGED_IN_USER'], $this->arResult['TASK']['ACCOMPLICES']))
					)
					&& CModule::IncludeModule("timeman")
					&& (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite())
				)
				{
					$this->arResult['TASK']['META:CAN_ADD_TO_DAY_PLAN'] = 'Y';

					$arTasksInPlan = CTaskPlannerMaintance::getCurrentTasksList();

					// If in day plan already
					if (
						is_array($arTasksInPlan)
						&& in_array($this->arResult['TASK']["ID"], $arTasksInPlan)
					)
					{
						$this->arResult['TASK']['META:IN_DAY_PLAN'] = 'Y';
						$this->arResult['TASK']['META:CAN_ADD_TO_DAY_PLAN'] = 'N';
					}
				}
			}
		}
		catch (Exception $e)
		{
			return false;
		}

		return true;
	}
}