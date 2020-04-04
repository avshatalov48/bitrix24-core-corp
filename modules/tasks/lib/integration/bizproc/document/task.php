<?
namespace Bitrix\Tasks\Integration\Bizproc\Document;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Tasks\Integration\Bizproc\Automation\Factory;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Main\Type\DateTime;

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
				break;
			}

			case \CBPCanUserOperateOperation::WriteDocument:
			{
				$creatorId = MemberTable::getList([
					'filter' => ['=TASK_ID' => $documentId, '=TYPE' => 'O'],
					'select' => ['USER_ID']
				])->fetch()['USER_ID'];

				return (int) $creatorId === (int) $userId;
				break;
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

		if ($operation === \CBPCanUserOperateOperation::CreateAutomation)
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
				'Options' => array(
					\CTasks::STATE_PENDING => Loc::getMessage('TASKS_BP_DOCUMENT_STATUS_PENDING_1'),
					\CTasks::STATE_IN_PROGRESS => Loc::getMessage('TASKS_BP_DOCUMENT_STATUS_IN_PROGRESS'),
					\CTasks::STATE_SUPPOSEDLY_COMPLETED => Loc::getMessage('TASKS_BP_DOCUMENT_STATUS_SUPPOSEDLY_COMPLETED'),
					\CTasks::STATE_COMPLETED => Loc::getMessage('TASKS_BP_DOCUMENT_STATUS_COMPLETED'),
					\CTasks::STATE_DEFERRED => Loc::getMessage('TASKS_BP_DOCUMENT_STATUS_DEFERRED'),
				)
			],
			'RESPONSIBLE_ID' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_RESPONSIBLE_ID'),
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
					\CTasks::MARK_POSITIVE => Loc::getMessage('TASKS_BP_DOCUMENT_MARK_POSITIVE'),
					\CTasks::MARK_NEGATIVE => Loc::getMessage('TASKS_BP_DOCUMENT_MARK_NEGATIVE')
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
			//'MULTITASK' => [
			//	'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_MULTITASK'),
			//	'Type' => 'bool',
			//],
			//'SITE_ID' => [
			//	'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_SITE_ID'),
			//	'Type' => 'string',
			//],
			'DECLINE_REASON' => [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_DECLINE_REASON'),
				'Type' => 'string',
				'Editable' => true
			],
			//'STAGE_ID' => [
			//	'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_STAGE_ID'),
			//	'Type' => 'int',
			//	'Editable' => true
			//],
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
			]
		];

		if (self::isPlanTask($documentType) || self::isPersonalTask($documentType))
		{
			$fields['MEMBER_ROLE'] = [
				'Name' => Loc::getMessage('TASKS_BP_DOCUMENT_MEMBER_ROLE'),
				'Type' => 'select',
				'Options' => [
					'O' => Loc::getMessage('TASKS_BP_DOCUMENT_MEMBER_ROLE_O'),
					'R' => Loc::getMessage('TASKS_BP_DOCUMENT_MEMBER_ROLE_R'),
					'A' => Loc::getMessage('TASKS_BP_DOCUMENT_MEMBER_ROLE_A'),
					'U' => Loc::getMessage('TASKS_BP_DOCUMENT_MEMBER_ROLE_U'),
				]
			];
		}

		return $fields;
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

		static::convertFieldsToDocument($fields);
		return $fields;
	}

	private static function getMemberRole($memberId, array $fields)
	{
		if ($memberId === (int) $fields['CREATED_BY'])
		{
			return 'O';
		}

		if ($memberId === (int) $fields['RESPONSIBLE_ID'])
		{
			return 'R';
		}

		foreach ($fields['ACCOMPLICES'] as $accomplice)
		{
			if ($memberId === (int) $accomplice)
			{
				return 'A';
			}
		}

		foreach ($fields['AUDITORS'] as $auditor)
		{
			if ($memberId === (int) $auditor)
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
			'DECLINE_REASON',
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
			$fields['PRIORITY'] = $fields['IS_IMPORTANT'] === 'Y' ? \CTasks::PRIORITY_HIGH : \CTasks::PRIORITY_AVERAGE;
			unset($fields['IS_IMPORTANT']);
		}

		//normalize date fields
		foreach (['DEADLINE', 'END_DATE_PLAN', 'START_DATE_PLAN'] as $dateField)
		{
			if (!isset($fields[$dateField]))
			{
				continue;
			}
			if (is_array($fields[$dateField]))
			{
				$fields[$dateField] = reset($fields[$dateField]);
			}
			if ($fields[$dateField] && !is_scalar($fields[$dateField]))
			{
				$fields[$dateField] = (string) $fields[$dateField];
			}
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

		$newUsers = [];
		foreach (array('CREATED_BY', 'RESPONSIBLE_ID', 'AUDITORS', 'ACCOMPLICES') as $code)
		{
			if (isset($fields[$code]))
			{
				if (!is_array($fields[$code]))
				{
					$newUsers[] = $fields[$code];
				}
				else
				{
					$newUsers = array_merge($newUsers, $fields[$code]);
				}
			}
		}
		if (!empty($newUsers))
		{
			\Bitrix\Tasks\Kanban\StagesTable::pinInStage($documentId, $newUsers);
		}

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
		return (int) substr($documentType, strlen('TASK_USER_'));
	}

	public static function isPersonalTask($documentType)
	{
		return (strpos($documentType, 'TASK_USER_') === 0);
	}

	public static function resolvePlanTaskType($userId)
	{
		return 'TASK_PLAN_'.(int)$userId;
	}

	public static function resolvePlanId($documentType)
	{
		return (int) substr($documentType, strlen('TASK_PLAN_'));
	}

	public static function isPlanTask($documentType)
	{
		return (strpos($documentType, 'TASK_PLAN_') === 0);
	}

	public static function resolveProjectTaskType($projectId)
	{
		return 'TASK_PROJECT_'.(int)$projectId;
	}

	public static function resolveProjectId($documentType)
	{
		return (int) substr($documentType, strlen('TASK_PROJECT_'));
	}

	public static function isProjectTask($documentType)
	{
		return (strpos($documentType, 'TASK_PROJECT_') === 0);
	}

	public static function getDocumentName($documentId)
	{
		$res = \CTasks::GetList([], ['ID' => (int) $documentId, 'CHECK_PERMISSIONS' => 'N'], ['TITLE']);
		if ($res && ($task = $res->Fetch()))
		{
			return $task['TITLE'];
		}
		return null;
	}

	public static function createAutomationTarget($documentType)
	{
		if (strpos($documentType, 'TASK_') === 0)
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
		return [];
	}

	public static function getUsersFromUserGroup($group, $documentId)
	{
		return [];
	}

	private static function convertFieldsToDocument(array &$fields)
	{
		$fields['IS_IMPORTANT'] = ($fields['PRIORITY'] > \CTasks::PRIORITY_AVERAGE) ? 'Y' : 'N';

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
			if ($fields['STATUS'] >= \CTasks::STATE_SUPPOSEDLY_COMPLETED && !empty($fields['CLOSED_DATE']))
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
	}

	public static function isFeatureEnabled($documentType, $feature)
	{
		return in_array($feature, [\CBPDocumentService::FEATURE_SET_MODIFIED_BY]);
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
}