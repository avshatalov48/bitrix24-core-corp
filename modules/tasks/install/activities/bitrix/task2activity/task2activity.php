<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Text\Emoji;

class CBPTask2Activity extends CBPActivity implements IBPEventActivity, IBPActivityExternalEventListener, IBPEventDrivenActivity
{
	private $isInEventActivityMode = false;
	private static $cycleCounter = [];
	const CYCLE_LIMIT = 3;

	private static $arAllowedTasksFieldNames = array(
		'TITLE', 'CREATED_BY', 'RESPONSIBLE_ID', 'ACCOMPLICES',
		'START_DATE_PLAN', 'END_DATE_PLAN', 'DEADLINE', 'DESCRIPTION',
		'PRIORITY', 'GROUP_ID', 'ALLOW_CHANGE_DEADLINE', 'TASK_CONTROL',
		'ADD_IN_REPORT', 'AUDITORS', 'ALLOW_TIME_TRACKING', 'PARENT_ID'
	);

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title"                   => "",
			"Fields"                  => null,
			"HoldToClose"             => false,
			"AUTO_LINK_TO_CRM_ENTITY" => true,
			"AsChildTask"             => 0,
			"CheckListItems"          => null,
			"TimeEstimateHour"          => null,
			"TimeEstimateMin"          => null,

