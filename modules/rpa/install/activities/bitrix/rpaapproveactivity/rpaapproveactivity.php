<?php

use Bitrix\Main\Controller\UserFieldConfig;
use Bitrix\Rpa;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

class CBPRpaApproveActivity
	extends CBPActivity
	implements IBPEventActivity, IBPActivityExternalEventListener
{
	public const RESPONSIBLE_TYPE_PLAIN = 'plain';
	public const RESPONSIBLE_TYPE_HEADS = 'heads';

	protected $taskId = 0;
	protected $taskStatus = false;
	protected $usersQueue = [];

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'Name' => null,
			'Description' => null,
			'Responsible' => null,
			'ExecutiveResponsible' => null,
			'SkipAbsent' => 'N',
			'AlterResponsible' => null,

			'ResponsibleType' => static::RESPONSIBLE_TYPE_PLAIN,
			'ApproveType' => 'all',
			'ApproveVoteTarget' => 0,
			'Actions' => [],
			'FieldsToShow' => [],

			'TaskId' => 0,
			'LastApprover' => null,
		];

		$this->SetPropertiesTypes([
			'TaskId' => ['Type' => 'int'],
			'LastApprover' => ['Type' => 'user'],
		]);
	}

	public function Execute()
	{
		if (!\Bitrix\Main\Loader::includeModule('rpa'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$this->Subscribe($this);

		return CBPActivityExecutionStatus::Executing;
	}

	public function Subscribe(IBPActivityExternalEventListener $eventHandler)
	{
		$documentId = $this->GetDocumentId();
		$documentService = $this->workflow->GetService('DocumentService');
		$taskUsers = $this->extractUsers();

		/** @var CBPTaskService $taskService */
		$taskService = $this->workflow->GetService('TaskService');
		$this->taskId = $taskService->CreateTask(
			[
				'USERS' => $taskUsers,
				'WORKFLOW_ID' => $this->GetWorkflowInstanceId(),
				'ACTIVITY' => $this->GetACNames()[0],
				'ACTIVITY_NAME' => $this->name,
				'NAME' => $this->Name,
				'DESCRIPTION' => $this->Description,
				'PARAMETERS' => $this->buildTaskParameters(),
				'DELEGATION_TYPE' => \CBPTaskDelegationType::Subordinate,
				'DOCUMENT_NAME' => $documentService->GetDocumentName($documentId)
			]
		);
		$this->TaskId = $this->taskId;
		$this->workflow->AddEventHandler($this->name, $eventHandler);
	}

	private function extractUsers()
	{
		if ($this->ResponsibleType == static::RESPONSIBLE_TYPE_HEADS)
		{
			return $this->extractNextHeads($this->getResponsibleId());
		}

		$documentId = $this->GetDocumentId();
		$taskUsers = CBPHelper::ExtractUsers($this->Responsible, $documentId);

		if ($this->ApproveType === 'queue')
		{
			$this->usersQueue = $taskUsers;
			$taskUsers = $this->extractNextInQueue();
		}
		else
		{
			$taskUsers = $this->applyAlternatives($taskUsers);
		}

		return $taskUsers;
	}

	private function getResponsibleId()
	{
		return \CBPHelper::ExtractUsers(
			$this->workflow->GetRuntime()->getDocumentService()->getDocumentResponsible($this->GetDocumentId()),
			$this->GetDocumentId(),
			true
		);
	}

	private function extractNextHeads(int $responsibleId): array
	{
		$userService = $this->workflow->GetRuntime()->getUserService();

		$heads = $userService->getUserHeads($responsibleId);
		$executives = $this->getExecutiveResponsible();

		if (array_intersect($heads, $executives))
		{
			$heads = array_unique(array_merge($heads, $executives));
		}

		$heads = $this->applyAlternatives($heads);

		return $heads;
	}

	private function extractNextInQueue(): array
	{
		$taskUser = array_shift($this->usersQueue);
		return $taskUser ? $this->applyAlternatives([$taskUser]) : [];
	}

	private function applyAlternatives(array $users): array
	{
		if ($this->SkipAbsent === 'Y')
		{
			$this->filterAbsent($users);
			if (!$users)
			{
				$users = \CBPHelper::ExtractUsers($this->AlterResponsible, $this->GetDocumentId());
			}
		}
		return $users;
	}

	private function filterAbsent(array &$users)
	{
		$userService = $this->workflow->GetRuntime()->getUserService();
		$filtered = [];

		foreach ($users as $i => $userId)
		{
			$schedule = $userService->getUserSchedule($userId);
			if (!$schedule->isAbsent())
			{
				$filtered[] = $userId;
			}
		}

		$users = $filtered;
	}

	protected function buildTaskParameters()
	{
		$params = [];
		$params['DOCUMENT_ID'] = $this->GetDocumentId();
		$params['TASK_EDIT_URL'] = (string)Rpa\Driver::getInstance()->getUrlManager()->getTaskIdUrl('#ID#');
		$params['ACTIONS'] = $this->Actions;
		$params['FIELDS_TO_SHOW'] = $this->FieldsToShow;
		$params['RESPONSIBLE_TYPE'] = $this->ResponsibleType;
		$params['APPROVE_TYPE'] = $this->ApproveType;

		return $params;
	}

	public function Unsubscribe(IBPActivityExternalEventListener $eventHandler)
	{
		$taskService = $this->workflow->GetService('TaskService');
		if ($this->taskStatus === false)
		{
			$taskService->DeleteTask($this->taskId);
		}
		else
		{
			$taskService->Update($this->taskId, [
				'STATUS' => $this->taskStatus
			]);
		}

		$this->workflow->RemoveEventHandler($this->name, $eventHandler);

		$this->taskId = 0;
		$this->taskStatus = false;
	}

	protected function ExecuteAction($action, $taskId, $modifiedBy, $fields = null)
	{
		/** @var CBPDocumentService $documentService */
		$documentService = $this->workflow->GetService('DocumentService');

		if (!is_array($fields))
		{
			$fields = [];
		}

		$stageId = (int) $action['stageId'];
		$itemStageId = $this->getItemStageId($this->GetDocumentType(), $this->GetDocumentId());

		if ($this->isCorrectStage($stageId))
		{
			$fields['STAGE_ID'] = $stageId;
		}
		else
		{
			$this->WriteToTrackingService(GetMessage('RPA_BP_APR_ERROR_STAGE_ID'), 0, CBPTrackingType::Error);
		}

		$fields['__taskId'] = $taskId;

		$documentService->UpdateDocument(
			$this->GetDocumentId(),
			$fields,
			$modifiedBy
		);

		$this->workflow->CloseActivity($this);

		if ($stageId !== $itemStageId)
		{
			throw new Exception(GetMessage('RPA_BP_APR_RUNTIME_TERMINATED'), CBPRuntime::EXCEPTION_CODE_INSTANCE_TERMINATED);
		}
	}

	private function isCorrectStage(int $stageId): bool
	{
		[$stages] = static::getDocumentStages($this->GetDocumentType());
		return isset($stages[$stageId]);
	}

	public function OnExternalEvent($arEventParameters = [])
	{
		if ($this->executionStatus == CBPActivityExecutionStatus::Closed)
			return;

		if (empty($arEventParameters['USER_ID']))
			return;

		if (isset($arEventParameters['onStageUpdate']) && isset($arEventParameters['stageId']))
		{
			if ($this->Actions[0]['stageId'] == $arEventParameters['stageId'])
			{
				$arEventParameters['APPROVE'] = true;
			}

			if ($this->Actions[1]['stageId'] == $arEventParameters['stageId'])
			{
				$arEventParameters['APPROVE'] = false;
			}
		}

		if (!array_key_exists('APPROVE', $arEventParameters))
			return;

		if (empty($arEventParameters['REAL_USER_ID']))
		{
			$arEventParameters['REAL_USER_ID'] = $arEventParameters['USER_ID'];
		}

		$approve = ($arEventParameters['APPROVE'] ? true : false);

		$taskUserIds = $this->getTaskIncompleteUserIds();

		$arEventParameters['USER_ID'] = intval($arEventParameters['USER_ID']);
		$arEventParameters['REAL_USER_ID'] = intval($arEventParameters['REAL_USER_ID']);
		if (!in_array($arEventParameters['REAL_USER_ID'], $taskUserIds))
		{
			return;
		}

		$this->LastApprover = 'user_'.$arEventParameters['REAL_USER_ID'];

		$taskService = $this->workflow->GetService('TaskService');
		$taskService->MarkCompleted($this->taskId, $arEventParameters['REAL_USER_ID'], $approve ? CBPTaskUserStatus::Yes : CBPTaskUserStatus::No);

		$approveType = $this->ApproveType;
		$responsibleType = $this->ResponsibleType;

		if ($responsibleType === static::RESPONSIBLE_TYPE_HEADS)
		{
			$approve = $this->touchHeadsQueue($approve, $arEventParameters['USER_ID']);
		}
		elseif ($approveType == 'any')
		{
			$approve = $this->calculateAnyApproveResult($approve);
		}
		elseif ($approveType == 'all')
		{
			$approve = $this->calculateAllApproveResult($approve);
		}
		elseif ($approveType == 'vote')
		{
			$approve = $this->calculateVoteApproveResult($approve);
		}
		elseif ($approveType == 'queue')
		{
			$approve = $this->touchQueue($approve);
		}

		if ($approve !== null)
		{
			$this->completeTask($approve);
		}
	}

	private function completeTask(bool $approve)
	{
		$actions = $this->Actions;

		$resultAction = ($approve ? $actions[0] : $actions[1]);
		$this->taskStatus = $approve ? CBPTaskStatus::CompleteYes : CBPTaskStatus::CompleteNo;

		$taskId = $this->taskId;
		$this->Unsubscribe($this);
		$this->ExecuteAction($resultAction, $taskId, $this->LastApprover);
	}

	private function calculateAnyApproveResult($currentStatus): ?bool
	{
		return $currentStatus;
	}

	private function calculateAllApproveResult($currentStatus): ?bool
	{
		[$all, $yes, $no] = $this->getResultCounters();

		if ($yes >= $all)
		{
			return true;
		}

		if ($no > 0)
		{
			return false;
		}

		return null;
	}

	private function calculateVoteApproveResult($currentStatus): ?bool
	{
		[$all, $yes, $no] = $this->getResultCounters();

		$voteLimit = min($all, max(0, (int)$this->ApproveVoteTarget));

		if ($yes >= $voteLimit)
		{
			return true;
		}

		if (($all - $no) < $voteLimit)
		{
			return false;
		}

		return null;
	}

	private function touchQueue($currentStatus): ?bool
	{
		//reject action stops queue
		if ($currentStatus === false)
		{
			return false;
		}

		$taskUsers = \CBPTaskService::getTaskUserIds($this->taskId);

		while ($newUsers = $this->extractNextInQueue())
		{
			if (array_diff($newUsers, $taskUsers))
			{
				break;
			}
		}

		if ($newUsers)
		{
			$taskUsers = array_merge($taskUsers, $newUsers);
			\CBPTaskService::Update($this->taskId, [
				'USERS' => $taskUsers,
			]);
		}

		return $newUsers ? null : $currentStatus;
	}

	private function touchHeadsQueue($currentStatus, $currentUserId): ?bool
	{
		if ($this->isExecutiveResponsible($currentUserId))
		{
			return $currentStatus;
		}

		if ($currentStatus === true)
		{
			$taskUsers = \CBPTaskService::getTaskUserIds($this->taskId);
			$heads = $this->extractNextHeads($currentUserId);

			if (!$heads)// Main head
			{
				return true;
			}

			$taskUsers = array_merge($taskUsers, $heads);

			\CBPTaskService::Update($this->taskId, [
				'USERS' => $taskUsers,
			]);
		}
		else
		{
			[$all, $yes, $no] = $this->getResultCounters();

			if (($all - $no) < 1)
			{
				return false;
			}
		}

		return null;
	}

	private function isExecutiveResponsible(int $userId)
	{
		$executives = $this->getExecutiveResponsible();
		return $executives ? in_array($userId, $executives) : false;
	}

	private function getExecutiveResponsible(): array
	{
		return CBPHelper::ExtractUsers($this->ExecutiveResponsible, $this->GetDocumentId());
	}

	private function getResultCounters()
	{
		$users = $this->getTaskUsers();

		$all = count($users);
		$yes = $no = 0;

		foreach ($users as $user)
		{
			if ((int)$user['STATUS'] === \CBPTaskUserStatus::Yes)
			{
				++$yes;
			}
			if ((int)$user['STATUS'] === \CBPTaskUserStatus::No)
			{
				++$no;
			}
		}

		return [$all, $yes, $no];
	}

	private function getTaskUsers(): array
	{
		$users = \CBPTaskService::getTaskUsers($this->taskId);

		return $users[$this->taskId] ?? [];
	}

	private function getTaskIncompleteUserIds(): array
	{
		$users = [];

		foreach ($this->getTaskUsers() as $taskUser)
		{
			if ((int)$taskUser['STATUS'] === \CBPTaskUserStatus::Waiting)
			{
				$users[] = (int) $taskUser['USER_ID'];
			}
		}

		return $users;
	}

	public function Cancel()
	{
		if ($this->taskId > 0)
		{
			$this->Unsubscribe($this);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	protected function OnEvent(CBPActivity $sender)
	{
		$sender->RemoveStatusChangeHandler(self::ClosedEvent, $this);
		$this->workflow->CloseActivity($this);
	}

	protected function ReInitialize()
	{
		parent::ReInitialize();

		$this->TaskId = 0;
		$this->LastApprover = null;
	}

	public static function ShowTaskForm($arTask, $userId, $userName = "")
	{
		$form = $buttons = '';

		$controls = static::getTaskControls($arTask);

		if (!empty($controls['BUTTONS']))
		{
			foreach ($controls['BUTTONS'] as $button)
			{
				$buttons .= sprintf(
					'<input type="submit" name="%s" value="%s"/>',
					htmlspecialcharsbx($button['NAME']),
					htmlspecialcharsbx($button['TEXT'])
				);
			}
		}

		return [$form, $buttons];
	}

	public static function getTaskControls($arTask)
	{
		$actions = $arTask['PARAMETERS']['ACTIONS'];

		return [
			'BUTTONS' => [
				[
					'TYPE' => 'submit',
					'TARGET_USER_STATUS' => CBPTaskUserStatus::Yes,
					'NAME' => 'approve',
					'VALUE' => 'Y',
					'TEXT' => $actions[0]['label'],
					'COLOR' => $actions[0]['color']
				],
				[
					'TYPE' => 'submit',
					'TARGET_USER_STATUS' => CBPTaskUserStatus::No,
					'NAME' => 'nonapprove',
					'VALUE' => 'Y',
					'TEXT' => $actions[1]['label'],
					'COLOR' => $actions[1]['color']
				]
			]
		];
	}

	public static function PostTaskForm($arTask, $userId, $arRequest, &$arErrors, $userName = "", $realUserId = null)
	{
		$arErrors = [];

		try
		{
			$userId = intval($userId);
			if ($userId <= 0)
				throw new CBPArgumentNullException('userId');

			$arEventParameters = [
				'USER_ID' => $userId,
				'REAL_USER_ID' => $realUserId,
				'USER_NAME' => $userName,
			];

			if (isset($arRequest['approve']) && $arRequest['approve'] <> ''
				|| isset($arRequest['INLINE_USER_STATUS']) && $arRequest['INLINE_USER_STATUS'] == CBPTaskUserStatus::Yes)
				$arEventParameters['APPROVE'] = true;
			elseif (isset($arRequest['nonapprove']) && $arRequest['nonapprove'] <> ''
				|| isset($arRequest['INLINE_USER_STATUS']) && $arRequest['INLINE_USER_STATUS'] == CBPTaskUserStatus::No)
				$arEventParameters['APPROVE'] = false;
			else
				throw new CBPNotSupportedException(GetMessage('RPA_BP_APR_ACT_NO_ACTION'));

			CBPRuntime::SendExternalEvent($arTask['WORKFLOW_ID'], $arTask['ACTIVITY_NAME'], $arEventParameters);

			return true;
		}
		catch (Exception $e)
		{
			$arErrors[] = [
				'code' => $e->getCode(),
				'message' => $e->getMessage(),
				'file' => $e->getFile().' ['.$e->getLine().']',
			];
		}

		return false;
	}

	public static function ValidateProperties($arTestProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];

		if (empty($arTestProperties['Responsible']) && $arTestProperties['ResponsibleType'] !== static::RESPONSIBLE_TYPE_HEADS)
		{
			$errors[] = ['code' => 'NotExist', 'parameter' => 'Users', 'message' => GetMessage('RPA_BP_APR_ACT_PROP_EMPTY1')];
		}

		if (!array_key_exists('ApproveType', $arTestProperties))
		{
			$errors[] = ['code' => 'NotExist', 'parameter' => 'ApproveType', 'message' => GetMessage('RPA_BP_APR_ACT_PROP_EMPTY2')];
		}
		else if (!in_array($arTestProperties['ApproveType'], ['any', 'all', 'vote', 'queue']))
		{
			$errors[] = ['code' => 'NotInRange', 'parameter' => 'ApproveType', 'message' => GetMessage('RPA_BP_APR_ACT_PROP_EMPTY3')];
		}

		if (!array_key_exists('Name', $arTestProperties) || $arTestProperties['Name'] == '')
		{
			$errors[] = ['code' => 'NotExist', 'parameter' => 'Name', 'message' => GetMessage('RPA_BP_APR_ACT_PROP_EMPTY4')];
		}

		return array_merge($errors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
	{
		self::fixCurrentValues($arCurrentValues, $documentType);

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, [
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'workflowParameters' => $arWorkflowParameters,
			'workflowVariables' => $arWorkflowVariables,
			'currentValues' => $arCurrentValues
		]);

		[$stages, $successStageId, $failStageId] = self::getDocumentStages($documentType);

		$dialog->setMap([
			'Name' => [
				'Name' => GetMessage('RPA_BP_APR_FIELD_NAME'),
				'FieldName' => 'approve_name',
				'Type' => 'string',
				'Required' => true
			],
			'Description' => [
				'Name' => GetMessage('RPA_BP_APR_FIELD_DESCRIPTION'),
				'FieldName' => 'approve_description',
				'Type' => 'string',
			],
			'ResponsibleType' => [
				'Name' => GetMessage('RPA_BP_APR_FIELD_RESPONSIBLE_TYPE'),
				'FieldName' => 'approve_responsible_type',
				'Type' => 'select',
				'Required' => true,
				'Default' => static::RESPONSIBLE_TYPE_PLAIN,
				'Options' => [
					static::RESPONSIBLE_TYPE_PLAIN => GetMessage('RPA_BP_APR_FIELD_RESPONSIBLE_TYPE_PLAIN'),
					static::RESPONSIBLE_TYPE_HEADS => GetMessage('RPA_BP_APR_FIELD_RESPONSIBLE_TYPE_HEADS'),
				]
			],
			'Responsible' => [
				'Name' => GetMessage('RPA_BP_APR_FIELD_USERS'),
				'FieldName' => 'approve_responsible',
				'Type' => 'user',
				'Required' => true,
				'Multiple' => true,
				'Default' => \Bitrix\Bizproc\Automation\Helper::getResponsibleUserExpression($documentType)
			],
			'ExecutiveResponsible' => [
				'Name' => GetMessage('RPA_BP_APR_FIELD_EXECUTIVE_RESPONSIBLE'),
				'FieldName' => 'approve_executive_responsible',
				'Type' => 'user',
				'Multiple' => true,
			],
			'AlterResponsible' => [
				'Name' => GetMessage('RPA_BP_APR_FIELD_ALTER_RESPONSIBLE'),
				'FieldName' => 'approve_alter_responsible',
				'Type' => 'user',
				'Multiple' => true,
			],
			'SkipAbsent' => [
				'Name' => GetMessage('RPA_BP_APR_FIELD_SKIP_ABSENT'),
				'FieldName' => 'approve_skip_absent',
				'Type' => 'bool',
				'Default' => 'Y',
			],
			'ApproveType' => [
				'Name' => GetMessage('RPA_BP_APR_FIELD_APPROVE_TYPE'),
				'FieldName' => 'approve_type',
				'Type' => 'select',
				'Options' => [
					'any' => GetMessage('RPA_BP_APR_FIELD_APPROVE_TYPE_ANY'),
					'vote' => GetMessage('RPA_BP_APR_FIELD_APPROVE_TYPE_FIXED'),
					'queue' => GetMessage('RPA_BP_APR_FIELD_APPROVE_TYPE_QUEUE'),
					'all' => GetMessage('RPA_BP_APR_FIELD_APPROVE_TYPE_ALL'),
				],
				'Default' => 'any',
			],
			'ApproveVoteTarget' => [
				'Name' => GetMessage('RPA_BP_APR_FIELD_APPROVE_FIXED_COUNT'),
				'FieldName' => 'approve_vote_target',
				'Type' => 'int',
				'Default' => 2,
			],
			'Actions' => [
				'Name' => GetMessage('RPA_BP_APR_FIELD_ACTIONS'),
				'Type' => 'mixed',
				'FieldName' => 'actions',
				'Multiple' => true,
				'Options' => $stages,
				'Default' => [
					[
						'label' => GetMessage('RPA_BP_APR_FIELD_APPROVE_ACTION_YES'),
						'color' => '3bc8f5',
						'stageId' => $successStageId
					],
					[
						'label' => GetMessage('RPA_BP_APR_FIELD_APPROVE_ACTION_NO'),
						'color' => 'f1361a',
						'stageId' => $failStageId
					]
				]
			],
			'FieldsToShow' => static::getFieldsToShowProperty($documentType)
		]);

		return $dialog;
	}

	private static function fixCurrentValues(&$currentValues, $documentType)
	{
		if (!is_array($currentValues))
		{
			return;
		}

		if (!empty($currentValues['fields_to_show']))
		{
			$toShow = [];
			foreach ($currentValues['fields_to_show'] as $fieldToShow => $value)
			{
				if ($value === 'Y')
				{
					$toShow[] = $fieldToShow;
				}
			}
			$currentValues['fields_to_show'] = $toShow;
		}

		if (!empty($currentValues['approve_responsible']))
		{
			$currentValues['approve_responsible'] = CBPHelper::UsersStringToArray($currentValues['approve_responsible'], $documentType, $errors);
		}

		if (!empty($currentValues['approve_executive_responsible_enable']))
		{
			$currentValues['approve_executive_responsible_enable'] = CBPHelper::UsersStringToArray($currentValues['approve_executive_responsible'], $documentType, $errors);
		}

		if (!empty($currentValues['approve_alter_responsible_enable']))
		{
			$currentValues['approve_alter_responsible_enable'] = CBPHelper::UsersStringToArray($currentValues['approve_alter_responsible'], $documentType, $errors);
		}

		if (!empty($currentValues['fields_to_set']))
		{
			$toSet = [];
			foreach ($currentValues['fields_to_set'] as $fieldToShow => $value)
			{
				if ($value === 'Y')
				{
					$toSet[] = $fieldToShow;
				}
			}
			$currentValues['fields_to_set'] = $toSet;
		}
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$errors = [];

		$arMap = [
			'approve_name' => 'Name',
			'approve_description' => 'Description',
			'approve_type' => 'ApproveType',
			'approve_vote_target' => 'ApproveVoteTarget',
			'actions' => 'Actions',
			'approve_responsible_type' => 'ResponsibleType',
			'approve_skip_absent' => 'SkipAbsent',
		];

		$properties = [];
		foreach ($arMap as $key => $value)
		{
			$properties[$value] = $arCurrentValues[$key] ?? null;
		}

		if (empty($properties['ApproveType']))
		{
			$properties['ApproveType'] = 'any';
		}

		$properties['FieldsToShow'] = [];
		if (!empty($arCurrentValues['fields_to_show']))
		{
			foreach ($arCurrentValues['fields_to_show'] as $fieldToShow => $value)
			{
				if ($value === 'Y')
				{
					$properties['FieldsToShow'][] = $fieldToShow;
				}
			}
		}

		$properties['Responsible'] = null;
		$properties['ExecutiveResponsible'] = null;
		$properties['AlterResponsible'] = null;

		if ($properties['ResponsibleType'] !== static::RESPONSIBLE_TYPE_HEADS)
		{
			$properties['Responsible'] = CBPHelper::UsersStringToArray($arCurrentValues['approve_responsible'], $documentType, $errors);
		}
		else
		{
			$properties['Responsible'] = ['responsible_head'];//TODO: remove, for kanban view only.
			if (!empty($arCurrentValues['approve_executive_responsible_enable']))
			{
				$properties['ExecutiveResponsible'] = CBPHelper::UsersStringToArray($arCurrentValues['approve_executive_responsible'], $documentType, $errors);
			}
		}

		if ($properties['SkipAbsent'] === 'Y' && !empty($arCurrentValues['approve_alter_responsible_enable']))
		{
			$properties['AlterResponsible'] = CBPHelper::UsersStringToArray($arCurrentValues['approve_alter_responsible'], $documentType, $errors);
		}

		if (count($errors) > 0)
		{
			return false;
		}

		$properties['ApproveVoteTarget'] = min(99, max(1, (int)$properties['ApproveVoteTarget']));
		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($errors) > 0)
		{
			return false;
		}

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity['Properties'] = $properties;

		return true;
	}

	protected static function getDocumentStages(array $docType)
	{
		$stages = [];
		$success = $fail = null;

		$type = self::getItemType($docType);
		if ($type)
		{
			foreach ($type->getStages() as $stage)
			{
				$stages[$stage->getId()] = $stage->getName();

				if ($success === null && $stage->isSuccess())
				{
					$success = $stage->getId();
				}

				if ($fail === null && $stage->isFail())
				{
					$fail = $stage->getId();
				}
			}
		}

		return [$stages, $success, $fail];
	}

	protected static function getItemType(array $docType): ?Rpa\Model\Type
	{
		$typeId = str_replace('T', '', $docType[2]);

		return Rpa\Model\TypeTable::getById($typeId)->fetchObject();
	}

	protected static function getFieldsToShowProperty(array $documentType): array
	{
		$settings = self::getFieldsToShowSettings($documentType);
		$default = [];
		if (isset($settings['fields']))
		{
			$default = array_keys($settings['fields']);
		}

		return [
			'Name' => GetMessage('RPA_BP_APR_FIELD_FIELDS_TO_SHOW'),
			'Type' => 'mixed',
			'FieldName' => 'fields_to_show',
			'Multiple' => true,
			'Settings' => $settings,
			'Default' => $default,
		];
	}

	protected static function getFieldsToShowSettings(array $documentType): array
	{
		$settings = [];
		$type = self::getItemType($documentType);

		if ($type)
		{
			$settings['entityId'] = $type->getItemUserFieldsEntityId();
			$controller = new UserFieldConfig();
			$userFieldsCollection = $type->getUserFieldCollection();
			foreach($userFieldsCollection as $userField)
			{
				$settings['fields'][$userField->getName()] = $controller->preparePublicData($userField->toArray(), Rpa\Driver::MODULE_ID);
			}
			$settings['typeId'] = $type->getId();
			$settings['isCreationEnabled'] = false;
		}

		return $settings;
	}

	private function getItemStageId(array $documentType, array $documentId): int
	{
		$itemId = Rpa\Integration\Bizproc\Document\Item::getDocumentItemId($documentId[2]);
		$type = self::getItemType($documentType);
		$item = $type ? $type->getItem($itemId) : null;

		return $item ? $item->getStageId() : 0;
	}
}