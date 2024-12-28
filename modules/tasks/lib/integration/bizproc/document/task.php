<?php

namespace Bitrix\Tasks\Integration\Bizproc\Document;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Flow\Path\FlowPathMaker;
use Bitrix\Tasks\Integration\Bizproc\Automation\Factory;
use Bitrix\Tasks\Internals\Task\Mark;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Internals\Task\Priority;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Slider\Path\TaskPathMaker;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;
use Bitrix\Socialnetwork;

if (!Main\Loader::includeModule('bizproc'))
{
	return;
}

Loc::loadMessages(__FILE__);

class Task implements \IBPWorkflowDocument
{
	public static function getDocumentType($documentId)
	{
		return 'TASK';
	}

	public static function getDocumentFieldTypes()
	{
		$result = \CBPHelper::GetDocumentFieldTypes();
		//TODO: append UF`s
		return $result;
	}

	public static function canUserOperateDocument($operation, $userId, $documentId, $arParameters = array())
	{
		$userId = (int) $userId;
		$user = new \CBPWorkflowTemplateUser($userId);

		if ($user->isAdmin())
		{
			return true; //Admin is the Lord of the Automation
		}

		if (\Bitrix\Tasks\Util\User::isExternalUser($userId))
		{
			return false;
		}

		switch ($operation)
		{
			case \CBPCanUserOperateOperation::CreateWorkflow:
			case \CBPCanUserOperateOperation::CreateAutomation:
			{
				//for admins only, already checked
				break;
			}

			case \CBPCanUserOperateOperation::StartWorkflow:
			case \CBPCanUserOperateOperation::ViewWorkflow:
			case \CBPCanUserOperateOperation::ReadDocument:
			{
				$members = MemberTable::getList([
					'filter' => ['=TASK_ID' => $documentId],
					'select' => ['USER_ID']
				])->fetchAll();

				$members = array_column($members, 'USER_ID');

				return in_array($userId, $members);
			}

			case \CBPCanUserOperateOperation::WriteDocument:
			{
				$creatorId = MemberTable::getList([
					'filter' => ['=TASK_ID' => $documentId, '=TYPE' => 'O'],
					'select' => ['USER_ID']
				])->fetch()['USER_ID'];

				return (int) $creatorId === (int) $userId;
			}
		}

		return false;
	}

	public static function canUserOperateDocumentType($operation, $userId, $documentType, $arParameters = array())
	{
		$userId = (int) $userId;
		$user = new \CBPWorkflowTemplateUser($userId);

		if ($user->isAdmin())
		{
			return true; //Admin is the Lord of the Automation
		}

		if (\Bitrix\Tasks\Util\User::isExternalUser($userId))
		{
			return false;
		}

		// if ($operation === \CBPCanUserOperateOperation::CreateAutomation)
		{
			if (static::isProjectTask($documentType))
			{
				$projectId = static::resolveProjectId($documentType);

				if ($projectId > 0 && Main\Loader::includeModule('socialnetwork'))
				{
					$activeFeatures = \CSocNetFeatures::GetActiveFeaturesNames(\SONET_ENTITY_GROUP, $projectId);
					if (!is_array($activeFeatures) || !array_key_exists('tasks', $activeFeatures))
					{
						return false;
					}

					return (\CSocNetUserToGroup::GetUserRole($userId, $projectId) === \SONET_ROLES_OWNER);
				}
			}
			elseif (static::isScrumProjectTask($documentType))
			{
				$projectId = static::resolveScrumProjectId($documentType);

				if ($projectId > 0 && Main\Loader::includeModule('socialnetwork'))
				{
					$activeFeatures = \CSocNetFeatures::GetActiveFeaturesNames(\SONET_ENTITY_GROUP, $projectId);
					if (!is_array($activeFeatures) || !array_key_exists('tasks', $activeFeatures))
					{
						return false;
					}

					return (
						\CSocNetUserToGroup::GetUserRole($userId, $projectId) === \SONET_ROLES_OWNER
						|| \CSocNetUserToGroup::GetUserRole($userId, $projectId) === \SONET_ROLES_MODERATOR
					);
				}
			}
			elseif (static::isPlanTask($documentType))
			{
				$ownerId = static::resolvePlanId($documentType);
				return ($ownerId === $userId);
			}
			elseif (static::isPersonalTask($documentType))
			{
				$ownerId = static::resolvePersonId($documentType);
				return ($ownerId === $userId);
			}
		}

		return false;
	}