			//return properties
			"ClosedBy"                => null,
			"ClosedDate"              => null,
			"TaskId"                  => null,
			"IsDeleted"               => 'N',
		);

		$this->SetPropertiesTypes([
			'TaskId' => ['Type' => 'int'],
			'ClosedBy' => ['Type' => 'string'],
			'ClosedDate' => ['Type' => 'datetime'],
			'IsDeleted' => ['Type' => 'bool'],
		]);
	}

	protected function ReInitialize()
	{
		parent::ReInitialize();

		$this->ClosedBy = null;
		$this->ClosedDate = null;
		$this->TaskId = null;
		$this->IsDeleted = 'N';
	}

	public function Cancel()
	{
		if (!$this->isInEventActivityMode && $this->HoldToClose)
		{
			$this->Unsubscribe($this);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public function Execute()
	{
		if ($this->isInEventActivityMode)
		{
			return CBPActivityExecutionStatus::Closed;
		}

		if (!$this->createTask())
		{
			return CBPActivityExecutionStatus::Closed;
		}

		if (!$this->HoldToClose)
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$this->Subscribe($this);
		$this->isInEventActivityMode = false;

		return CBPActivityExecutionStatus::Executing;
	}

	private function createTask()
	{
		if (!CModule::IncludeModule("tasks"))
		{
			return false;
		}

		$documentId = $this->GetDocumentId();

		$logMap = static::getPropertiesMap($this->getDocumentType());

		$this->checkCycling($documentId);

		$fields = $this->Fields;

		$fields["CREATED_BY"] = CBPHelper::ExtractUsers($this->Fields["CREATED_BY"], $documentId, true);
		$fields["RESPONSIBLE_ID"] = CBPHelper::ExtractUsers($this->Fields["RESPONSIBLE_ID"], $documentId, true);
		$fields["ACCOMPLICES"] = CBPHelper::ExtractUsers($this->Fields["ACCOMPLICES"], $documentId);
		$fields["AUDITORS"] = CBPHelper::ExtractUsers($this->Fields["AUDITORS"], $documentId);

		if (!$fields["SITE_ID"])
		{
			$fields["SITE_ID"] = SITE_ID;
		}

		if(!is_array($fields['UF_CRM_TASK']))
		{
			$fields['UF_CRM_TASK'] = isset($fields['UF_CRM_TASK']) ? [$fields['UF_CRM_TASK']] : [];
		}
		if ($this->AUTO_LINK_TO_CRM_ENTITY && $documentId[0] === 'crm' && CModule::IncludeModule('crm'))
		{
			$documentId   = $this->GetDocumentId();
			$documentType = $this->GetDocumentType();

			$letter = CCrmOwnerTypeAbbr::ResolveByTypeID(CCrmOwnerType::ResolveID($documentType[2]));

			$fields['UF_CRM_TASK'][] = str_replace($documentType[2], $letter, $documentId[2]);
		}

		if ($documentId[0] === 'tasks' && $this->AsChildTask)
		{
			$fields['PARENT_ID'] = $documentId[2];

			if (empty($fields['GROUP_ID']))
			{
				$res = \CTasks::GetList(
					array(),
					['ID' => (int) $fields['PARENT_ID'], 'CHECK_PERMISSIONS' => 'N'],
					array('GROUP_ID')
				);
				if ($res && ($task = $res->fetch()))
				{
					$fields['GROUP_ID'] = $task['GROUP_ID'];
				}
			}
		}
		elseif(!$this->AsChildTask && CBPHelper::isEmptyValue($fields['PARENT_ID']) === false)
		{
			$parentId = is_array($fields['PARENT_ID']) ? reset($fields['PARENT_ID']) : $fields['PARENT_ID'];
			if(is_null(\Bitrix\Tasks\Integration\Bizproc\Document\Task::getDocument($parentId)))
			{
				$parentIdModifiedProperty = $logMap['PARENT_ID'];
				$parentIdModifiedProperty['BaseType'] = 'string';
				$this->writeDebugInfo($this->getDebugInfo(['PARENT_ID' => $parentId], ['PARENT_ID' => $parentIdModifiedProperty]));

				$this->WriteToTrackingService(
					GetMessage('BPTA1A_TASK_TASK_PRESENCE_ERROR', ['#TASK_ID#' => $parentId]),
					0,
					CBPTrackingType::Error
				);

				return false;
			}
			elseif(empty($arFieldsChecked['GROUP_ID']))
			{
				$res = \CTasks::GetList(
					array(),
					['ID' => (int) $parentId, 'CHECK_PERMISSIONS' => 'N'],
					array('GROUP_ID')
				);
				if ($res && ($task = $res->fetch()))
				{
					$fields['GROUP_ID'] = $task['GROUP_ID'];
				}
			}
			$fields['PARENT_ID'] = (int) $parentId;
		}

		$arUnsetFields = [];
		foreach ($fields as $fieldName => $fieldValue)
		{
			if (mb_substr($fieldName, -5) === '_text')
			{
				$fields[mb_substr($fieldName, 0, -5)] = $fieldValue;
				$arUnsetFields[] = $fieldName;
			}
		}

		foreach ($arUnsetFields as $fieldName)
		{
			unset($fields[$fieldName]);
		}

		// Check fields for "white" list
		$arFieldsChecked = [];
		foreach (array_keys($fields) as $fieldName)
		{
			if (
				in_array($fieldName, static::$arAllowedTasksFieldNames, true)
				||
				\Bitrix\Tasks\Util\Userfield::isUFKey($fieldName)
			)
			{
				$rawFields = $this->getRawProperty('Fields');
				if('UF_TASK_WEBDAV_FILES' == $fieldName && $this->canUploadFilesToDisk($rawFields[$fieldName])
					&& CModule::IncludeModule('disk') && Bitrix\Disk\Configuration::isSuccessfullyConverted())
				{
					$fields[$fieldName] = $this->uploadFilesToDisk($fields[$fieldName], $fields['CREATED_BY']);
				}
				elseif('UF_TASK_WEBDAV_FILES' == $fieldName && is_array($fields[$fieldName]))
				{
					foreach($fields[$fieldName] as $key => $fileId)
					{
						if(!empty($fileId) && is_string($fileId) && mb_substr($fileId, 0, 1) != 'n')
						{
							if(CModule::IncludeModule("disk") && \Bitrix\Disk\Configuration::isSuccessfullyConverted())
							{
								$item = \Bitrix\Disk\Internals\FileTable::getList(array(
									'select' => array('ID'),
									'filter' => array('=XML_ID' => $fileId, 'TYPE' => \Bitrix\Disk\Internals\FileTable::TYPE_FILE)
								))->fetch();

								if($item)
								{
									$fields[$fieldName][$key] = 'n'.$item['ID'];
								}
							}
						}
					}
					unset($fileId);
				}

				$arFieldsChecked[$fieldName] = $fields[$fieldName];
			}
		}

		if (empty($arFieldsChecked['CREATED_BY']))
		{
			$this->writeDebugInfo(
				$this->getDebugInfo(
					['CREATED_BY' => $arFieldsChecked['CREATED_BY']],
					['CREATED_BY' => $logMap['CREATED_BY']]
				)
			);

			$this->WriteToTrackingService(
				GetMessage("BPSA_CREATED_BY_ERROR"),
				0,
				CBPTrackingType::Error
			);

			return false;
		}

		$allDateFields = array_merge(
			['DEADLINE', 'END_DATE_PLAN', 'START_DATE_PLAN'],
			array_keys(\Bitrix\Tasks\Integration\Bizproc\Document\Task::getFieldsCreatedByUser('datetime'))
		);
		foreach ($allDateFields as $dateField)
		{
			$checkedDateField = $this->assertDateField($dateField, $arFieldsChecked[$dateField]);
			if(!$checkedDateField)
			{
				unset($arFieldsChecked[$dateField]);
			}
			else
			{
				$arFieldsChecked[$dateField] = $checkedDateField;
			}
		}

		$this->checkPlanDates($arFieldsChecked);

		if ($fields['ALLOW_TIME_TRACKING'] === 'Y')
		{
			$arFieldsChecked['TIME_ESTIMATE'] = (int) $this->TimeEstimateHour * 3600 + (int) $this->TimeEstimateMin * 60;
		}

		$prevOccurAsUserId = \Bitrix\Tasks\Util\User::getOccurAsId(); // null or positive integer
		\Bitrix\Tasks\Util\User::setOccurAsId($arFieldsChecked['CREATED_BY']);

		$result = false;
		$task = null;
		$errors = array();
		try
		{
			$this->writeDebugInfo($this->getDebugInfo(array_merge($arFieldsChecked, ['HoldToClose' => $this->HoldToClose])));

			// todo: use \Bitrix\Tasks\Item\Task here
			$task = CTaskItem::add($arFieldsChecked, \Bitrix\Tasks\Util\User::getAdminId());
			$result = $task->getId();
		}
		catch(TasksException $e)
		{
			// todo: incapsulate this
			if($e->checkOfType(TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE))
			{
				$errors = unserialize($e->getMessage(), ['allowed_classes' => false]);
			}
		}

		\Bitrix\Tasks\Util\User::setOccurAsId($prevOccurAsUserId);

		if (!$result)
		{
			if (count($errors) > 0)
			{
				$errorDesc = array();
				if(is_array($errors) && !empty($errors))
				{
					foreach($errors as $error)
					{
						$errorDesc[] = $error['text'].' ('.$error['id'].')';
					}
				}

				$this->WriteToTrackingService(
					GetMessage("BPSA_TRACK_ERROR").(!empty($errorDesc) ? ' '.implode(', ', $errorDesc) : ''),
					0,
					CBPTrackingType::Error
				);
			}

			return false;
		}

		$checkListItems = $this->CheckListItems;
		if ($checkListItems && is_array($checkListItems))
		{
			$taskItem = CTaskItem::getInstance($result, $arFieldsChecked['CREATED_BY']);

			foreach ($checkListItems as $checkListItem)
			{
				if ($checkListItem)
				{
					if (is_array($checkListItem))
					{
						$checkListItem = implode(', ', \CBPHelper::MakeArrayFlat($checkListItem));
					}
					$checkListItem = mb_substr(trim((string)$checkListItem), 0, 255);
					if ($checkListItem === '')
					{
						continue;
					}

					\CTaskCheckListItem::add($taskItem, ['TITLE' => $checkListItem]);
				}
			}

			if (count(array_filter($checkListItems)) === count($checkListItems))
			{
				$map = $this->getDebugInfo(
					['CHECK_LIST_ITEMS' => $checkListItems],
					[
						"CHECK_LIST_ITEMS" => [
							"Name" => GetMessage("BPSA_CHECK_LIST_ITEMS"),
							"Type" => "string",
							"Required" => false,
							"Multiple" => true,
						],
					]
				);
				$this->writeDebugInfo($map);
			}
		}

		$this->TaskId = $result;
		$this->markAsBPTask($result);
		$this->WriteToTrackingService(str_replace("#VAL#", $result, GetMessage("BPSA_TRACK_OK")));

		return $result > 0;
	}

	protected function canUploadFilesToDisk($value) : bool
	{
		return is_string($value) && CBPActivity::isExpression($value);
	}

	protected function uploadFilesToDisk($fileIds, $userId) : array
	{
		if(is_null($fileIds))
		{
			return [];
		}
		if(!is_array($fileIds))
		{
			$fileIds = [$fileIds];
		}

		$diskFilesIds = [];
		foreach ($fileIds as $id)
		{
			$file = CFile::MakeFileArray($id);
			if(is_array($file))
			{
				$uploadedFile = \Bitrix\Tasks\Integration\Disk\UserField::uploadFile($file, $userId);
				$diskFilesIds[] = $uploadedFile->getData()['ATTACHMENT_ID'];
			}
		}
		return $diskFilesIds;
	}

	protected function assertDateField(string $dateFieldName, $dateFieldValue)
	{
		if(is_object($dateFieldValue))
		{
			return (string) $dateFieldValue;
		}
		elseif(is_array($dateFieldValue))
		{
			$realDateField = [];
			foreach ($dateFieldValue as $date)
			{
				$checkedField = $this->assertDateField($dateFieldName, $date);
				if(is_array($checkedField))
				{
					$realDateField = array_merge($realDateField, $checkedField);
				}
				elseif($checkedField)
				{
					$realDateField[] = $checkedField;
				}
			}
			return $realDateField;
		}

		if (!empty($dateFieldValue) && !CheckDateTime($dateFieldValue))
		{
			$this->WriteToTrackingService(
				'Incorrect '.$dateFieldName.': '.$dateFieldValue,
				0,
				CBPTrackingType::Error
			);
			return false;
		}
		return $dateFieldValue;
	}

	protected function checkPlanDates(&$fields)
	{
		if (!empty($fields['START_DATE_PLAN']) && !empty($fields['END_DATE_PLAN']))
		{
			$startDate = MakeTimeStamp($fields['START_DATE_PLAN']);
			$endDate = MakeTimeStamp($fields['END_DATE_PLAN']);
			if ($endDate < $startDate)
			{
				unset($fields['END_DATE_PLAN']);
			}
		}
	}

	protected function markAsBPTask(int $taskId): void
	{
		if(!CModule::IncludeModule('crm'))
		{
			return;
		}

		[$documentType, $documentId] = mb_split('_(?=[^_]*$)', $this->GetDocumentId()[2]);

		$documentStage = $this->getDocumentStage($documentType);

		if($documentStage !== '')
		{
			$activity = CCrmActivity::GetList(
				[],
				[
					'OWNER_ID' => $documentId,
					'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($documentType),
					'TYPE_ID' => CCrmActivityType::Task,
					'ASSOCIATED_ENTITY_ID' => $taskId
				],
				false,
				false,
				['ID']
			)->Fetch();

			if($activity)
			{
				CCrmActivity::Update(
					$activity['ID'],
					['SETTINGS' => ['OWNER_STAGE' => $documentStage]],
					false,
					false
				);
			}
		}
	}

	protected function getDocumentStage(string $documentType): string
	{
		if(!CModule::IncludeModule('crm'))
		{
			return '';
		}

		$runtime = CBPRuntime::GetRuntime();
		$runtime->StartRuntime();
		$documentService = $runtime->GetService('DocumentService');
		$document = $documentService->GetDocument($this->GetDocumentId());

		switch ($documentType)
		{
			case CCrmOwnerType::LeadName:
			case CCrmOwnerType::QuoteName:
				return $document['STATUS_ID'] ?? '';

			case CCrmOwnerType::DealName:
				return $document['STAGE_ID'] ?? '';

			default:
				$documentTypeId = CCrmOwnerType::ResolveID($documentType);
				if(
					method_exists(CCrmOwnerType::class, 'isPossibleDynamicTypeId')
					&& CCrmOwnerType::isPossibleDynamicTypeId($documentTypeId)
				)
				{
					return $document['STAGE_ID'] ?? $document['STATUS_ID'] ?? '';
				}
				return '';
		}
	}

	public function Subscribe(IBPActivityExternalEventListener $eventHandler)
	{
		$this->isInEventActivityMode = true;

		if ($eventHandler instanceof CBPListenEventActivitySubscriber)
		{
			$result = $this->createTask();
			if (!$result)
			{
				return false;
			}
		}

		$taskId = (int)$this->TaskId;
		if (!$taskId)
		{
			return false;
		}

		$schedulerService = $this->workflow->GetService("SchedulerService");
		$schedulerService->SubscribeOnEvent($this->workflow->GetInstanceId(), $this->name, "tasks", "OnTaskUpdate", $taskId);
		$schedulerService->SubscribeOnEvent($this->workflow->GetInstanceId(), $this->name, "tasks", "OnTaskDelete", $taskId);

		$this->workflow->AddEventHandler($this->name, $eventHandler);
		$this->WriteToTrackingService(GetMessage("BPSA_TRACK_SUBSCR"));
	}

	public function Unsubscribe(IBPActivityExternalEventListener $eventHandler)
	{
		$taskId = (int)$this->TaskId;

		$schedulerService = $this->workflow->GetService("SchedulerService");
		$schedulerService->UnSubscribeOnEvent($this->workflow->GetInstanceId(), $this->name, "tasks", "OnTaskUpdate", $taskId);
		$schedulerService->UnSubscribeOnEvent($this->workflow->GetInstanceId(), $this->name, "tasks", "OnTaskDelete", $taskId);

		//delete invalid subscriptions
		$schedulerService->UnSubscribeOnEvent($this->workflow->GetInstanceId(), $this->name, "tasks", "OnTaskUpdate", null);
		$schedulerService->UnSubscribeOnEvent($this->workflow->GetInstanceId(), $this->name, "tasks", "OnTaskDelete", null);

		$this->workflow->RemoveEventHandler($this->name, $eventHandler);
	}

	public function OnExternalEvent($arEventParameters = array())
	{
		if ($this->onExternalEventHandler($arEventParameters))
		{
			$this->Unsubscribe($this);
			$this->workflow->CloseActivity($this);
		}

		return;
	}

	public function OnExternalDrivenEvent($arEventParameters = array())
	{
		return $this->onExternalEventHandler($arEventParameters);
	}

	private function onExternalEventHandler($arEventParameters = array())
	{
		if ($this->TaskId != $arEventParameters[0])
		{
			return;
		}

		if ($this->executionStatus != CBPActivityExecutionStatus::Closed)
		{
			if (isset($arEventParameters['eventName']) && $arEventParameters['eventName'] === 'OnTaskDelete')
			{
				$this->IsDeleted = 'Y';
				$this->WriteToTrackingService(GetMessage("BPSA_TRACK_DELETED"));
				return true;
			}
			elseif ($arEventParameters[1]["STATUS"] == 5)
			{
				$this->ClosedBy = "user_".$arEventParameters[1]["CLOSED_BY"];
				$this->ClosedDate = $arEventParameters[1]["CLOSED_DATE"];

				$this->WriteToTrackingService(str_replace("#DATE#", $arEventParameters[1]["CLOSED_DATE"], GetMessage("BPSA_TRACK_CLOSED")));
				return true;
			}
		}
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null, $currentSiteId = null)
	{
		if (!is_array($arWorkflowParameters))
		{
			$arWorkflowParameters = array();
		}
		if (!is_array($arWorkflowVariables))
		{
			$arWorkflowVariables = array();
		}

		$rawValues = array();

		if (!is_array($arCurrentValues))
		{
			$arCurrentValues = array();

			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
			if (is_array($arCurrentActivity["Properties"])
				&& array_key_exists("Fields", $arCurrentActivity["Properties"])
				&& is_array($arCurrentActivity["Properties"]["Fields"]))
			{
				$textUserFields = ["UF_TASK_WEBDAV_FILES", "UF_CRM_TASK"];

				foreach ($arCurrentActivity["Properties"]["Fields"] as $k => $v)
				{
					$arCurrentValues[$k] = $v;

					if (in_array($k, array("CREATED_BY", "RESPONSIBLE_ID", "ACCOMPLICES", "AUDITORS")))
					{
						if (!is_array($arCurrentValues[$k]))
							$arCurrentValues[$k] = array($arCurrentValues[$k]);

						$ar = (array) $arCurrentValues[$k];
						/*foreach ($arCurrentValues[$k] as $val)
						{
							if (intval($val)."!" == $val."!")
								$val = "user_".$val;
							$ar[] = $val;
						}*/

						$rawValues[$k] = $ar;
						$arCurrentValues[$k] = CBPHelper::UsersArrayToString($ar, $arWorkflowTemplate, $documentType);
					}
					if(in_array($k, $textUserFields)
						&& is_string($arCurrentValues[$k])
						&& CBPActivity::isExpression($arCurrentValues[$k]))
					{
						$arCurrentValues["{$k}_text"] = $arCurrentValues[$k];
						unset($arCurrentValues[$k]);
					}
					elseif('UF_TASK_WEBDAV_FILES' == $k && is_array($arCurrentValues[$k]) && CModule::IncludeModule("disk") && \Bitrix\Disk\Configuration::isSuccessfullyConverted())
					{
						foreach($arCurrentValues[$k] as $key => $fileId)
						{
							if(!empty($fileId) && is_string($fileId) && mb_substr($fileId, 0, 1) != 'n')
							{
								$item = \Bitrix\Disk\Internals\FileTable::getList(array(
									'select' => array('ID'),
									'filter' => array('=XML_ID' => $fileId, 'TYPE' => \Bitrix\Disk\Internals\FileTable::TYPE_FILE)
								))->fetch();

								if($item)
								{
									$arCurrentValues[$k][$key] = 'n'.$item['ID'];
								}
							}
						}
						unset($fileId);
					}
				}
			}

			$arCurrentValues["HOLD_TO_CLOSE"] = ($arCurrentActivity["Properties"]["HoldToClose"] ? "Y" : "N");
			$arCurrentValues["AS_CHILD_TASK"] = ($arCurrentActivity["Properties"]["AsChildTask"] ? "Y" : "N");
			$arCurrentValues["AUTO_LINK_TO_CRM_ENTITY"] = ($arCurrentActivity["Properties"]["AUTO_LINK_TO_CRM_ENTITY"] ? "Y" : "N");
			$arCurrentValues["CHECK_LIST_ITEMS"] = $arCurrentActivity["Properties"]["CheckListItems"];
			$arCurrentValues["TIME_ESTIMATE_H"] = $arCurrentActivity["Properties"]["TimeEstimateHour"];
			$arCurrentValues["TIME_ESTIMATE_M"] = $arCurrentActivity["Properties"]["TimeEstimateMin"];
		}
		else
		{
			foreach (static::$arAllowedTasksFieldNames as $field)
			{
				if ((!is_array($arCurrentValues[$field]) && ($arCurrentValues[$field] == '')
					|| is_array($arCurrentValues[$field]) && (count($arCurrentValues[$field]) <= 0))
					&& ($arCurrentValues[$field."_text"] <> ''))
				{
					$arCurrentValues[$field] = $arCurrentValues[$field."_text"];
				}
			}
		}

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, array(
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'workflowParameters' => $arWorkflowParameters,
			'workflowVariables' => $arWorkflowVariables,
			'currentValues' => $arCurrentValues,
			'formName' => $formName,
			'siteId' => $currentSiteId
		));

		$dialog->setRuntimeData(array(
			"formName" => $formName,
			"documentType" => $documentType,
			"popupWindow" => &$popupWindow,
			"arDocumentFields" => self::__GetFields(),
			'currentSiteId' => $currentSiteId,
			'allowedTaskFields' => static::$arAllowedTasksFieldNames,
			'rawValues' => $rawValues
		));

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$errors = [];
		$properties = ["Fields" => []];

		$arTaskPriority = array(0, 1, 2);
		foreach ($arTaskPriority as $k => $v)
		{
			$arTaskPriority[$v] = GetMessage("TASK_PRIORITY_".$v);
		}

		$arGroups = array(GetMessage("TASK_EMPTY_GROUP"));
		if (CModule::IncludeModule("socialnetwork"))
		{
			$db = CSocNetGroup::GetList(array("NAME" => "ASC"), array("ACTIVE" => "Y"), false, false, array("ID", "NAME"));
			while ($ar = $db->GetNext())
			{
				$arGroups[$ar["ID"]] = "[" . $ar["ID"] . "]" . Emoji::decode($ar["NAME"]);
			}
		}

		$arDF = self::__GetFields();

		foreach (static::$arAllowedTasksFieldNames as $field)
		{
			$r = null;

			if (in_array($field, array("CREATED_BY", "RESPONSIBLE_ID", "ACCOMPLICES", "AUDITORS")))
			{
				$value = $arCurrentValues[$field];
				if ($value <> '')
				{
					$arErrorsTmp = array();
					$r = CBPHelper::UsersStringToArray($value, $documentType, $arErrorsTmp);
					if (count($arErrorsTmp) > 0)
					{
						$errors = array_merge($errors, $arErrorsTmp);
					}
				}
			}
			elseif (array_key_exists($field, $arCurrentValues) || array_key_exists($field."_text", $arCurrentValues))
			{
				$arValue = array();
				if (array_key_exists($field, $arCurrentValues))
				{
					$arValue = $arCurrentValues[$field];
					if (!is_array($arValue) || is_array($arValue) && CBPHelper::IsAssociativeArray($arValue))
						$arValue = array($arValue);
				}
				if (array_key_exists($field."_text", $arCurrentValues))
					$arValue[] = $arCurrentValues[$field."_text"];

				foreach ($arValue as $value)
				{
					if($field != 'DESCRIPTION')
					{
						$value = trim($value);
					}

					if (!CBPDocument::IsExpression($value)) // checks if this is constant field?
					{
						if ($field == "PRIORITY")
						{
							if ($value == '')
								$value = null;

							if ($value != null && !array_key_exists($value, $arTaskPriority))
							{
								$value = null;
								$errors[] = array(
									"code" => "ErrorValue",
									"message" => "Priority is empty",
									"parameter" => $field,
								);
							}
						}
						elseif ($field == "GROUP_ID")
						{
							if ($value == '')
								$value = null;
							if ($value != null && !array_key_exists($value, $arGroups))
							{
								$value = null;
								$errors[] = array(
									"code" => "ErrorValue",
									"message" => "Group is empty",
									"parameter" => $field,
								);
							}
						}
						elseif (in_array($field, array("ALLOW_CHANGE_DEADLINE", "TASK_CONTROL", "ADD_IN_REPORT", 'ALLOW_TIME_TRACKING')))
						{
							if (mb_strtoupper($value) == "Y" || $value === true || $value."!" == "1!")
								$value = "Y";
							elseif (mb_strtoupper($value) == "N" || $value === false || $value."!" == "0!")
								$value = "N";
							else
								$value = null;
						}
						else
						{
							if (!is_array($value) && $value == '')
								$value = null;
						}
					}

					if ($value != null)
						$r[] = $value;
				}
			}

			$r_orig = $r;

			if (!in_array($field, array("ACCOMPLICES", "AUDITORS")))
			{
				if ($r && count($r) > 0)
					$r = $r[0];
				else
					$r = null;
			}

			if (in_array($field, array("TITLE", "CREATED_BY", "RESPONSIBLE_ID")) && ($r == null || is_array($r) && count($r) <= 0))
			{
				$errors[] = array(
					"code" => "emptyRequiredField",
					"message" => str_replace("#FIELD#", $arDF[$field]["Name"], GetMessage("BPCDA_FIELD_REQUIED")),
				);
			}

			$properties["Fields"][$field] = $r;

			if (array_key_exists($field."_text", $arCurrentValues) && isset($r_orig[1]))
			{
				$properties["Fields"][$field . '_text'] = $r_orig[1];
			}
		}

		$arUserFields = \Bitrix\Tasks\Util\Userfield\Task::getScheme();
		foreach ($arUserFields as $field)
		{
			$fieldValue = $arCurrentValues[$field["FIELD_NAME"]];
			$fieldValueText = $arCurrentValues["{$field['FIELD_NAME']}_text"];
			if(CBPHelper::isEmptyValue($fieldValue) && !CBPHelper::isEmptyValue($fieldValueText))
			{
				$fieldValue = $fieldValueText;
			}

			if($field["MANDATORY"] == "Y")
			{
				if (($field["MULTIPLE"] == "Y" && (!$fieldValue || CBPHelper::isEmptyValue($fieldValue))) ||
					($field["MULTIPLE"] == "N" && $fieldValue === '' && $field['USER_TYPE_ID'] !== 'boolean'))
				{
					$errors[] = array(
						"code" => "emptyRequiredField",
						"message" => str_replace("#FIELD#", $field["EDIT_FORM_LABEL"], GetMessage("BPCDA_FIELD_REQUIED")),
					);
				}
			}

			$properties["Fields"][$field["FIELD_NAME"]] = $fieldValue;
		}

		$properties["HoldToClose"] = ((mb_strtoupper($arCurrentValues["HOLD_TO_CLOSE"]) == "Y") ? true : false);
		$properties["AsChildTask"] = ((mb_strtoupper($arCurrentValues["AS_CHILD_TASK"]) == "Y") ? 1 : 0);
		$properties["CheckListItems"] = $arCurrentValues["CHECK_LIST_ITEMS"];
		$properties["TimeEstimateHour"] = $arCurrentValues["TIME_ESTIMATE_H"];
		$properties["TimeEstimateMin"] = $arCurrentValues["TIME_ESTIMATE_M"];
		$properties["AUTO_LINK_TO_CRM_ENTITY"] = ((mb_strtoupper($arCurrentValues["AUTO_LINK_TO_CRM_ENTITY"]) == "Y") ? true : false);

		if (count($errors) > 0)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$currentActivity["Properties"] = $properties;

		return true;
	}

	private static function __GetFields()
	{
		if(!CModule::IncludeModule("tasks"))
		{
			return [];
		}

		$arTaskPriority[1] = GetMessage('TASKS_COMMON_NO');
		$arTaskPriority[2] = GetMessage('TASKS_COMMON_YES');

		$arGroups = array(GetMessage("TASK_EMPTY_GROUP"));
		if (CModule::IncludeModule("socialnetwork"))
		{
			$db = CSocNetGroup::GetList(array("NAME" => "ASC"), array("ACTIVE" => "Y"), false, false, array("ID", "NAME"));
			while ($ar = $db->GetNext())
			{
				$arGroups[$ar["ID"]] = "[" . $ar["ID"] . "]" . htmlspecialcharsback(Emoji::decode($ar["NAME"]));
			}
		}

		$arFields = array(
			"TITLE" => array(
				"Name" => GetMessage("BPTA1A_TASKNAME"),
				"Type" => "S",
				"Editable" => true,
				"Required" => true,
				"Multiple" => false,
				"BaseType" => "string"
			),
			"CREATED_BY" => array(
				"Name" => GetMessage("BPTA1A_TASKCREATEDBY"),
				"Type" => "S:UserID",
				"Editable" => true,
				"Required" => true,
				"Multiple" => false,
				"BaseType" => "user"
			),
			"RESPONSIBLE_ID" => array(
				"Name" => GetMessage("BPTA1A_TASKASSIGNEDTO"),
				"Type" => "S:UserID",
				"Editable" => true,
				"Required" => true,
				"Multiple" => false,
				"BaseType" => "user",
				"Settings" => [
					'allowEmailUsers' => true,
				],
			),
			"DESCRIPTION" => array(
				"Name" => GetMessage("BPTA1A_TASKDETAILTEXT"),
				"Type" => "T",
				"Editable" => true,
				"Required" => false,
				"Multiple" => false,
				"BaseType" => "text"
			),
			"ACCOMPLICES" => array(
				"Name" => GetMessage("BPTA1A_TASKACCOMPLICES"),
				"Type" => "S:UserID",
				"Editable" => true,
				"Required" => false,
				"Multiple" => true,
				"BaseType" => "user"
			),
			"START_DATE_PLAN" => array(
				"Name" => GetMessage("BPTA1A_TASKACTIVEFROM"),
				"Type" => "S:DateTime",
				"Editable" => true,
				"Required" => false,
				"Multiple" => false,
				"BaseType" => "datetime"
			),
			"END_DATE_PLAN" => array(
				"Name" => GetMessage("BPTA1A_TASKACTIVETO"),
				"Type" => "S:DateTime",
				"Editable" => true,
				"Required" => false,
				"Multiple" => false,
				"BaseType" => "datetime"
			),
			"DEADLINE" => array(
				"Name" => GetMessage("BPTA1A_TASKDEADLINE"),
				"Type" => "S:DateTime",
				"Editable" => true,
				"Required" => false,
				"Multiple" => false,
				"BaseType" => "datetime"
			),
			"PRIORITY" => array(
				"Name" => GetMessage("BPTA1A_TASKPRIORITY_V3"),
				"Type" => "L",
				"Options" => $arTaskPriority,
				"Editable" => true,
				"Required" => false,
				"Multiple" => false,
				"BaseType" => "select"
			),
			"GROUP_ID" => array(
				"Name" => GetMessage("BPTA1A_TASKGROUPID"),
				"Type" => "L",
				"Options" => $arGroups,
				"Editable" => true,
				"Required" => false,
				"Multiple" => false,
				"BaseType" => "select"
			),
			"ALLOW_CHANGE_DEADLINE" => array(
				"Name" => GetMessage("BPTA1A_CHANGE_DEADLINE"),
				"Type" => "B",
				"Editable" => true,
				"Required" => false,
				"Multiple" => false,
				"BaseType" => "bool"
			),
			"ALLOW_TIME_TRACKING" => array(
				"Name" => GetMessage("BPTA1A_ALLOW_TIME_TRACKING"),
				"Type" => "B",
				"Editable" => true,
				"Required" => false,
				"Multiple" => false,
				"BaseType" => "bool"
			),
			"TASK_CONTROL" => array(
				"Name" => GetMessage("BPTA1A_CHECK_RESULT_V2"),
				"Type" => "B",
				"Editable" => true,
				"Required" => false,
				"Multiple" => false,
				"BaseType" => "bool"
			),
			"AUDITORS" => array(
				"Name" => GetMessage("BPTA1A_TASKTRACKERS"),
				"Type" => "S:UserID",
				"Editable" => true,
				"Required" => false,
				"Multiple" => true,
				"BaseType" => "user"
			),
			"PARENT_ID" => array(
				"Name" => GetMessage('BPTA1A_MAKE_SUBTASK'),
				"Type" => "S",
				"Editable" => true,
				"Required" => false,
				"Multiple" => false,
				"BaseType" => "int"
			)
		);

		$arUserFields = \Bitrix\Tasks\Util\Userfield\Task::getScheme();
		foreach($arUserFields as $field)
		{
			if (in_array($field['USER_TYPE_ID'], array('mail_message')))
			{
				continue;
			}

			$arFields[$field["FIELD_NAME"]] = array(
				"Name" => $field["EDIT_FORM_LABEL"] ?: $field['USER_TYPE']['DESCRIPTION'],
				"Type" => $field["USER_TYPE_ID"],
				"Editable" => true,
				"Required" => ($field["MANDATORY"] == "Y"),
				"Multiple" => ($field["MULTIPLE"] == "Y"),
				"BaseType" => $field["USER_TYPE_ID"] === 'boolean' ? 'bool' : $field['USER_TYPE_ID'],
				"UserField" => $field
			);
		}

		return $arFields;
	}

	private function checkCycling(array $documentId)
	{
		//check tasks robots only.
		if ($documentId[0] !== 'tasks')
		{
			return true;
		}

		$key = $this->GetName();

		if (!isset(self::$cycleCounter[$key]))
		{
			self::$cycleCounter[$key] = 0;
		}

		self::$cycleCounter[$key]++;
		if (self::$cycleCounter[$key] > self::CYCLE_LIMIT)
		{
			$this->WriteToTrackingService(GetMessage("BPSA_CYCLING_ERROR_1"), 0, CBPTrackingType::Error);
			throw new Exception();
		}
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		$fields = self::__GetFields();
		$bpOptions = [
			'HoldToClose' => [
				'Name' => \Bitrix\Main\Localization\Loc::getMessage('BPTA1A_HOLD_TO_CLOSE'),
				'Type' => \Bitrix\Bizproc\FieldType::BOOL,
				'BaseType' => \Bitrix\Bizproc\FieldType::BOOL,
				'Required' => true,
				'Default' => 'N'
			],
		];

		return array_merge($fields, $bpOptions);
	}

	protected function getDebugInfo(array $values = [], array $map = []): array
	{
		$onlyDesignerFields = ['AUTO_LINK_TO_CRM_ENTITY', 'UF_CRM_TASK', 'UF_TASK_WEBDAV_FILES'];
		$mustBeInBPUserStyle = ['CREATED_BY', 'RESPONSIBLE_ID', 'ACCOMPLICES', 'AUDITORS'];

		if (count($map) <= 0)
		{
			$map = static::getPropertiesMap($this->getDocumentType());
		}

		$fields = $this->Fields;

		foreach ($map as $key => $property)
		{
			if (array_key_exists($key, $values))
			{
				// hack
				if ($key === 'PRIORITY')
				{
					$map[$key]['BaseType'] = 'bool';
					$values[$key]= ((int)$values['PRIORITY'] === 2);
				}

				// temporary
				if (in_array($key, $onlyDesignerFields))
				{
					unset($map[$key]);
				}

				//hack
				if (in_array($key, $mustBeInBPUserStyle))
				{
					$values[$key] = $fields[$key];
				}

				continue;
			}

			unset($map[$key]);
		}

		return parent::getDebugInfo($values, $map);
	}
}
