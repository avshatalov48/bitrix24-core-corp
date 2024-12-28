<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Bizproc\FieldType;
use Bitrix\Bizproc\Result\ResultDto;
use Bitrix\Crm\Activity\Provider\Tasks\Task;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Emoji;
use Bitrix\Tasks;

class CBPTask2Activity extends CBPActivity implements
	IBPEventActivity,
	IBPActivityExternalEventListener,
	IBPEventDrivenActivity
{
	private $isInEventActivityMode = false;
	private static $cycleCounter = [];
	const CYCLE_LIMIT = 3;

	private static array $arAllowedTasksFieldNames = [
		'TITLE',
		'CREATED_BY',
		'RESPONSIBLE_ID',
		'ACCOMPLICES',
		'START_DATE_PLAN',
		'END_DATE_PLAN',
		'DEADLINE',
		'DESCRIPTION',
		'PRIORITY',
		'GROUP_ID',
		'FLOW_ID',
		'ALLOW_CHANGE_DEADLINE',
		'TASK_CONTROL',
		'ADD_IN_REPORT',
		'AUDITORS',
		'ALLOW_TIME_TRACKING',
		'PARENT_ID',
		'TAG_NAMES',
		'REQUIRED_RESULT',
		'DEPENDS_ON',
	];

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
			'ClosedBy' => ['Type' => 'user'],
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
		$this->HoldToClose = CBPHelper::getBool($this->HoldToClose);
		$this->AUTO_LINK_TO_CRM_ENTITY = CBPHelper::getBool($this->AUTO_LINK_TO_CRM_ENTITY);
		$this->AsChildTask = CBPHelper::getBool($this->AsChildTask);
		$this->REQUIRED_RESULT = CBPHelper::getBool($this->REQUIRED_RESULT);

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

		if (!isset($fields['SITE_ID']) || !$fields["SITE_ID"])
		{
			$fields["SITE_ID"] = SITE_ID;
		}

		if (!is_array($fields['UF_CRM_TASK'] ?? null))
		{
			$fields['UF_CRM_TASK'] = isset($fields['UF_CRM_TASK']) ? [$fields['UF_CRM_TASK']] : [];
		}
		if ($this->AUTO_LINK_TO_CRM_ENTITY && $documentId[0] === 'crm' && CModule::IncludeModule('crm'))
		{
			$documentId = $this->GetDocumentId();
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
					[], ['ID' => (int)$fields['PARENT_ID'], 'CHECK_PERMISSIONS' => 'N'], ['GROUP_ID']
				);
				if ($res && ($task = $res->fetch()))
				{
					$fields['GROUP_ID'] = $task['GROUP_ID'];
				}
			}
		}
		elseif (!$this->AsChildTask && CBPHelper::isEmptyValue($fields['PARENT_ID']) === false)
		{
			$parentId = is_array($fields['PARENT_ID']) ? reset($fields['PARENT_ID']) : $fields['PARENT_ID'];
			if (
				!is_numeric($parentId)
				|| is_null(\Bitrix\Tasks\Integration\Bizproc\Document\Task::getDocument($parentId))
			)
			{
				$parentIdModifiedProperty = $logMap['PARENT_ID'];
				$parentIdModifiedProperty['BaseType'] = 'string';
				if ($this->workflow->isDebug())
				{
					$this->writeDebugInfo(
						$this->getDebugInfo(['PARENT_ID' => $parentId], ['PARENT_ID' => $parentIdModifiedProperty])
					);
				}

				$this->WriteToTrackingService(
					Loc::getMessage('BPTA1A_TASK_TASK_PRESENCE_ERROR', ['#TASK_ID#' => $parentId]),
					0,
					CBPTrackingType::Error
				);

				return false;
			}
			elseif (empty($fields['GROUP_ID']))
			{
				$res = \CTasks::GetList(
					[], ['ID' => (int)$parentId, 'CHECK_PERMISSIONS' => 'N'], ['GROUP_ID']
				);
				if ($res && ($task = $res->fetch()))
				{
					$fields['GROUP_ID'] = $task['GROUP_ID'];
				}
			}
			$fields['PARENT_ID'] = (int)$parentId;
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
		$rawFields = $this->getRawProperty('Fields');
		foreach (array_keys($fields) as $fieldName)
		{
			if (
				in_array($fieldName, static::$arAllowedTasksFieldNames, true)
				|| \Bitrix\Tasks\Util\Userfield::isUFKey($fieldName)
			)
			{
				if ($fieldName === 'UF_TASK_WEBDAV_FILES' && Loader::includeModule('disk'))
				{
					$canUseDisk = \Bitrix\Disk\Configuration::isSuccessfullyConverted();

					if ($canUseDisk && $this->canUploadFilesToDisk($rawFields[$fieldName]))
					{
						$fields[$fieldName] = $this->uploadFilesToDisk($fields[$fieldName], $fields['CREATED_BY']);
					}
					elseif (is_array($fields[$fieldName]))
					{
						$filesToUpload = [];
						foreach ($fields[$fieldName] as $key => $fileId)
						{
							if ($canUseDisk && !empty($fileId))
							{
								$diskFile = \Bitrix\Disk\File::load(['XML_ID' => (string)$fileId]);

								if ((!is_string($fileId) || mb_substr($fileId, 0, 1) !== 'n') && is_null($diskFile))
								{
									$filesToUpload[] = $fileId;
									unset($fields[$fieldName][$key]);
								}
								elseif (is_string($fileId) && mb_substr($fileId, 0, 1) !== 'n')
								{
									$fields[$fieldName][$key] = 'n' . $diskFile->getId();
								}
							}
						}

						$fields[$fieldName] = array_merge(
							$fields[$fieldName],
							$this->uploadFilesToDisk($filesToUpload, $fields['CREATED_BY'])
						);
					}
				}

				$arFieldsChecked[$fieldName] = $fields[$fieldName];
			}
		}

		if (isset($arFieldsChecked['TAG_NAMES']))
		{
			if (is_array($arFieldsChecked['TAG_NAMES']))
			{
				$arFieldsChecked['TAGS'] = [];
				foreach ($arFieldsChecked['TAG_NAMES'] as $tagName)
				{
					if (is_numeric($tagName))
					{
						$arFieldsChecked['TAGS'][] = (string)$tagName;
					}
					elseif (is_string($tagName))
					{
						$arFieldsChecked['TAGS'][] = $tagName;
					}
				}
			}

			unset($arFieldsChecked['TAG_NAMES']);
		}

		if (empty($arFieldsChecked['CREATED_BY']))
		{
			if ($this->workflow->isDebug())
			{
				$this->writeDebugInfo(
					$this->getDebugInfo(
						['CREATED_BY' => $arFieldsChecked['CREATED_BY']], ['CREATED_BY' => $logMap['CREATED_BY']]
					)
				);
			}

			$this->WriteToTrackingService(
				Loc::getMessage("BPSA_CREATED_BY_ERROR"),
				0,
				CBPTrackingType::Error
			);

			return false;
		}

		if (isset($arFieldsChecked['GROUP_ID']))
		{
			$arFieldsChecked['GROUP_ID'] = (int)CBPHelper::stringify($arFieldsChecked['GROUP_ID']);
		}

		if (!CBPHelper::isEmptyValue($rawFields['FLOW_ID']))
		{
			$arFieldsChecked['FLOW_ID'] = (int)CBPHelper::stringify($arFieldsChecked['FLOW_ID']);
			if ($arFieldsChecked['FLOW_ID'] <= 0)
			{
				$this->WriteToTrackingService(
					Loc::getMessage('BPTA1A_TASK_FLOW_PRESENCE_ERROR'),
					0,
					CBPTrackingType::Error
				);

				return false;
			}
		}

		$allDateFields = array_merge(
			['DEADLINE', 'END_DATE_PLAN', 'START_DATE_PLAN'],
			\Bitrix\Tasks\Integration\Bizproc\Document\Task::getFieldsCreatedByUser('datetime')
		);
		foreach ($allDateFields as $dateField)
		{
			$dateFieldName = is_array($dateField) ? $dateField['Name'] : $dateField;
			$checkedDateField = $this->assertDateField($dateField, $arFieldsChecked[$dateFieldName] ?? null);

			// In some cases (crm), we got time in user timezone
			if ($dateFieldName === 'DEADLINE')
			{
				$deadlineValue = $arFieldsChecked[$dateFieldName] ?? null;
				if ($deadlineValue instanceof \Bitrix\Bizproc\BaseType\Value\DateTime)
				{
					$checkedDateField = date($deadlineValue->getFormat(), $deadlineValue->getTimestamp());
				}
			}

			if (!$checkedDateField)
			{
				unset($arFieldsChecked[$dateFieldName]);
			}
			else
			{
				$arFieldsChecked[$dateFieldName] = $checkedDateField;
			}
		}

		$this->checkPlanDates($arFieldsChecked);
		$this->checkSeParameter($arFieldsChecked);

		if ($fields['ALLOW_TIME_TRACKING'] === 'Y')
		{
			$arFieldsChecked['TIME_ESTIMATE'] = (int)$this->TimeEstimateHour * 3600 + (int)$this->TimeEstimateMin * 60;
		}

		$prevOccurAsUserId = \Bitrix\Tasks\Util\User::getOccurAsId(); // null or positive integer
		\Bitrix\Tasks\Util\User::setOccurAsId($arFieldsChecked['CREATED_BY']);

		$result = false;
		$task = null;
		$errors = [];
		try
		{
			if ($this->workflow->isDebug())
			{
				$this->writeDebugInfo(
					$this->getDebugInfo(array_merge($arFieldsChecked, ['HoldToClose' => $this->HoldToClose]))
				);
			}

			// todo: use \Bitrix\Tasks\Item\Task here
			$task = CTaskItem::add(
				$arFieldsChecked, \Bitrix\Tasks\Util\User::getAdminId(), [
									'SPAWNED_BY_WORKFLOW' => true,
									'SKIP_TIMEZONE' => [
										'DEADLINE',
									],
								]
			);
			$result = $task->getId();
		}
		catch (TasksException $e)
		{
			// todo: incapsulate this
			if ($e->checkOfType(TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE))
			{
				$errors = unserialize($e->getMessage(), ['allowed_classes' => false]);
			}
		}

		\Bitrix\Tasks\Util\User::setOccurAsId($prevOccurAsUserId);

		if (!$result)
		{
			if (count($errors) > 0)
			{
				$errorDesc = [];
				if (is_array($errors) && !empty($errors))
				{
					foreach ($errors as $error)
					{
						$errorDesc[] = $error['text'] . ' (' . $error['id'] . ')';
					}
				}

				$this->WriteToTrackingService(
					html_entity_decode(
						Loc::getMessage("BPSA_TRACK_ERROR") . (!empty($errorDesc) ? ' ' . implode(', ', $errorDesc)
							: '')
					),
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
				if ($this->workflow->isDebug())
				{
					$map = $this->getDebugInfo(
						['CHECK_LIST_ITEMS' => $checkListItems], [
																   "CHECK_LIST_ITEMS" => [
																	   "Name" => Loc::getMessage(
																		   "BPSA_CHECK_LIST_ITEMS"
																	   ),
																	   "Type" => "string",
																	   "Required" => false,
																	   "Multiple" => true,
																   ],
															   ]
					);
					$this->writeDebugInfo($map);
				}
			}
		}

		$this->TaskId = $result;
		$this->markAsBPTask($result);
		$this->WriteToTrackingService(str_replace("#VAL#", $result, Loc::getMessage("BPSA_TRACK_OK")));

		if (
			$this->TaskId
			&& (bool)\Bitrix\Main\Config\Option::get('bizproc', 'release_preview_2024')
			&& method_exists($this, 'fixResult')
		)
		{
			$this->fixResult($this->makeResultFromId($this->TaskId));
		}

		if ($this->workflow->isDebug())
		{
			$this->logDebugTaskUrl($result);
		}

		return $result > 0;
	}

	public function makeResultFromId(int $id): ResultDto
	{
		$resultDocumentId = Tasks\Integration\Bizproc\Document\Task::resolveDocumentId($id);
		$resultDocumentType = $resultDocumentId;
		array_pop($resultDocumentType);
		$resultDocumentType[] = 'TASK';
		$resultValue = [
			'DOCUMENT_ID' => $resultDocumentId,
			'DOCUMENT_TYPE' => $resultDocumentType,
		];

		return new ResultDto(get_class($this), $resultValue);
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

	protected function assertDateField($dateField, $dateFieldValue)
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
				$checkedField = $this->assertDateField($dateField, $date);
				if(is_array($checkedField))
				{
					$realDateField = array_merge($realDateField, $checkedField);
				}
				elseif($checkedField)
				{
					$realDateField[] = $checkedField;
				}
			}

			if (is_array($realDateField) && (is_string($dateField) || empty($dateField['Multiple'])))
			{
				return current($realDateField);
			}

			return $realDateField;
		}

		if (!empty($dateFieldValue) && !CheckDateTime($dateFieldValue))
		{
			$name = is_array($dateField) ? $dateField['Name'] : $dateField;
			$this->WriteToTrackingService(
				'Incorrect ' . $name . ': ' . $dateFieldValue,
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

	private function checkSeParameter(&$fields): void
	{
		$parameters = [];
		if (isset($fields['REQUIRED_RESULT']))
		{
			$parameters[\Bitrix\Tasks\Internals\Task\ParameterTable::PARAM_RESULT_REQUIRED] = [
				'VALUE' => CBPHelper::getBool($fields['REQUIRED_RESULT']) ? 'Y' : 'N',
				'CODE' => \Bitrix\Tasks\Internals\Task\ParameterTable::PARAM_RESULT_REQUIRED,
				'ID' => '0',
			];

			unset($fields['REQUIRED_RESULT']);
		}

		if ($parameters)
		{
			$fields['SE_PARAMETER'] = $parameters;
		}
	}

	protected function markAsBPTask(int $taskId): void
	{
		if(!CModule::IncludeModule('crm'))
		{
			return;
		}

		$splitDocumentId = mb_split('_(?=[^_]*$)', $this->GetDocumentId()[2]);

		if (!is_array($splitDocumentId) || count($splitDocumentId) < 2)
		{
			return;
		}

		[$documentType, $documentId] = $splitDocumentId;

		$documentStage = $this->getDocumentStage($documentType);

		if($documentStage !== '')
		{
			$activity = CCrmActivity::GetList(
				[],
				[
					'CHECK_PERMISSIONS' => 'N',
					'OWNER_ID' => $documentId,
					'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($documentType),
					'TYPE_ID' => CCrmActivityType::Task,
					'ASSOCIATED_ENTITY_ID' => $taskId,
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
			else
			{
				$activity = CCrmActivity::GetList(
					[],
					[
						'CHECK_PERMISSIONS' => 'N',
						'OWNER_ID' => $documentId,
						'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($documentType),
						'TYPE_ID' => CCrmActivityType::Provider,
						'PROVIDER_ID' => Task::getId(),
						'PROVIDER_TYPE_ID' => Task::getProviderTypeId(),
						'ASSOCIATED_ENTITY_ID' => $taskId,
					],
					false,
					false,
					['ID', 'SETTINGS']
				)->Fetch();
				if ($activity)
				{
					$settings = array_merge(['OWNER_STAGE' => $documentStage], $activity['SETTINGS']);
					CCrmActivity::Update(
						$activity['ID'],
						['SETTINGS' => $settings],
						false,
						false
					);
				}
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
			case CCrmOwnerType::OrderName:
				return $document['STATUS_ID'] ?? '';

			case CCrmOwnerType::DealName:
			case CCrmOwnerType::SmartDocumentName:
			case CCrmOwnerType::SmartInvoiceName:
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
		$this->WriteToTrackingService(Loc::getMessage("BPSA_TRACK_SUBSCR"));
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
				$this->WriteToTrackingService(Loc::getMessage("BPSA_TRACK_DELETED"));
				return true;
			}
			elseif ($arEventParameters[1]["STATUS"] == 5)
			{
				$this->ClosedBy = "user_".$arEventParameters[1]["CLOSED_BY"];
				$this->ClosedDate = $arEventParameters[1]["CLOSED_DATE"];

				$this->WriteToTrackingService(str_replace("#DATE#", $arEventParameters[1]["CLOSED_DATE"], Loc::getMessage("BPSA_TRACK_CLOSED")));

				return !$this->isAutoCompleted($arEventParameters);
			}
		}
	}

	private function isAutoCompleted(array $params): bool
	{
		if ($this->getRootActivity()->getDocumentEventType() !== \CBPDocumentEventType::Automation)
		{
			return false; //skip none-automation
		}

		if(!CModule::IncludeModule('crm'))
		{
			return false;
		}

		[$typeId, $id] = \CCrmBizProcHelper::resolveEntityId($this->getDocumentId());

		if ($typeId)
		{
			$activity = CCrmActivity::GetList(
				[],
				[
					'OWNER_ID' => $id,
					'OWNER_TYPE_ID' => $typeId,
					'@PROVIDER_ID' => [Task::getId(), \Bitrix\Crm\Activity\Provider\Tasks\Task::getProviderTypeId()],
					'ASSOCIATED_ENTITY_ID' => $params[0],
					'STATUS' => CCrmActivityStatus::AutoCompleted,
				],
				false,
				false,
				['ID']
			)->fetch();

			if ($activity)
			{
				return true;
			}
		}

		return false;
	}

	public static function getPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null, $currentSiteId = null)
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return false;
		}

		$dialog = new PropertiesDialog(__FILE__, array(
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'workflowParameters' => $arWorkflowParameters,
			'workflowVariables' => $arWorkflowVariables,
			'currentValues' => $arCurrentValues,
			'formName' => $formName,
			'siteId' => $currentSiteId,
		));

		$map = static::getPropertiesDialogMap();
		foreach ($map['Fields']['Map'] as &$field)
		{
			if ($field['Type'] === FieldType::USER && $field['Required'])
			{
				$field['Default'] = \Bitrix\Bizproc\Automation\Helper::getResponsibleUserExpression($documentType);
			}
		}

		unset($field);
		$dialog->setMap($map);

		$currentValues = $dialog->getCurrentValues();

		// compatibility
		foreach ($map as $field)
		{
			$fieldName = $field['FieldName'];
			if (isset($currentValues[$fieldName], $field['Type']) && $field['Type'] === FieldType::BOOL)
			{
				$currentValues[$fieldName] = CBPHelper::getBool($currentValues[$fieldName]) ? 'Y' : 'N';
			}
		}

		$dialog->setCurrentValues($currentValues);

		$runtimeData = [
			'tags' => [],
			'dependsOn' => [],
		];
		if (isset($currentValues['Fields']))
		{
			if (isset($currentValues['Fields']['TAG_NAMES']) && $currentValues['Fields']['TAG_NAMES'])
			{
				$runtimeData['tags'] = static::getTagsByNames($currentValues['Fields']['TAG_NAMES']);
			}
			if (isset($currentValues['Fields']['DEPENDS_ON']) && $currentValues['Fields']['DEPENDS_ON'])
			{
				$runtimeData['dependsOn'] = static::getDependsOnByTaskIds($currentValues['Fields']['DEPENDS_ON']);
			}
		}

		$dialog->setRuntimeData($runtimeData);

		return $dialog;
	}

	private static function getTagsByNames(array $tagNames): array
	{
		if (!$tagNames)
		{
			return [];
		}

		$tagsIterator = \Bitrix\Tasks\Internals\Task\LabelTable::getList([
			'select' => ['ID', 'NAME'],
			'filter' => ['@NAME' => $tagNames],
		]);

		$knownTags = [];
		while ($tag = $tagsIterator->fetchObject())
		{
			$knownTags[$tag->getName()] = $tag;
		}

		$tags = [];
		foreach ($tagNames as $name)
		{
			if (CBPDocument::isExpression($name))
			{
				$tags[] = [
					'type' => 'expression',
					'name' => $name,
				];
			}
			elseif (isset($knownTags[$name]))
			{
				$tags[] = [
					'type' => 'simple',
					'id' => $knownTags[$name]->getId(),
					'name' => $name,
				];
			}
			else
			{
				$tags[] = [
					'type' => 'new',
					'name' => $name,
				];
			}
		}

		return $tags;
	}

	public static function getDependsOnByTaskIds(array $taskIds): array
	{
		$dependsOn = [];

		foreach ($taskIds as $id)
		{
			$dependsOn[] = CBPDocument::isExpression($id) ? $id : (int)$id;
		}

		return $dependsOn;
	}

	public static function GetPropertiesDialogValues(
		$documentType,
		$activityName,
		&$workflowTemplate,
		&$workflowParameters,
		&$workflowVariables,
		$currentValues,
		&$errors
	)
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return false;
		}

		$errors = [];
		$properties = ['Fields' => []];

		$documentService = CBPRuntime::getRuntime()->getDocumentService();

		if (isset($currentValues['FLOW_ID'])
			&& !CBPHelper::isEmptyValue($currentValues['FLOW_ID'])
		)
		{
			foreach ((new \Bitrix\Tasks\Flow\Control\Task\Field\FlowFieldHandler(0))->getModifiedFields() as $field)
			{
				$currentValues[$field] = null;
			}
		}


		$map = static::getPropertiesDialogMap();
		foreach ($map['Fields']['Map'] as $taskFieldId => $taskField)
		{
			$field = $documentService->getFieldTypeObject($documentType, $taskField);
			if (!$field)
			{
				if (mb_substr($taskFieldId, 0, 3) === 'UF_')
				{
					$extractResult = static::extractUserField($taskField, $currentValues);
					if ($extractResult->isSuccess())
					{
						$properties['Fields'][$taskFieldId] = $extractResult->getData()['value'];
					}
					else
					{
						foreach ($extractResult->getErrors() as $extractError)
						{
							$errors[] = [
								'code' => $extractError->getCode(),
								'message' => $extractError->getMessage(),
								'parameter' => $taskField,
							];
						}
					}
				}
				continue;
			}

			$properties['Fields'][$taskFieldId] = $field->extractValue(
				['Field' => $taskField['FieldName']],
				$currentValues,
				$errors,
			);
		}

		$simpleMap = $map;
		unset($simpleMap['Fields']);
		foreach ($simpleMap as $fieldId => $fieldProperty)
		{
			$field = $documentService->getFieldTypeObject($documentType, $fieldProperty);
			if (!$field)
			{
				continue;
			}

			$properties[$fieldId] = $field->extractValue(
				['Field' => $fieldProperty['FieldName']],
				$currentValues,
				$errors,
			);
		}

		if (!$errors)
		{
			$errors = array_merge($errors, static::validateProperties($properties));
		}
		if ($errors)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
		$currentActivity['Properties'] = $properties;

		return true;
	}

	private static function extractUserField(array $field, array $currentValues): \Bitrix\Main\Result
	{
		$fieldValue = $currentValues[$field['FieldName']] ?? null;
		$fieldValueText = $currentValues["{$field['FieldName']}_text"] ?? null;

		if(CBPHelper::isEmptyValue($fieldValue) && !CBPHelper::isEmptyValue($fieldValueText))
		{
			$fieldValue = $fieldValueText;
		}

		$result = new \Bitrix\Main\Result();

		$result->setData(['value' => $fieldValue]);

		if($field['Required'])
		{
			if (
				($field['Multiple'] && (!$fieldValue || CBPHelper::isEmptyValue($fieldValue)))
				|| (!$field['Multiple'] && $fieldValue === '' && $field['Type'] !== FieldType::BOOL))
			{
				$result->addError(
					new \Bitrix\Main\Error(
						Loc::getMessage(
							'BPSNMA_EMPTY_REQUIRED_PROPERTY',
							['#PROPERTY_NAME#' => $field['Name']]
						)
					)
				);
			}
		}

		return $result;
	}

	public static function validateProperties($testProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];

		$testProperties = array_merge($testProperties['Fields'] ?? [], $testProperties);

		$map = static::getPropertiesDialogMap();
		$map = array_merge($map['Fields']['Map'], $map);
		unset($map['Fields']);

		foreach ($map as $propertyKey => $fieldProperties)
		{
			if(
				\CBPHelper::getBool($fieldProperties['Required'] ?? false)
				&& \CBPHelper::isEmptyValue($testProperties[$propertyKey] ?? null)
			)
			{
				if (
					$propertyKey === 'RESPONSIBLE_ID'
					&& !\CBPHelper::isEmptyValue($testProperties['FLOW_ID'] ?? null)
				)
				{
					continue;
				}
				$errors[] = [
					'code' => 'NotExist',
					'parameter' => 'FieldValue',
					'message' => Loc::getMessage(
						'BPSNMA_EMPTY_REQUIRED_PROPERTY',
						['#PROPERTY_NAME#' => $fieldProperties['Name']]
					),
				];
			}
		}

		return array_merge($errors, parent::validateProperties($testProperties, $user));
	}

	private static function getPropertiesDialogMap(): array
	{
		return [
			'Fields' => [
				'FieldName' => 'Fields',
				'Map' => static::getTaskFieldsMap(),
				'Getter' => function($dialog, $property, $currentActivity, $compatible) {
					$fields = $currentActivity['Properties']['Fields'];
					$files = $fields['UF_TASK_WEBDAV_FILES'] ?? null;

					if(
						isset($files)
						&& is_array($files)
						&& Loader::includeModule('disk')
						&& \Bitrix\Disk\Configuration::isSuccessfullyConverted()
					)
					{
						foreach($files as $key => $fileId)
						{
							if(!empty($fileId) && is_string($fileId) && mb_substr($fileId, 0, 1) != 'n')
							{
								$item = \Bitrix\Disk\Internals\FileTable::getList([
									'select' => ['ID'],
									'filter' => [
										'=XML_ID' => $fileId,
										'TYPE' => \Bitrix\Disk\Internals\FileTable::TYPE_FILE,
									],
								])->fetchObject();

								if($item)
								{
									$files[$key] = 'n' . $item->getId();
								}
							}
						}

						$fields['UF_TASK_WEBDAV_FILES'] = $files;
					}

					return $fields;
				},
			],
			'HoldToClose' => [
				'Name' => Loc::getMessage('BPTA1A_HOLD_TO_CLOSE'),
				'FieldName' => 'HOLD_TO_CLOSE',
				'Type' => FieldType::BOOL,
				'Required' => true,
				'Default' => false,
			],
			'AUTO_LINK_TO_CRM_ENTITY' => [
				'Name' => Loc::getMessage('BPTA1A_FIELD_NAME_AUTO_LINK_TO_CRM_ENTITY'),
				'FieldName' => 'AUTO_LINK_TO_CRM_ENTITY',
				'Type' => FieldType::BOOL,
				'Default' => false,
			],
			'AsChildTask' => [
				'Name' => Loc::getMessage('BPTA1A_FIELD_NAME_AS_CHILD_TASK'),
				'FieldName' => 'AS_CHILD_TASK',
				'Type' => FieldType::BOOL,
				'Default' => false,
			],
			'CheckListItems' => [
				'Name' => Loc::getMessage('BPSA_CHECK_LIST_ITEMS'),
				'FieldName' => 'CHECK_LIST_ITEMS',
				'Type' => FieldType::STRING,
				'Multiple' => true,
			],
			'TimeEstimateHour' => [
				'Name' => Loc::getMessage('BPTA1A_TIME_TRACKING_H'),
				'FieldName' => 'TIME_ESTIMATE_H',
				'Type' => FieldType::INT,
			],
			'TimeEstimateMin' => [
				'Name' => Loc::getMessage('BPTA1A_TIME_TRACKING_M'),
				'FieldName' => 'TIME_ESTIMATE_M',
				'Type' => FieldType::INT,
			],
		];
	}

	private static function getTaskFieldsMap(): array
	{
		if (!Loader::includeModule('tasks'))
		{
			return [];
		}

		$fields = [
			'TITLE' => [
				'Name' => Loc::getMessage('BPTA1A_TASKNAME'),
				'FieldName' => 'TITLE',
				'Type' => \Bitrix\Bizproc\FieldType::STRING,
				'Editable' => true,
				'Required' => true,
				'Multiple' => false,
			],
			'CREATED_BY' => [
				'Name' => Loc::getMessage('BPTA1A_TASKCREATEDBY'),
				'FieldName' => 'CREATED_BY',
				'Type' => FieldType::USER,
				'Editable' => true,
				'Required' => true,
				'Multiple' => false,
				'Default' => 'author',
				'Settings' => [
					'allowEmailUsers' => true,
				],
			],
			'RESPONSIBLE_ID' => [
				'Name' => Loc::getMessage('BPTA1A_TASKASSIGNEDTO_V2'),
				'FieldName' => 'RESPONSIBLE_ID',
				'Type' => FieldType::USER,
				'Editable' => true,
				'Required' => true,
				'Multiple' => false,
				'Default' => 'author',
				'Settings' => [
					'allowEmailUsers' => true,
				],
			],
			'DESCRIPTION' => [
				'Name' => Loc::getMessage('BPTA1A_TASKDETAILTEXT'),
				'FieldName' => 'DESCRIPTION',
				'Type' => FieldType::TEXT,
				'Editable' => true,
				'Required' => false,
				'Multiple' => false,
			],
			'ACCOMPLICES' => [
				'Name' => Loc::getMessage('BPTA1A_TASKACCOMPLICES'),
				'FieldName' => 'ACCOMPLICES',
				'Type' => FieldType::USER,
				'Editable' => true,
				'Required' => false,
				'Multiple' => true,
				'Settings' => [
					'allowEmailUsers' => true,
				],
			],
			'START_DATE_PLAN' => [
				'Name' => Loc::getMessage('BPTA1A_TASKACTIVEFROM'),
				'FieldName' => 'START_DATE_PLAN',
				'Type' => FieldType::DATETIME,
				'Editable' => true,
				'Required' => false,
				'Multiple' => false,
			],
			'END_DATE_PLAN' => [
				'Name' => Loc::getMessage('BPTA1A_TASKACTIVETO'),
				'FieldName' => 'END_DATE_PLAN',
				'Type' => FieldType::DATETIME,
				'Editable' => true,
				'Required' => false,
				'Multiple' => false,
			],
			'DEADLINE' => [
				'Name' => Loc::getMessage('BPTA1A_TASKDEADLINE'),
				'FieldName' => 'DEADLINE',
				'Type' => FieldType::DATETIME,
				'Editable' => true,
				'Required' => false,
				'Multiple' => false,
			],
			'PRIORITY' => [
				'Name' => Loc::getMessage('BPTA1A_TASKPRIORITY_V3'),
				'FieldName' => 'PRIORITY',
				'Type' => FieldType::SELECT,
				'Options' => [
					Tasks\Internals\Task\Priority::AVERAGE => Loc::getMessage('TASKS_COMMON_NO'),
					Tasks\Internals\Task\Priority::HIGH => Loc::getMessage('TASKS_COMMON_YES'),
				],
				'Editable' => true,
				'Required' => false,
				'Multiple' => false,
			],
			'GROUP_ID' => [
				'Name' => Loc::getMessage('BPTA1A_TASKGROUPID'),
				'FieldName' => 'GROUP_ID',
				'Type' => FieldType::SELECT,
				'Options' => static::fetchTaskGroups(),
				'Editable' => true,
				'Required' => false,
				'Multiple' => false,
				'Default' => 0,
			],
			'FLOW_ID' => [
				'Name' => Loc::getMessage('BPTA1A_TASK_FLOW_ID'),
				'FieldName' => 'FLOW_ID',
				'Type' => FieldType::SELECT,
				'Options' => static::fetchTaskFlows(),
				'Editable' => true,
				'Required' => false,
				'Multiple' => false,
				'Default' => 0,
			],
			'ALLOW_CHANGE_DEADLINE' => [
				'Name' => Loc::getMessage('BPTA1A_CHANGE_DEADLINE_MSGVER_1'),
				'FieldName' => 'ALLOW_CHANGE_DEADLINE',
				'Type' => FieldType::BOOL,
				'Editable' => true,
				'Required' => false,
				'Multiple' => false,
				'Default' => 'Y',
				'Settings' => [
					'display' => 'checkbox',
				],
			],
			'ALLOW_TIME_TRACKING' => [
				'Name' => Loc::getMessage('BPTA1A_ALLOW_TIME_TRACKING'),
				'FieldName' => 'ALLOW_TIME_TRACKING',
				'Type' => FieldType::BOOL,
				'Editable' => true,
				'Required' => false,
				'Multiple' => false,
				'Default' => 'Y',
				'Settings' => [
					'display' => 'checkbox',
				],
			],
			'TASK_CONTROL' => [
				'Name' => Loc::getMessage('BPTA1A_CHECK_RESULT_V2'),
				'FieldName' => 'TASK_CONTROL',
				'Type' => FieldType::BOOL,
				'Editable' => true,
				'Required' => false,
				'Multiple' => false,
				'Default' => 'Y',
				'Settings' => [
					'display' => 'checkbox',
				],
			],
			'AUDITORS' => [
				'Name' => Loc::getMessage('BPTA1A_TASKTRACKERS'),
				'FieldName' => 'AUDITORS',
				'Type' => FieldType::USER,
				'Editable' => true,
				'Required' => false,
				'Multiple' => true,
				'Settings' => [
					'allowEmailUsers' => true,
				],
			],
			'PARENT_ID' => [
				'Name' => Loc::getMessage('BPTA1A_MAKE_SUBTASK'),
				'FieldName' => 'PARENT_ID',
				'Type' => FieldType::INT,
				'Editable' => true,
				'Required' => false,
				'Multiple' => false,
			],
			'TAG_NAMES' => [
				'Name' => Loc::getMessage('BPTA1A_FIELD_NAME_TAGS'),
				'FieldName' => 'TAG_NAMES',
				'Type' => FieldType::STRING,
				'Editable' => true,
				'Required' => false,
				'Multiple' => true,
			],
			'REQUIRED_RESULT' => [
				'Name' => Loc::getMessage('BPTA1A_REQUIRE_RESULT'),
				'FieldName' => 'REQUIRED_RESULT',
				'Type' => FieldType::BOOL,
				'Editable' => true,
				'Required' => false,
				'Multiple' => false,
				'Default' => 'N',
				'Settings' => [
					'display' => 'checkbox',
				],
			],
			'DEPENDS_ON' => [
				'Name' => Loc::getMessage('BPTA1A_FIELD_NAME_DEPENDS_ON'),
				'FieldName' => 'DEPENDS_ON',
				'Type' => FieldType::INT,
				'Editable' => true,
				'Multiple' => true,
			],
		];

		foreach (\Bitrix\Tasks\Util\Userfield\Task::getScheme() as $field)
		{
			if ($field['USER_TYPE_ID'] === 'mail_message')
			{
				continue;
			}

			$fields[$field['FIELD_NAME']] = [
				'Name' => $field['EDIT_FORM_LABEL'] ?: $field['USER_TYPE']['DESCRIPTION'],
				'FieldName' => $field['FIELD_NAME'],
				'Type' => $field['USER_TYPE_ID'] === 'boolean' ? FieldType::BOOL : $field['USER_TYPE_ID'],
				'Editable' => true,
				'Required' => ($field['MANDATORY'] == 'Y'),
				'Multiple' => ($field['MULTIPLE'] == 'Y'),
				'BaseType' => $field['USER_TYPE_ID'] === 'boolean' ? 'bool' : $field['USER_TYPE_ID'],
				'UserField' => $field,
			];
		}

		return $fields;
	}

	private static function fetchTaskGroups(): array
	{
		$groups = [Loc::getMessage('TASK_EMPTY_GROUP')];

		if (Loader::includeModule('socialnetwork'))
		{
			$groupIterator = CSocNetGroup::GetList(
				['NAME' => 'ASC'],
				['ACTIVE' => 'Y'],
				false,
				false,
				['ID', 'NAME']
			);
			while ($group = $groupIterator->GetNext())
			{
				$groups[$group['ID']] = "[{$group['ID']}]" . htmlspecialcharsback(Emoji::decode($group['NAME']));
			}
		}

		return $groups;
	}
	private static function fetchTaskFlows(): array
	{
		$flows = [];

		$provider = new \Bitrix\Tasks\Flow\Provider\FlowProvider();
		$query = new \Bitrix\Tasks\Flow\Provider\Query\ExpandedFlowQuery();

		$query
			->setSelect(['ID', 'NAME'])
			->whereActive(true)
			->setAccessCheck(false);

		foreach ($provider->getList($query) as $flow)
		{
			$flowId = (string)$flow->getId();
			$flows[$flowId] = "[{$flowId}]" . htmlspecialcharsbx($flow->getName());
		}

		return $flows;
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
			$this->WriteToTrackingService(Loc::getMessage('BPSA_CYCLING_ERROR_1'), 0, CBPTrackingType::Error);
			throw new Exception();
		}
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		$fields = static::getTaskFieldsMap();
		$fields['TAGS'] = $fields['TAG_NAMES'];
		unset($fields['TAG_NAMES']);
		$fields['DEPENDS_ON']['Type'] = FieldType::STRING;

		$bpOptions = [
			'HoldToClose' => [
				'Name' => Loc::getMessage('BPTA1A_HOLD_TO_CLOSE'),
				'Type' => \Bitrix\Bizproc\FieldType::BOOL,
				'BaseType' => \Bitrix\Bizproc\FieldType::BOOL,
				'Required' => true,
				'Default' => 'N',
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

			// SE_PARAMETER
			if (
				$key === 'REQUIRED_RESULT'
				&& isset($values['SE_PARAMETER'][Tasks\Internals\Task\ParameterTable::PARAM_RESULT_REQUIRED])
			)
			{
				$values['REQUIRED_RESULT'] =
					$values['SE_PARAMETER'][Tasks\Internals\Task\ParameterTable::PARAM_RESULT_REQUIRED]['VALUE'];

				continue;
			}

			unset($map[$key]);
		}

		if (is_array($fields['DEPENDS_ON'] ?? null))
		{
			$map['DEPENDS_ON']['TrackValue'] = [];
			foreach ($fields['DEPENDS_ON'] as $taskId)
			{
				$task = Tasks\Internals\TaskTable::getById($taskId)->fetchObject();
				if ($task)
				{
					$map['DEPENDS_ON']['TrackValue'][] = $task->getTitle();
				}
			}
		}

		unset($values['SE_PARAMETER']);

		return parent::getDebugInfo($values, $map);
	}

	private function logDebugTaskUrl(int $taskId)
	{
		/** @var CBPDocumentService $documentService*/
		$documentService = $this->workflow->getService('DocumentService');

		$url = $documentService->getDocumentAdminPage(
			Tasks\Integration\Bizproc\Document\Task::resolveDocumentId($taskId)
		);

		$toWrite = [
			'propertyName' => Loc::getMessage('BPTA1A_TASK_URL_NAME'),
			'propertyValue' => $url,
			'propertyLinkName' => Loc::getMessage('BPTA1A_TASK_URL_LABEL'),
		];

		$this->writeDebugTrack(
			$this->getWorkflowInstanceId(),
			$this->getName(),
			$this->executionStatus,
			$this->executionResult,
			$this->getTitle(),
			$toWrite,
			CBPTrackingType::DebugLink,
		);
	}
}
