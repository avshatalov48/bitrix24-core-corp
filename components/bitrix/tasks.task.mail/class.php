<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Manager\Task;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksMailTaskComponent extends TasksBaseComponent
{
	protected $task = null;
	protected $senderId = null;
	protected $groups2Get = [];

	public function executeComponent()
	{
		if (!$this->checkModules())
		{
			$this->arResult['ERROR'] = [
				'TYPE' => 'FATAL',
				'CODE' => 'MODULE_NOT_INSTALLED',
			];

			$this->includeComponentTemplate();
		}

		$this->errors = new Collection();

		if (!$this->checkPermission())
		{
			$this->handleErrors();
			$this->includeComponentTemplate();
		}
		if (!$this->checkParameters())
		{
			$this->handleErrors();
			$this->includeComponentTemplate();
		}

		$this->getAllData();
		$this->includeComponentTemplate();
		$this->processExecutionEnd();
	}

	protected function checkModules()
	{
		 return Loader::includeModule('tasks')
			 && Loader::includeModule('socialnetwork')
			 && Loader::includeModule('forum')
		 ;
	}

	protected function handleErrors()
	{
		foreach ($this->errors as $error)
		{
			$this->arResult['ERROR'][$error->getCode()] = $error->toArray();
		}
	}

	protected function checkPermission()
	{
		$userId = $this->getUserId();

		if (!$userId)
		{
			$this->errors->add('USER_NOT_DEFINED', 'Can not identify current user');

			return $this->errors->checkNoFatals();
		}

		$this->arResult['USER_ID'] = $userId;

		if (!CBXFeatures::IsFeatureEnabled('Tasks'))
		{
			$this->errors->add('TASKS_MODULE_NOT_AVAILABLE', Loc::getMessage("TASKS_TB_TASKS_MODULE_NOT_AVAILABLE"));
		}

		$accessError = $this->checkAccessRights();
		if ($accessError instanceof Util\Error)
		{
			$this->errors->add($accessError->getCode(), $accessError->getMessage(), $accessError->getType());
		}

		$taskId = (int)$this->arParams['ID'];
		if (
			$taskId
			&& ($task = CTaskItem::getInstanceFromPool($taskId, $this->arResult['USER_ID']))
			&& $task instanceof CTaskItem
		)
		{
			$this->task = $task;
		}

		return $this->errors->checkNoFatals();
	}

	protected function checkAccessRights(): ?Util\Error
	{
		$error = new Util\Error(Loc::getMessage('TASKS_TT_NOT_FOUND_OR_NOT_ACCESSIBLE'), 'ACCESS_DENIED.NO_TASK');

		$taskId = (int)$this->arParams['ID'];
		$oldTask = $taskId ? TaskModel::createFromId($taskId) : null;

		if (
			!array_key_exists('USER_ID', $this->arResult)
			|| !$this->arResult['USER_ID']
		)
		{
			return $error;
		}

		$res = (new Tasks\Access\TaskAccessController((int)$this->arResult['USER_ID']))->check(
			Tasks\Access\ActionDictionary::ACTION_TASK_READ,
			$oldTask,
		);
		if (!$res)
		{
			return $error;
		}

		return null;
	}

	protected function processExecutionEnd()
	{
		if ($this->errors->checkHasFatals())
		{
			\Bitrix\Tasks\Integration\Mail::stopMailEventCompiler();
		}
	}

	protected function getUserId()
	{
		$sender = (int)$this->arParams['RECIPIENT_ID'];
		if(!$sender)
		{
			$sender = (int)$this->arParams['USER_ID'];
		}

		if ($sender)
		{
			return $sender;
		}

		return false;
	}

	protected function checkParameters()
	{
		static::tryParseNonNegativeIntegerParameter($this->arParams['ID']);
		static::tryParseEnumerationParameter($this->arParams['ENTITY'], ['TASK', 'COMMENT'], 'TASK');
		static::tryParseEnumerationParameter($this->arParams['ENTITY_ACTION'], ['ADD', 'UPDATE']);
		static::tryParseNonNegativeIntegerParameter($this->arParams['USER_ID']);
		static::tryParseNonNegativeIntegerParameter($this->arParams['RECIPIENT_ID'], $this->arParams['USER_ID']); // Bob

		$this->arParams['PREVIOUS_FIELDS'] = Type::unSerializeArray($this->arParams['~PREVIOUS_FIELDS'] ?? ''); // tilda is required, as "PREVIOUS_FIELDS" contains broken serialization
		$this->arParams['SUB_ENTITY_SELECT'] = [Task\CheckList::getCode(), Task\Tag::getCode(), Task\ParentTask::getCode()];
		$this->arParams['AUX_DATA_SELECT'] = ['USER_FIELDS'];

		static::tryParseStringParameter($this->arParams["URL"], \Bitrix\Tasks\Integration\Mail\Task::getDefaultPublicPath($this->arParams['ID']));

		return $this->errors->checkNoFatals();
	}

	protected function getData()
	{
		$data = [];

		if ($this->task !== null)
		{
			$data = Task::get(
				$this->arResult['USER_ID'],
				$this->task->getId(),
				[
					'ENTITY_SELECT' => [
						'TAG',
						'CHECKLIST',
						'REMINDER',
						'PROJECTDEPENDENCE',
						'TEMPLATE',
						'RELATEDTASK',
					],
					'ESCAPE_DATA' => false,
					'ERRORS' => $this->errors,
				]
			);
		}
		else
		{
			$this->errors->add('TASKS_NOT_FOUND', 'Task not found');
		}

		if ($this->errors->checkHasFatals())
		{
			return;
		}

		$this->arResult['DATA']['TASK'] = $data['DATA'];
		$this->arResult['CAN']['TASK'] = $data['CAN'];

		$this->getDataAux();
		$this->collectProjects();
	}

	protected function getAuxData()
	{
	}

	protected function getDataAux()
	{
		$this->arResult['AUX_DATA'] = [];
		$this->getDataUserFields();

		$this->arResult['AUX_DATA']['CHANGES'] = $this->getChanges();
		$this->arResult['AUX_DATA']['SITES'] = [];

		$sites = \Bitrix\Tasks\Util\Site::getPair();
		$this->arResult['AUX_DATA']['SITE'] = $sites['INTRANET'];

		$this->senderId = $this->arResult['DATA']['TASK']['CREATED_BY']; // Alice

		// for file download in forum comments
		if (
			isset($this->arParams["RECIPIENT_ID"])
			&& (int)$this->arParams["RECIPIENT_ID"] > 0
		)
		{
			$backUrl = \Bitrix\Tasks\Integration\Mail\Task::getBackUrl(
				(int)$this->arParams["RECIPIENT_ID"],
				$this->arResult['DATA']['TASK']["ID"],
				$this->arParams["URL"],
				$this->arResult['AUX_DATA']['SITE']['SITE_ID']
			);

			if ($backUrl)
			{
				$this->arResult['AUX_DATA']["ENTITY_URL"] = $backUrl;
			}
		}

		// for file download in task body
		// todo: remove this when a special disk widget used
		$fileData = [];
		$ufCode = \Bitrix\Tasks\Integration\Disk\UserField::getMainSysUFCode();
		if(isset($this->arResult['DATA']['TASK'][$ufCode]) && is_array($this->arResult['DATA']['TASK'][$ufCode]))
		{
			$fileData = \Bitrix\Tasks\Integration\Disk::getAttachmentData($this->arResult['DATA']['TASK'][$ufCode]);
			foreach($fileData as $k => &$v)
			{
				if((string)$v['URL'] !== '')
				{
					$v['URL'] = \Bitrix\Tasks\Integration\Mail\Task::getBackUrl(
						(int)$this->arParams["RECIPIENT_ID"],
						$this->arResult['DATA']['TASK']["ID"],
						$v['URL'],
						$this->arResult['AUX_DATA']['SITE']['SITE_ID'],
						$this->arResult['AUX_DATA']["ENTITY_URL"]
					);
				}
			}
			unset($v);
		}
		$this->arResult['DATA']['ATTACHMENT'] = $fileData;
	}

	protected function getDataUserFields()
	{
		$this->arResult['AUX_DATA']['USER_FIELDS'] = Util\UserField\Task::getScheme(
			$this->task !== null ? $this->task->getId() : 0
		);

		// restore uf values from task data
		if (Type::isIterable($this->arResult['AUX_DATA']['USER_FIELDS']))
		{
			foreach ($this->arResult['AUX_DATA']['USER_FIELDS'] as $ufCode => $ufDesc)
			{
				if (isset($this->arResult['DATA']['TASK'][$ufCode]))
				{
					$this->arResult['AUX_DATA']['USER_FIELDS'][$ufCode]['VALUE'] = $this->arResult['DATA']['TASK'][$ufCode];
				}
			}
		}
	}

	protected function collectProjects()
	{
		if (
			isset($this->arResult['DATA']['TASK']['GROUP_ID'])
			&& $this->arResult['DATA']['TASK']['GROUP_ID']
		)
		{
			$this->groups2Get[] = ($this->arResult['DATA']['TASK']['GROUP_ID']);
		}
		elseif (
			isset($this->arResult['DATA']['TASK'][Task\Project::getCode(true)])
			&& $this->arResult['DATA']['TASK'][Task\Project::getCode(true)]
		)
		{
			$this->groups2Get[] = ($this->arResult['DATA']['TASK'][Task\Project::getCode(true)]['ID']);
		}
	}

	protected function getReferenceData()
	{
		$users = [
			$this->senderId,
			$this->arParams['RECIPIENT_ID']
		];

		$this->arResult['DATA']['GROUP'] = Group::getData($this->groups2Get, ['IMAGE_ID', 'AVATAR_TYPE']);
		$this->arResult['DATA']['USER'] = User::getData($users);
	}

	protected function formatData()
	{
		$data =& $this->arResult['DATA']['TASK'];

		if (Type::isIterable($data))
		{
			Task::extendData($data, $this->arResult['DATA']);

			// left for compatibility
			$data[Task::SE_PREFIX . 'PARENT'] = $data[Task\ParentTask::getCode(true)] ?? null;
		}

		$this->arResult['DATA']['MEMBERS'] = [
			'SENDER' => $this->arResult['DATA']['USER'][$this->senderId],
			'RECEIVER' => $this->arResult['DATA']['USER'][$this->arParams['RECIPIENT_ID']]
		];
	}

	protected function getChanges()
	{
		$previousFields = $this->arParams['PREVIOUS_FIELDS'] ?? [];
		foreach ($previousFields as $name => $value)
		{
			if (
				is_array($value)
				&& array_key_exists('FROM_VALUE', $value)
				&& array_key_exists('TO_VALUE', $value)
			)
			{
				$this->arParams['PREVIOUS_FIELDS'][$name] = $value['FROM_VALUE'];
			}
		}

		return CTaskLog::getChanges($previousFields, $this->arResult['DATA']['TASK']);
	}
}