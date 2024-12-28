<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Collection;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\Log\LogFacade;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Ui\Avatar;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Access\TemplateAccessController;
use Bitrix\Tasks\Access\Model\TemplateModel;
use Bitrix\Tasks\Integration\Extranet;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksWidgetMemberSelectorComponent extends TasksBaseComponent
	implements \Bitrix\Main\Errorable, \Bitrix\Main\Engine\Contract\Controllerable
{
	public const CONTEXT_TASK = 'task';
	public const CONTEXT_TEMPLATE = 'template';

	private const ALLOWED_AVATAR_SIZE = 100;

	/** @var \Bitrix\Tasks\Util\Error\Collection */
	private $errorCollection;

	protected function checkParameters()
	{
		if(!Type::isIterable($this->arParams['DATA']))
		{
			$this->arParams['DATA'] = array();
		}

		if (!array_key_exists('CONTEXT', $this->arParams))
		{
			$this->arParams['CONTEXT'] = self::CONTEXT_TASK;
		}

		if ($this->arParams['CONTEXT'] !== self::CONTEXT_TEMPLATE)
		{
			foreach ($this->arParams['DATA'] as $k => $row)
			{
				if (!(int)($row['ID'] ?? null))
				{
					unset($this->arParams['DATA'][$k]);
				}
			}
		}

		static::tryParseBooleanParameter($this->arParams['IS_FLOW_FORM']);

		static::tryParseArrayParameter($this->arParams['INPUT_TEMPLATE_SET']);
		static::tryParseIntegerParameter($this->arParams['MIN'], 0);
		static::tryParseIntegerParameter($this->arParams['MAX'], 99999);

		static::tryParseStringParameter($this->arParams['CHECK_ABSENCE'], 'Y');

		$supportedTypes = array('USER', 'USER.EXTRANET', 'USER.MAIL', 'PROJECT', 'DEPARTMENT');
		static::tryParseArrayParameter($this->arParams['TYPES'], $supportedTypes);
		$this->arParams['TYPES'] = array_map(function(){return true;}, array_flip(array_intersect($this->arParams['TYPES'], $supportedTypes)));

		$supportedAttributes = array('ID', 'NAME', 'LAST_NAME', 'EMAIL', 'VALUE');
		static::tryParseArrayParameter($this->arParams['ATTRIBUTE_PASS'], $supportedAttributes);
		$this->arParams['ATTRIBUTE_PASS'] = array_intersect($this->arParams['ATTRIBUTE_PASS'], $supportedAttributes);

		$this->arResult['TASK_LIMIT_EXCEEDED'] = static::tryParseBooleanParameter($this->arParams['TASK_LIMIT_EXCEEDED']);
		$this->arResult['taskMailUserIntegrationEnabled'] = static::tryParseBooleanParameter($this->arParams['taskMailUserIntegrationEnabled'], true);
		$this->arResult['taskMailUserIntegrationFeatureId'] = static::tryParseStringParameterStrict($this->arParams['taskMailUserIntegrationFeatureId'], '');
		$this->arResult['viewSelectorEnabled'] = static::tryParseBooleanParameter($this->arParams['viewSelectorEnabled'], true);

		$this->arResult['isProjectLimitExceeded'] = static::tryParseBooleanParameter($this->arParams['isProjectLimitExceeded']);
		$this->arResult['projectFeatureId'] = static::tryParseStringParameterStrict($this->arParams['projectFeatureId'], '');
		$this->arResult['isCollab'] = static::tryParseBooleanParameter($this->arParams['IS_COLLAB']);
		$this->arResult['isCollaber'] = static::tryParseBooleanParameter($this->arParams['isCollaber']);
		$this->arResult['isNeedShowPreselectedCollabHint'] = static::tryParseBooleanParameter($this->arParams['isNeedShowPreselectedCollabHint']);

		return $this->errors->checkNoFatals();
	}

	public function configureActions()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return [];
		}

		return [
			'setResponsible' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'setAuditors' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'setAccomplices' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'enterAuditor' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'leaveAuditor' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'setProject' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'isAbsence' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'getDestination' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'setDestination' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'getResponsible' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				]
			]
		];
	}

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->init();
	}

	public function getErrors()
	{
		if (!empty($this->componentId))
		{
			return parent::getErrors();
		}
		return $this->errorCollection->toArray();
	}
	/**
	 * @param $context
	 *
	 * Used for legacy member selector only
	 * @Deprecated
	 */
	public function getDestinationAction($context = 'TASKS')
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		if(!in_array($context, ['TASKS', 'TASKS_RIGHTS']))
		{
			$this->addForbiddenError();
			return [];
		}

		$params = [
			'AVATAR_WIDTH' => static::ALLOWED_AVATAR_SIZE,
			'AVATAR_HEIGHT' => static::ALLOWED_AVATAR_SIZE,
			'USE_PROJECTS' => 'Y'
		];

		return \Bitrix\Tasks\Integration\SocialNetwork::getLogDestination($context, $params);
	}

	/**
	 * @param $items
	 * @param $context
	 * @return array|null
	 * @throws \Bitrix\Main\LoaderException
	 *
	 * Used for legacy member selector only
	 * @Deprecated
	 */
	public function setDestinationAction($items = [], $context = 'TASKS')
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		if(!in_array($context, ['TASKS', 'TASKS_RIGHTS']))
		{
			$this->addForbiddenError();
			return [];
		}

		\Bitrix\Tasks\Integration\SocialNetwork::setLogDestinationLast($items, $context);

		return [];
	}

	/**
	 * @param $userIds
	 * @return array|false|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function isAbsenceAction($userIds)
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		$userIds = array_map(
			function ($el) {return (int)$el;},
			$userIds
		);

		// just filter accessible only
		$users = \Bitrix\Tasks\Integration\SocialNetwork\User::getUsers($userIds);

		$userIds = [];
		foreach ($users as $user)
		{
			$userIds[] = $user->getId();
		}

		if (empty($userIds))
		{
			return null;
		}

		return User::isAbsence($userIds);
	}

	/**
	 * @param $taskId
	 * @param $groupId
	 * @param $context
	 * @return array|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function setProjectAction($taskId, $groupId, $context = self::CONTEXT_TASK)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		$groupId = (int) $groupId;

		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if ($context && $context === self::CONTEXT_TEMPLATE)
		{
			$oldTemplate = TemplateModel::createFromId(($taskId));
			$newTemplate = clone $oldTemplate;
			$newTemplate->setGroupId($groupId);

			$isAccess = (new TemplateAccessController($this->userId))->check(ActionDictionary::ACTION_TEMPLATE_SAVE, $oldTemplate, $newTemplate);
		}
		else
		{
			$oldTask = TaskModel::createFromId(($taskId));
			$newTask = TaskModel::createFromRequest(['GROUP_ID' => $groupId]);

			$isAccess = (new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_SAVE, $oldTask, $newTask);
		}

		if (!$isAccess)
		{
			$this->addForbiddenError();
			return $result;
		}

		if ($context === self::CONTEXT_TEMPLATE)
		{
			$this->updateTemplate(
				$taskId,
				[
					'GROUP_ID' => $groupId,
				]
			);
		}
		else
		{
			$this->updateTask(
				$taskId,
				[
					'GROUP_ID' => $groupId,
				]
			);
		}


		if ($this->errorCollection->checkNoFatals())
		{
			if (!empty($groupId))
			{
				$group = WorkgroupTable::getById($groupId)->fetch();
				$owner = $group === false ? '' : $group['NAME'];
			}
			else
			{
				$owner = CurrentUser::get()->getFirstName() . ' ' .CurrentUser::get()->getLastName();
			}
			return [
				'owner' => $owner,
			];
		}

		return [];
	}

	/**
	 * @param $taskId
	 * @return array|null
	 * @throws CTaskAssertException
	 * @throws TasksException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function enterAuditorAction($taskId, $context = self::CONTEXT_TASK)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if ($context && $context === self::CONTEXT_TEMPLATE)
		{
			$isAccess = false;
		}
		else
		{
			$isAccess = TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskId);
		}

		if (!$isAccess)
		{
			$this->addForbiddenError();
			return $result;
		}

		try
		{
			$task = \CTaskItem::getInstance($taskId, $this->userId);
			$task->startWatch();
		}
		catch (Bitrix\Tasks\Exception $e)
		{
			$this->errorCollection->add('ACTION_ERROR.UNEXPECTED_ERROR', $e->getFirstErrorMessage(), false, ['ui' => 'notification']);
			return $result;
		}

		return $result;
	}

	/**
	 * @param $taskId
	 * @return array|null
	 * @throws CTaskAssertException
	 * @throws TasksException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function leaveAuditorAction($taskId, $context = self::CONTEXT_TASK)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if ($context && $context === self::CONTEXT_TEMPLATE)
		{
			$isAccess = false;
		}
		else
		{
			$isAccess = TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskId);
		}

		if (!$isAccess)
		{
			$this->addForbiddenError();
			return $result;
		}

		try
		{
			$task = \CTaskItem::getInstance($taskId, $this->userId);
			$task->stopWatch();
		}
		catch (Bitrix\Tasks\Exception $e)
		{
			$this->errorCollection->add('ACTION_ERROR.UNEXPECTED_ERROR', $e->getFirstErrorMessage(), false, ['ui' => 'notification']);
			return $result;
		}

		return $result;
	}

	/**
	 * @param $taskId
	 * @param $context
	 * @param $data
	 * @return array|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function setResponsibleAction($taskId, $data = null, $context = self::CONTEXT_TASK)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if ($context && $context === self::CONTEXT_TEMPLATE)
		{
			$oldTemplate = TemplateModel::createFromId((int)$taskId);
			$newTemplate = clone $oldTemplate;

			$members = $newTemplate->getMembers();
			$members[RoleDictionary::ROLE_RESPONSIBLE] = [];
			if (is_array($data))
			{
				foreach ($data as $responsible)
				{
					$members[RoleDictionary::ROLE_RESPONSIBLE][] = (int)$responsible['ID'];
				}
			}
			$newTemplate->setMembers($members);

			$isAccess = (new TemplateAccessController($this->userId))->check(ActionDictionary::ACTION_TEMPLATE_SAVE, $oldTemplate, $newTemplate);
		}
		else
		{
			$oldTask = TaskModel::createFromId((int)$taskId);
			$newTask = clone $oldTask;

			$members = $newTask->getMembers();
			$members[RoleDictionary::ROLE_RESPONSIBLE] = [];
			if (is_array($data))
			{
				foreach ($data as $responsible)
				{
					$members[RoleDictionary::ROLE_RESPONSIBLE][] = (int)$responsible['ID'];
				}
			}
			$newTask->setMembers($members);

			$isAccess = (new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_CHANGE_RESPONSIBLE, $oldTask, $newTask);
		}

		if (!$isAccess)
		{
			$this->addForbiddenError();
			return $result;
		}

		if ($context === self::CONTEXT_TEMPLATE)
		{
			if (!is_array($data))
			{
				$data = [];
			}
			$this->updateTemplate(
				$taskId,
				[
					'RESPONSIBLE_ID' => !empty($data) ? $data[0]['ID'] : 0,
					'RESPONSIBLES' => serialize(array_column($data, 'ID')),
				]
			);
		}
		else
		{
			$this->updateTask(
				$taskId,
				[
					'SE_RESPONSIBLE' => $data,
				]
			);
		}

		if (!$this->errorCollection->checkNoFatals())
		{
			return null;
		}

		return $result;
	}

	/**
	 * @param $taskId
	 * @param $context
	 * @param $data
	 * @return array|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function setAuditorsAction($taskId, $data = null, $context = self::CONTEXT_TASK)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if ($context && $context === self::CONTEXT_TEMPLATE)
		{
			$oldTemplate = TemplateModel::createFromId($taskId);
			$newTemplate = clone $oldTemplate;

			$members = $newTemplate->getMembers();
			$members[RoleDictionary::ROLE_AUDITOR] = [];
			if (is_array($data))
			{
				foreach ($data as $auditor)
				{
					$members[RoleDictionary::ROLE_AUDITOR][] = (int)$auditor['ID'];
				}
			}

			$newTemplate->setMembers($members);

			$isAccess =
				(new TemplateAccessController($this->userId))
					->check(ActionDictionary::ACTION_TEMPLATE_SAVE, $oldTemplate, $newTemplate)
			;
		}
		else
		{
			$oldTask = TaskModel::createFromId($taskId);
			$newTask = TaskModel::createFromRequest(['SE_AUDITOR' => $data]);

			$isAccess =
				(new TaskAccessController($this->userId))
					->check(ActionDictionary::ACTION_TASK_SAVE, $oldTask, $newTask)
			;
		}

		if (!$isAccess)
		{
			$this->addForbiddenError();
			return $result;
		}

		if ($context === self::CONTEXT_TEMPLATE)
		{
			if (!is_array($data))
			{
				$data = [];
			}
			$this->updateTemplate(
				$taskId,
				[
					'AUDITORS' => serialize(array_column($data, 'ID')),
				]
			);
		}
		else
		{
			$this->updateTask(
				$taskId,
				[
					'SE_AUDITOR' => $data,
				]
			);
		}

		if ($this->errorCollection->checkNoFatals())
		{
			return null;
		}

		return [];
	}

	/**
	 * @param $taskId
	 * @param $context
	 * @param $data
	 * @return array|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function setAccomplicesAction($taskId, $data = null, $context = self::CONTEXT_TASK)
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		$result = [];

		if ($context && $context === self::CONTEXT_TEMPLATE)
		{
			$oldTemplate = TemplateModel::createFromId((int)$taskId);
			$newTemplate = clone $oldTemplate;

			$members = $newTemplate->getMembers();
			$members[RoleDictionary::ROLE_ACCOMPLICE] = [];
			if (is_array($data))
			{
				foreach ($data as $accomplice)
				{
					$members[RoleDictionary::ROLE_ACCOMPLICE][] = (int)$accomplice['ID'];
				}
			}
			$newTemplate->setMembers($members);

			$isAccess = (new TemplateAccessController($this->userId))->check(ActionDictionary::ACTION_TEMPLATE_SAVE, $oldTemplate, $newTemplate);
		}
		else
		{
			$oldTask = TaskModel::createFromId((int)$taskId);
			$newTask = clone $oldTask;

			$members = $newTask->getMembers();
			$members[RoleDictionary::ROLE_ACCOMPLICE] = [];
			if (is_array($data))
			{
				foreach ($data as $accomplice)
				{
					$members[RoleDictionary::ROLE_ACCOMPLICE][] = (int)$accomplice['ID'];
				}
			}
			$newTask->setMembers($members);

			$isAccess = (new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_CHANGE_ACCOMPLICES, $oldTask, $newTask);
		}

		if (!$isAccess)
		{
			$this->addForbiddenError();
			return $result;
		}

		if ($context === self::CONTEXT_TEMPLATE)
		{
			if (!is_array($data))
			{
				$data = [];
			}
			$this->updateTemplate(
				$taskId,
				[
					'ACCOMPLICES' => serialize(array_column($data, 'ID')),
				]
			);
		}
		else
		{
			$this->updateTask(
				$taskId,
				[
					'SE_ACCOMPLICE' => $data,
				]
			);
		}

		if ($this->errorCollection->checkNoFatals())
		{
			return null;
		}

		return [];
	}

	/**
	 * @param string $code
	 * @return \Bitrix\Main\Error|void
	 */
	public function getErrorByCode($code)
	{
		// TODO: Implement getErrorByCode() method.
	}

	protected function init()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		$this->setUserId();
		$this->errorCollection = new \Bitrix\Tasks\Util\Error\Collection();
	}

	/**
	 * @param int $taskId
	 * @param array $data
	 */
	private function updateTask(int $taskId, array $data)
	{
		try
		{
			\Bitrix\Tasks\Manager\Task::update(
				$this->userId,
				$taskId,
				$data,
				[
					'PUBLIC_MODE' => true,
					'ERRORS' => $this->errorCollection,
				]
			);
		}
		catch (TasksException $e)
		{
			LogFacade::logThrowable($e, 'TASKS_DEBUG_MEMBER_SELECTOR');
			$messages = @unserialize($e->getMessage(), ['allowed_classes' => false]);
			if (is_array($messages))
			{
				foreach ($messages as $message)
				{
					$this->errorCollection->add('TASK_EXCEPTION', $message['text'], false, ['ui' => 'notification']);
				}
			}
		}
		catch (\Exception $e)
		{
			LogFacade::logThrowable($e, 'TASKS_DEBUG_MEMBER_SELECTOR');
			$this->errorCollection->add('UNKNOWN_EXCEPTION', Loc::getMessage('TASKS_WMS_UNEXPECTED_ERROR'), false, ['ui' => 'notification']);
		}
	}

	public function getResponsibleAction(int $taskId): ?array
	{
		if ($taskId <= 0)
		{
			return [];
		}

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->addForbiddenError();
			return null;
		}

		$task = TaskRegistry::getInstance()->getObject($taskId);
		if (is_null($task))
		{
			return [];
		}

		if (
			!in_array($this->userId, $task->getAllMemberIds(), true)
		)
		{
			return [];
		}

		$responsibleId = $task->getResponsibleId();

		$responsible = User::getData([$responsibleId], [
			'ID',
			'NAME',
			'LAST_NAME',
			'EMAIL',
			'SECOND_NAME',
			'WORK_POSITION',
			'PERSONAL_PHOTO',
		])[$responsibleId];

		return
		[
			'id' => $responsible['ID'],
			'nameFormatted' => User::formatName($responsible),
			'entityType' => RoleDictionary::ROLE_AUDITOR,
			'nameTemplate' => 'default',
			'WORK_POSITION' => $responsible['WORK_POSITION'],
			'AVATAR' => Avatar::getSrc($responsible['PERSONAL_PHOTO']),
			'type' =>
				[
					'extranet' => \Bitrix\Tasks\Integration\Extranet\User::isExtranet($responsible['ID']),
				],
		];
	}

	public function getTaskGroupAction(int $taskId): ?array
	{
		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->addForbiddenError();

			return null;
		}

		$task = TaskRegistry::getInstance()->getObject($taskId);
		if (null === $task)
		{
			return null;
		}

		$groupId = $task->getGroupId();
		if ($groupId === 0)
		{
			return null;
		}

		return Group::getById($groupId)?->toArray();
	}

	public function getMemberAction(array $userIds, int $taskId): ?array
	{
		Collection::normalizeArrayValuesByInt($userIds, false);

		if (empty($userIds) || $taskId <= 0)
		{
			return [];
		}

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->addForbiddenError();
			return null;
		}

		if (\Bitrix\Tasks\Integration\Extranet\User::isExtranet($this->userId))
		{
			return [];
		}

		$task = TaskRegistry::getInstance()->getObject($taskId);
		if (null === $task)
		{
			return [];
		}

		if (
			!in_array($this->userId, $task->getAllMemberIds(), true)
		)
		{
			return [];
		}

		foreach ($userIds as $userId)
		{
			if (!in_array($userId, $task->getAuditorMembersIds(), true))
			{
				return [];
			}
		}

		$users = User::getData($userIds, [
			'ID',
			'NAME',
			'LAST_NAME',
			'EMAIL',
			'SECOND_NAME',
			'WORK_POSITION',
			'PERSONAL_PHOTO',
		]);

		$response = [];
		foreach ($users as $user)
		{
			$isExtranet = Extranet\User::isExtranet((int)$user['ID']);
			$response[] = [
				'id' => $user['ID'],
				'nameFormatted' => User::formatName($user),
				'entityType' => RoleDictionary::ROLE_AUDITOR,
				'nameTemplate' => 'default',
				'WORK_POSITION' => $user['WORK_POSITION'],
				'AVATAR' => Avatar::getSrc($user['PERSONAL_PHOTO']),
				'type' => [
					'extranet' => $isExtranet,
					'collaber' => $isExtranet && Extranet\User::isCollaber((int)$user['ID']),
				],
			];
		}

		return $response;
	}

	/**
	 * @param int $templateId
	 * @param array $data
	 * @return void
	 */
	private function updateTemplate(int $templateId, array $data)
	{
		try
		{
			\Bitrix\Tasks\Manager\Task\Template::update(
				$this->userId,
				$templateId,
				$data,
				[
					'ERRORS' => $this->errorCollection,
				]
			);
		}
		catch (TasksException $e)
		{
			$messages = @unserialize($e->getMessage(), ['allowed_classes' => false]);
			if (is_array($messages))
			{
				foreach ($messages as $message)
				{
					$this->errorCollection->add('TASK_EXCEPTION', $message['text'], false, ['ui' => 'notification']);
				}
			}
		}
		catch (\Exception $e)
		{
			$this->errorCollection->add('UNKNOWN_EXCEPTION', Loc::getMessage('TASKS_WMS_UNEXPECTED_ERROR'), false, ['ui' => 'notification']);
		}
	}

	private function setUserId()
	{
		$this->userId = (int) \Bitrix\Tasks\Util\User::getId();
	}

	private function addForbiddenError()
	{
		$this->errorCollection->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'));
	}
}