	public static function getDocumentAdminPage($documentId)
	{
		$res = \CTasks::GetList(
			array(),
			['ID' => (int) $documentId, 'CHECK_PERMISSIONS' => 'N'],
			array('RESPONSIBLE_ID')
		);
		if ($res && ($task = $res->Fetch()))
		{
			return sprintf(
				'/company/personal/user/%d/tasks/task/view/%d/',
				$task['RESPONSIBLE_ID'],
				$documentId
			);
		}

		return null;
	}

	public static function getDocumentFields($documentType)
	{
		$fields = [
			'ID' => [
				'Name' => 'ID',
				'Type' => 'int',
			],
			'TITLE' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_TITLE'),
				'Type' => 'string',
				'Editable' => true
			],
			'DESCRIPTION' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_DESCRIPTION'),
				'Type' => 'text',
				'Editable' => true
			],
			'IS_IMPORTANT' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_IS_IMPORTANT'),
				'Type' => 'bool',
				'Editable' => true
			],
			'STATUS' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_STATUS'),
				'Type' => 'select',
				//'Editable' => true,
				'Options' => [
					Status::PENDING => Loc::getMessage('TASKS_BP_DOCUMENT_STATUS_PENDING_1'),
					Status::IN_PROGRESS => Loc::getMessage('TASKS_BP_DOCUMENT_STATUS_IN_PROGRESS'),
					Status::SUPPOSEDLY_COMPLETED => Loc::getMessage('TASKS_BP_DOCUMENT_STATUS_SUPPOSEDLY_COMPLETED'),
					Status::COMPLETED => Loc::getMessage('TASKS_BP_DOCUMENT_STATUS_COMPLETED'),
					Status::DEFERRED => Loc::getMessage('TASKS_BP_DOCUMENT_STATUS_DEFERRED'),
				],
			],
			'RESPONSIBLE_ID' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_ASSIGNEE_ID'),
				'Type' => 'user',
				//'Editable' => true
			],
			'DATE_START' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_DATE_START'),
				'Type' => 'datetime',
			],
			'DURATION_PLAN' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_DURATION_PLAN'),
				'Type' => 'int',
			],
			'DURATION_FACT' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_DURATION_FACT'),
				'Type' => 'int',
			],
			'TIME_ESTIMATE' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_TIME_ESTIMATE'),
				'Type' => 'int',
				//'Editable' => true
			],
			'DEADLINE' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_DEADLINE'),
				'Type' => 'datetime',
				'Editable' => true
			],
			'START_DATE_PLAN' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_START_DATE_PLAN'),
				'Type' => 'datetime',
				'Editable' => true
			],
			'END_DATE_PLAN' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_END_DATE_PLAN'),
				'Type' => 'datetime',
				'Editable' => true
			],
			'IS_EXPIRED' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_IS_EXPIRED'),
				'Type' => 'bool',
			],
			'CREATED_BY' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_CREATED_BY'),
				'Type' => 'user',
			],
			'CREATED_DATE' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_CREATED_DATE'),
				'Type' => 'datetime',
			],
			'CHANGED_BY' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_CHANGED_BY'),
				'Type' => 'user',
			],
			'CHANGED_DATE' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_CHANGED_DATE'),
				'Type' => 'datetime',
			],
			'CLOSED_BY' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_CLOSED_BY'),
				'Type' => 'user',
			],
			'CLOSED_DATE' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_CLOSED_DATE'),
				'Type' => 'datetime',
			],
			'MARK' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_MARK'),
				'Type' => 'select',
				'Editable' => true,
				'Options' => [
					Mark::POSITIVE => Loc::getMessage('TASKS_BP_DOCUMENT_MARK_POSITIVE'),
					Mark::NEGATIVE => Loc::getMessage('TASKS_BP_DOCUMENT_MARK_NEGATIVE')
				]
			],
			'ALLOW_CHANGE_DEADLINE' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_ALLOW_CHANGE_DEADLINE'),
				'Type' => 'bool',
				'Editable' => true,
			],
			'ALLOW_TIME_TRACKING' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_ALLOW_TIME_TRACKING'),
				'Type' => 'bool',
				'Editable' => true
			],
			'MATCH_WORK_TIME' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_MATCH_WORK_TIME'),
				'Type' => 'bool',
			],
			'TASK_CONTROL' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_TASK_CONTROL'),
				'Type' => 'bool',
				'Editable' => true
			],
			'ADD_IN_REPORT' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_ADD_IN_REPORT'),
				'Type' => 'bool',
				//'Editable' => true
			],
			'GROUP_ID' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_GROUP_ID_INT'),
				'Type' => 'int',
				//'Editable' => true
			],
			'GROUP_ID_PRINTABLE' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_GROUP_ID_PRINTABLE'),
				'Type' => 'string',
			],
			'PARENT_ID' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_PARENT_ID'),
				'Type' => 'int',
			],
			'ACCOMPLICES' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_ACCOMPLICES'),
				'Type' => 'user',
				'Editable' => true,
				'Multiple' => true,
			],
			'AUDITORS' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_AUDITORS'),
				'Type' => 'user',
				'Editable' => true,
				'Multiple' => true,
			],
			'TAGS' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_TAGS'),
				'Type' => 'string',
				'Editable' => true,
				'Multiple' => true,
			],
		];

		if (isset($documentType) && (self::isPlanTask($documentType) || self::isPersonalTask($documentType)))
		{
			$fields['MEMBER_ROLE'] = [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_MEMBER_ROLE'),
				'Type' => 'select',
				'Options' => [
					'O' => Loc::getMessage('TASKS_BP_DOCUMENT_MEMBER_ROLE_O'),
					'R' => Loc::getMessage('TASKS_BP_DOCUMENT_MEMBER_ROLE_R_V2'),
					'A' => Loc::getMessage('TASKS_BP_DOCUMENT_MEMBER_ROLE_A'),
					'U' => Loc::getMessage('TASKS_BP_DOCUMENT_MEMBER_ROLE_U'),
				]
			];
		}

		if (Main\Loader::includeModule('forum'))
		{
			$fields['COMMENT_RESULT'] = [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_COMMENT_RESULT'),
				'Type' => 'text',
				//'Editable' => true,
				'Multiple' => true,
			];
			$fields['COMMENT_RESULT_LAST'] = [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_COMMENT_RESULT_LAST'),
				'Type' => 'text',
				//'Editable => true,
				'Multiple' => false,
			];

		}

		return array_merge($fields, self::getFieldsCreatedByUser());
	}

	public static function getFieldsCreatedByUser(string $type = null)
	{
		static $fieldsCreatedByUser = null;
		if(is_array($fieldsCreatedByUser))
		{
			return isset($type) ? self::filterUserFields($type, $fieldsCreatedByUser) : $fieldsCreatedByUser;
		}
		$fieldsCreatedByUser = [];

		$fieldsCreatedByUserData = \Bitrix\Main\UserFieldTable::getList([
			'select' => [
				'ID',
				'FIELD_NAME',
				'USER_TYPE_ID',
				'EDIT_IN_LIST',
				'MANDATORY',
				'MULTIPLE',
				'EDIT_FORM_LABEL' => 'LABELS.EDIT_FORM_LABEL',
			],
			'filter' => [
				'=ENTITY_ID' => 'TASKS_TASK',
				'%=FIELD_NAME' => 'UF_AUTO_%'
			],
			'runtime' => [
				\Bitrix\Main\UserFieldTable::getLabelsReference('LABELS', LANGUAGE_ID),
			],
		])->fetchAll();

		foreach ($fieldsCreatedByUserData as $field)
		{
			$name = $field['EDIT_FORM_LABEL'] ?: $field['FIELD_NAME'];

			$fieldsCreatedByUser[$field['FIELD_NAME']] = [
				'Name' => $name,
				'Type' => $field['USER_TYPE_ID'] === 'boolean' ? 'bool' : $field['USER_TYPE_ID'],
				'Editable' => \CBPHelper::getBool($field['EDIT_IN_LIST']),
				'Required' => \CBPHelper::getBool($field['MANDATORY']),
				'Multiple' => \CBPHelper::getBool($field['MULTIPLE'])
			];
		}

		return isset($type) ? self::filterUserFields($type, $fieldsCreatedByUser) : $fieldsCreatedByUser;
	}

	protected static function filterUserFields(string $type, array $userFields)
	{
		$filteredFields = [];
		foreach ($userFields as $name => $field)
		{
			if($field['Type'] === $type)
			{
				$filteredFields[$name] = $field;
			}
		}
		return $filteredFields;
	}

	public static function getDocument($documentId, $documentType = null)
	{
		//$task = \Bitrix\Tasks\Item\Task::getInstance($documentId, 1);
		//$fields = $task->getData();
		$res = \CTasks::GetByID($documentId, false);
		$fields = $res ? $res->fetch() : null;

		if (!$fields)
		{
			return null;
		}

		if ($documentType)
		{
			$memberId = 0;
			if (self::isPlanTask($documentType))
			{
				$memberId = self::resolvePlanId($documentType);
			}
			elseif (self::isPersonalTask($documentType))
			{
				$memberId = self::resolvePersonId($documentType);
			}

			if ($memberId > 0)
			{
				$fields['MEMBER_ROLE'] = self::getMemberRole($memberId, $fields);
			}
		}

		if (Main\Loader::includeModule('forum'))
		{
			$fields['COMMENT_RESULT'] =
				(new \Bitrix\Tasks\Internals\Task\Result\ResultManager(0))
					->getTaskResults((int)$documentId)
			;
			$fields['COMMENT_RESULT_LAST'] = \Bitrix\Tasks\Internals\Task\Result\ResultManager::getLastResult((int)$documentId);
		}

		$fields = self::setFlowMessages($fields);

		static::convertFieldsToDocument($fields);

		return $fields;
	}

	private static function getMemberRole($memberId, array $fields)
	{
		if ($memberId === (int)$fields['CREATED_BY'])
		{
			return 'O';
		}

		if ($memberId === (int)$fields['RESPONSIBLE_ID'])
		{
			return 'R';
		}

		foreach ($fields['ACCOMPLICES'] as $accomplice)
		{
			if ($memberId === (int)$accomplice)
			{
				return 'A';
			}
		}

		foreach ($fields['AUDITORS'] as $auditor)
		{
			if ($memberId === (int)$auditor)
			{
				return 'U';
			}
		}

		return null;
	}

	public static function createDocument($parentDocumentId, $arFields)
	{
		throw new NotImplementedException('Currently unavailable.');
	}

	public static function updateDocument($documentId, $fields, $modifiedById = null)
	{
		$whiteFieldsList = [
			//string
			'TITLE',
			'DESCRIPTION',
			'TAGS',

			//int
			'IS_IMPORTANT',
			'DURATION_PLAN',
			'TIME_ESTIMATE',
			'MARK',
			'STAGE_ID',
			'STATUS',

			//user
			'RESPONSIBLE_ID',
			'ACCOMPLICES',
			'AUDITORS',

			//date|datetime
			'DEADLINE',
			'START_DATE_PLAN',
			'END_DATE_PLAN',

			//bool
			'ALLOW_CHANGE_DEADLINE',
			'ALLOW_TIME_TRACKING',
			//'MATCH_WORK_TIME',
			'TASK_CONTROL',
			'ADD_IN_REPORT',
		];
		$whiteFieldsList = array_merge($whiteFieldsList, array_keys(self::getFieldsCreatedByUser()));

		$fields = array_intersect_key($fields, array_flip($whiteFieldsList));

		if (isset($fields['RESPONSIBLE_ID']))
		{
			$fields['RESPONSIBLE_ID'] = \CBPHelper::ExtractUsers(
				$fields['RESPONSIBLE_ID'],
				self::resolveDocumentId($documentId),
				true
			);
		}

		if (isset($fields['ACCOMPLICES']))
		{
			$fields['ACCOMPLICES'] = \CBPHelper::ExtractUsers(
				$fields['ACCOMPLICES'],
				self::resolveDocumentId($documentId)
			);
		}

		if (isset($fields['AUDITORS']))
		{
			$fields['AUDITORS'] = \CBPHelper::ExtractUsers(
				$fields['AUDITORS'],
				self::resolveDocumentId($documentId)
			);
		}

		if (isset($fields['IS_IMPORTANT']))
		{
			$fields['PRIORITY'] = $fields['IS_IMPORTANT'] === 'Y' ? Priority::HIGH : Priority::AVERAGE;
			unset($fields['IS_IMPORTANT']);
		}

		if (is_array($fields['TAGS'] ?? null))
		{
			$preparedTags = [];
			foreach ($fields['TAGS'] as $tag)
			{
				if (is_numeric($tag))
				{
					$preparedTags[] = (string)$tag;
				}
				elseif (is_string($tag))
				{
					$preparedTags[] = $tag;
				}
			}

			$fields['TAGS'] = $preparedTags;
		}
		else
		{
			unset($fields['TAGS']);
		}

		$documentFields = self::getDocumentFields(null);
		foreach ($fields as $fieldsName => $fieldValue)
		{
			if($documentFields[$fieldsName]['Type'] === 'bool')
			{
				$isUf = mb_strpos($fieldsName, 'UF_') === 0;
				$fieldValue = \CBPHelper::getBool($fieldValue);
				$fields[$fieldsName] = $isUf ? (int) $fieldValue : ($fieldValue ? 'Y' : 'N');
			}
		}

		//normalize date fields
		$userDateFields = self::getFieldsCreatedByUser('datetime');
		$allDateFields = array_merge(
			['DEADLINE', 'END_DATE_PLAN', 'START_DATE_PLAN'],
			array_keys($userDateFields)
		);
		foreach ($allDateFields as $dateField)
		{
			if (!isset($fields[$dateField]))
			{
				if (array_key_exists($dateField, $fields))
				{
					$fields[$dateField] = '';
				}

				continue;
			}
			$isMultiple = isset($userDateFields[$dateField]) && $userDateFields[$dateField]['Multiple'];
			$fields[$dateField] = self::convertDateValue($fields[$dateField], $isMultiple);
		}

		if (empty($fields))
		{
			return false;
		}

		$prevOccurAsUserId = \Bitrix\Tasks\Util\User::getOccurAsId();
		if ($modifiedById > 0)
		{
			\Bitrix\Tasks\Util\User::setOccurAsId($modifiedById);
		}

		$task = new \CTasks();
		$result =  $task->update($documentId, $fields, [
			'CHECK_RIGHTS_ON_FILES' => 'N',
			'AUTHOR_ID' => $modifiedById ?: 1,
			'USER_ID' => $modifiedById ?: 1,
		]);

		\Bitrix\Tasks\Util\User::setOccurAsId($prevOccurAsUserId);

		return $result;
	}

	public static function deleteDocument($documentId)
	{
		$res = \CTasks::GetList([], ['ID' => (int) $documentId, 'CHECK_PERMISSIONS' => 'N'], ['CREATED_BY']);
		if ($res && ($task = $res->Fetch()))
		{
			$prevOccurAsUserId = \Bitrix\Tasks\Util\User::getOccurAsId(); // null or positive integer
			\Bitrix\Tasks\Util\User::setOccurAsId($task['CREATED_BY']);

			$task = new \CTasks();
			$task->delete($documentId);

			\Bitrix\Tasks\Util\User::setOccurAsId($prevOccurAsUserId);
		}

		return true;
	}

	public static function getEntityName($entity)
	{
		return Loc::getMessage('TASKS_BP_DOCUMENT_ENTITY_NAME');
	}

	public static function resolvePersonalTaskType($userId)
	{
		return 'TASK_USER_'.(int)$userId;
	}

	public static function resolvePersonId($documentType)
	{
		return (int)mb_substr($documentType, mb_strlen('TASK_USER_'));
	}

	public static function isPersonalTask($documentType)
	{
		return (mb_strpos($documentType, 'TASK_USER_') === 0);
	}

	public static function resolvePlanTaskType($userId)
	{
		return 'TASK_PLAN_'.(int)$userId;
	}

	public static function resolvePlanId($documentType)
	{
		return (int)mb_substr($documentType, mb_strlen('TASK_PLAN_'));
	}

	public static function isPlanTask($documentType)
	{
		return (mb_strpos($documentType, 'TASK_PLAN_') === 0);
	}

	public static function resolveProjectTaskType($projectId)
	{
		return 'TASK_PROJECT_'.(int)$projectId;
	}

	public static function resolveScrumProjectTaskType($projectId)
	{
		return 'TASK_SCRUM_PROJECT_'.(int) $projectId;
	}

	public static function resolveProjectId($documentType)
	{
		return (int)mb_substr($documentType, mb_strlen('TASK_PROJECT_'));
	}

	public static function resolveScrumProjectId($documentType)
	{
		return (int)mb_substr($documentType, mb_strlen('TASK_SCRUM_PROJECT_'));
	}

	public static function isProjectTask($documentType)
	{
		return (mb_strpos($documentType, 'TASK_PROJECT_') === 0);
	}

	public static function isScrumProjectTask($documentType)
	{
		return (mb_strpos($documentType, 'TASK_SCRUM_PROJECT_') === 0);
	}

	public static function getDocumentName($documentId)
	{
		$res = \CTasks::GetList([], ['ID' => (int) $documentId, 'CHECK_PERMISSIONS' => 'N'], ['TITLE']);
		if ($res && ($task = $res->Fetch()))
		{
			return \Bitrix\Main\Text\Emoji::decode($task['TITLE']);
		}
		return null;
	}

	public static function createAutomationTarget($documentType)
	{
		if (mb_strpos($documentType, 'TASK_') === 0)
		{
			return Factory::createTarget($documentType);
		}

		return null;
	}

	public static function resolveDocumentId($taskId)
	{
		return ['tasks', __CLASS__, $taskId];
	}

	public static function getAllowableOperations($documentType)
	{
		return [];
	}

	public static function getAllowableUserGroups($documentType)
	{
		if (static::isScrumProjectTask($documentType))
		{
			return [
				'scrum_owner' => Loc::getMessage('TASKS_BP_DOCUMENT_SCRUM_OWNER_ROLE'),
				'scrum_master' => Loc::getMessage('TASKS_BP_DOCUMENT_SCRUM_MASTER_ROLE'),
				'scrum_team' => Loc::getMessage('TASKS_BP_DOCUMENT_SCRUM_TEAM_ROLE'),
			];
		}

		return [];
	}

	public static function getUsersFromUserGroup($group, $documentId)
	{
		if ($group === 'responsible')
		{
			$member = MemberTable::getList([
				'filter' => ['=TASK_ID' => $documentId, '=TYPE' => MemberTable::MEMBER_TYPE_RESPONSIBLE],
				'select' => ['USER_ID']
			])->fetch();

			return $member ? [$member['USER_ID']] : [];
		}
		elseif (strpos($group, 'scrum_') === 0)
		{
			$projectId = static::getProjectId($documentId);

			if ($projectId && Main\Loader::includeModule('socialnetwork'))
			{
				$workGroup = Socialnetwork\Item\Workgroup::getById($projectId);
				$scrumMaster = (int)$workGroup->getScrumMaster();

				if ($group === 'scrum_master')
				{
					return [$scrumMaster];
				}

				if ($group === 'scrum_owner')
				{
					$owner = Socialnetwork\UserToGroupTable::getList([
						'filter' => [
							'=ROLE' => Socialnetwork\UserToGroupTable::ROLE_OWNER,
							'=GROUP_ID' => $projectId,
						],
						'cache' => [
							'ttl' => 3600
						],
					])->fetch();

					return $owner ? [$owner['USER_ID']] : [];
				}

				//else ($group === 'scrum_team')
				$teamRows = Socialnetwork\UserToGroupTable::getList([
					'filter' => [
						'=ROLE' => Socialnetwork\UserToGroupTable::ROLE_MODERATOR,
						'=GROUP_ID' => $projectId,
					],
					'cache' => [
						'ttl' => 3600
					],
				])->fetchAll();

				$team = array_column($teamRows, 'USER_ID');
				$team = array_map(fn($user) => (int)$user, $team);

				return array_filter($team, fn($user) => $user !== $scrumMaster);
			}
		}

		return [];
	}

	private static function getProjectId(int $documentId): int
	{
		$document = static::getDocument($documentId);
		if ($document && $document['GROUP_ID'])
		{
			return (int)$document['GROUP_ID'];
		}

		return 0;
	}

	private static function convertFieldsToDocument(array &$fields)
	{
		$fields['IS_IMPORTANT'] = ($fields['PRIORITY'] > Priority::AVERAGE) ? 'Y' : 'N';

		$documentFields = self::getDocumentFields(null);
		foreach ($fields as $fieldName => $fieldValue)
		{
			if(($documentFields[$fieldName]['Type'] ?? null) === 'bool')
			{
				$fields[$fieldName] = self::resolveBoolType($fieldValue);
			}
		}

		//users
		foreach (['RESPONSIBLE_ID', 'CREATED_BY', 'CHANGED_BY', 'CLOSED_BY'] as $userKey)
		{
			$fields[$userKey] = $fields[$userKey] > 0 ?sprintf('user_%d', $fields[$userKey]) : null;
		}
		foreach ($fields['ACCOMPLICES'] as $i => $userId)
		{
			$fields['ACCOMPLICES'][$i] = sprintf('user_%d', $userId);
		}
		foreach ($fields['AUDITORS'] as $i => $userId)
		{
			$fields['AUDITORS'][$i] = sprintf('user_%d', $userId);
		}
		//$fields['TAGS'] - nothing to do.

		$fields['STATUS'] = $fields['REAL_STATUS'];
		$fields['IS_EXPIRED'] = 'N';
		if (!empty($fields['DEADLINE']))
		{
			$closedDateTs = time();
			if ($fields['STATUS'] >= Status::SUPPOSEDLY_COMPLETED && !empty($fields['CLOSED_DATE']))
			{
				$closedDateTs = DateTime::createFromUserTime($fields['CLOSED_DATE'])->getTimestamp();
			}

			$deadlineTs = DateTime::createFromUserTime($fields['DEADLINE'])->getTimestamp();
			if ($deadlineTs <= $closedDateTs)
			{
				$fields['IS_EXPIRED'] = 'Y';
			}
		}

		$fields['GROUP_ID_PRINTABLE'] = Loc::getMessage('TASKS_BP_DOCUMENT_GROUP_ID_PRINTABLE_DEFAULT');
		if ($fields['GROUP_ID'] > 0)
		{
			$fields['GROUP_ID_PRINTABLE'] = $fields['GROUP_ID'];
			if (Main\Loader::includeModule('socialnetwork'))
			{
				$res = \Bitrix\Socialnetwork\WorkgroupTable::getList(
					['filter' => ['=ID' => $fields['GROUP_ID']], 'select' => ['NAME']]
				);
				if ($row = $res->fetch())
				{
					$fields['GROUP_ID_PRINTABLE'] = $row['NAME'];
				}
			}
		}
		else
		{
			$fields['GROUP_ID'] = null;
		}

		if (isset($fields['FLOW_ID']) && $fields['FLOW_ID'] > 0)
		{
			$flowOwnerId = FlowRegistry::getInstance()->get($fields['FLOW_ID'])->getOwnerId();
			$fields['FLOW_OWNER'] =  'user_' . $flowOwnerId;
		}

		if ((int)$fields['PARENT_ID'] <= 0) // issue: 0155930
		{
			$fields['PARENT_ID'] = null;
		}

		if (is_array($fields['COMMENT_RESULT']))
		{
			$results = [];
			/** @var \Bitrix\Tasks\Internals\Task\Result\Result $result */
			foreach ($fields['COMMENT_RESULT'] as $result)
			{
				$results[] = htmlspecialcharsback($result->getText()); //$result->getFormattedText();
			}

			$fields['COMMENT_RESULT'] = array_reverse($results);
			unset($results, $result);
		}

		if (is_array($fields['COMMENT_RESULT_LAST']))
		{
			$fields['COMMENT_RESULT_LAST'] = htmlspecialcharsback($fields['COMMENT_RESULT_LAST']['TEXT']);
		}
	}

	protected static function resolveBoolType($value)
	{
		if(is_array($value))
		{
			return array_map([self::class, 'resolveBoolType'], $value);
		}

		return \CBPHelper::getBool($value) ? 'Y' : 'N';
	}

	public static function isFeatureEnabled($documentType, $feature)
	{
		return in_array($feature, [\CBPDocumentService::FEATURE_SET_MODIFIED_BY]);
	}

	/**
	 * @param string $documentId
	 * @param string $workflowId
	 * @param int $status
	 * @param null|\CBPActivity $rootActivity
	 */
	public static function onWorkflowStatusChange($documentId, $workflowId, $status, $rootActivity)
	{
		if (
			$rootActivity
			&& $status === \CBPWorkflowStatus::Running
			&& !$rootActivity->workflow->isNew()
			&& TaskLimit::isLimitExceeded()
		)
		{
			throw new \Exception(Loc::getMessage('TASKS_BP_DOCUMENT_RESUME_RESTRICTED'));
		}
	}

	// Old & deprecated below
	public static function GetJSFunctionsForFields()
	{
		return '';
	}

	public static function publishDocument($documentId)
	{
		return true;
	}

	public static function unpublishDocument($documentId)
	{
		return true;
	}

	public static function lockDocument($documentId, $workflowId)
	{
		return true;
	}

	public static function unlockDocument($documentId, $workflowId)
	{
		return true;
	}

	public static function isDocumentLocked($documentId, $workflowId)
	{
		return false;
	}

	public static function getDocumentForHistory($documentId, $historyIndex)
	{
		return static::getDocument($documentId);
	}

	public static function recoverDocumentFromHistory($documentId, $arDocument)
	{
		return true;
	}

	private static function convertDateValue($value, $multiple = false)
	{
		if (!is_array($value))
		{
			$value = [$value];
		}

		$result = [];

		foreach ($value as $val)
		{
			if (is_object($val))
			{
				$result[] = (string) $val;
			}

			if ($val && is_scalar($val))
			{
				$result[] = (string) $val;
			}
		}

		$result = array_filter($result, fn($date) => \CheckDateTime($date));

		return $multiple ? $result : reset($result);
	}

	private static function setFlowMessages(array $fields): array
	{
		$flowId = (int)($fields['FLOW_ID'] ?? 0);

		if ($flowId < 1)
		{
			return $fields;
		}

		$userLang = self::getUserLanguage((int)$fields['RESPONSIBLE_ID']);
		$flowData = FlowRegistry::getInstance()->get($flowId, ['NAME', 'OWNER_ID']);

		$taskId = $fields['ID'];
		$groupId = $fields['GROUP_ID'] ?? null;
		$taskUrl = TaskPathMaker::getPath([
			'task_id' => $taskId,
			'group_id' => $groupId,
			'action' => 'view',
		]);

		$fields['HALF_TIME_BEFORE_EXPIRE_MESSAGE'] = Loc::getMessage(
			'TASKS_FLOW_NOTIFICATION_MESSAGE_HALF_TIME_BEFORE_EXPIRE_MESSAGE',
			[
				'#TASK_URL#' => $taskUrl,
				'#TASK_TITLE#' => $fields['TITLE'],
				'{taskId}' => $taskId,
			],
			$userLang
		);

		$flowOwnerId = $flowData?->getOwnerId() ?? 0;

		$flowUrl = (new FlowPathMaker(ownerId: $flowOwnerId))
			->addQueryParam('apply_filter', 'Y')
			->addQueryParam('ID_numsel', 'exact')
			->addQueryParam('ID_from', $flowId)
			->makeEntitiesListPath()
		;

		$fields['HIMSELF_ADMIN_TASK_NOT_TAKEN_MESSAGE'] = Loc::getMessage(
			'TASKS_FLOW_NOTIFICATION_MESSAGE_HIMSELF_ADMIN_TASK_NOT_TAKEN_MESSAGE',
			[
				'#FLOW_URL#' => $flowUrl,
				'#FLOW_TITLE#' => $flowData?->getName() ?? '',
				'{flowId}' => $flowId,
			],
			$userLang
		);

		return $fields;
	}

	private static function getUserLanguage(null|int $userId): null|string
	{
		if (!$userId)
		{
			return null;
		}

		$res = UserTable::query()
			->where('ID', $userId)
			->setSelect([
				'NOTIFICATION_LANGUAGE_ID'
			])
			->exec()
			->fetchObject()
		;

		return ($res)
			? $res->getNotificationLanguageId()
			: null
			;
	}
}
