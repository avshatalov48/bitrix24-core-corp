<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Localization\Loc;

use \Bitrix\Tasks\Manager\Task;
use \Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.task");

class TasksMailTaskComponent extends TasksTaskComponent
{
	protected $senderId = null;
	private $replaceUser = false;
	private $prevUser = false;

	protected static function checkRights(array $arParams, array $arResult, array $auxParams): ?Util\Error
	{
		$error = new Util\Error(Loc::getMessage('TASKS_TT_NOT_FOUND_OR_NOT_ACCESSIBLE'), 'ACCESS_DENIED.NO_TASK');

		$taskId = (int) $arParams[static::getParameterAlias('ID')];
		$oldTask = $taskId ? TaskModel::createFromId($taskId) : null;
		$accessCheckParams = null;
		$action = Tasks\Access\ActionDictionary::ACTION_TASK_READ;

		if (
			!array_key_exists('USER_ID', $arResult)
			|| !$arResult['USER_ID']
		)
		{
			return $error;
		}

		$res = (new Tasks\Access\TaskAccessController((int)$arResult['USER_ID']))->check($action, $oldTask, $accessCheckParams);
		if (!$res)
		{
			return $error;
		}

		return null;
	}

	protected function processExecutionStart()
	{
		if (!static::checkTasksModule())
		{
			return false;
		}

		parent::processExecutionStart();

		$taskId = (int)$this->arParams['ID'];
		$userId = (int)$this->arParams['USER_ID'];

		if (!$taskId || !$userId)
		{
			return false;
		}

		$currentUserId = \Bitrix\Tasks\Util\User::getId();
		$this->replaceUser = !$currentUserId;

		try
		{
			$taskData = CTaskItem::getInstance($taskId, $userId)->getData(false);

			if ($this->replaceUser || $currentUserId !== (int)$taskData['CREATED_BY'])
			{
				$this->replaceUser = true;
				$this->prevUser = $GLOBALS['USER'];
				$GLOBALS['USER'] = $this->makeFakeCUserClass($taskData['CREATED_BY']);
			}
		}
		catch (TasksException $e) // todo: get rid of catching TasksException when refactor exception mechanism
		{
			$this->replaceUser = false;

			if ($e->checkOfType(TasksException::TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE))
			{
				$this->errors->add(
					'ACCESS_DENIED.NO_TASK',
					'Task not found or not accessible'
				);

				return false;
			}
		}
		catch (\Bitrix\Tasks\Exception $e)
		{
			$this->replaceUser = false;
			// todo: replace 'INTERNAL_ERROR' when invent symbolic code mechanism
			$this->errors->add('INTERNAL_ERROR', $e->getMessageFriendly());

			return false;
		}

		return true;
	}

	/**
	 * @param int $userId
	 * @return CUser
	 */
	protected function makeFakeCUserClass(int $userId): CUser
	{
		$settings = [
			'id' => $userId,
			'user_group' => [],
		];

		$userGroupResult = CUser::GetUserGroupEx($userId);
		while ($userGroup = $userGroupResult->Fetch())
		{
			$settings['user_group'][] = $userGroup['GROUP_ID'];
		}

		$fakeCUserClass = new class extends CUser {
			protected static $settings = [];

			public static function init($settings)
			{
				static::$settings = $settings;
			}

			public function GetID()
			{
				return static::$settings['id'];
			}

			public static function GetUserGroup($ID)
			{
				return static::$settings['user_group'];
			}
		};
		$fakeCUserClass::init($settings);

		return $fakeCUserClass;
	}

	protected function processExecutionEnd()
	{
		if($this->replaceUser)
		{
			$GLOBALS['USER'] = $this->prevUser;
		}

		if($this->errors->checkHasFatals())
		{
			\Bitrix\Tasks\Integration\Mail::stopMailEventCompiler();
		}
	}

	protected static function getEffectiveUserId($arParams)
	{
		$sender = intval($arParams['RECIPIENT_ID']);
		if(!$sender)
		{
			$sender = intval($arParams['USER_ID']);
		}

		if($sender)
		{
			return $sender;
		}
		else
		{
			return parent::getEffectiveUserId($arParams);
		}
	}

	protected static function checkRestrictions(array &$arParams, array &$arResult, Collection $errors)
	{
		// no restriction check, override
	}

	protected static function checkUserRestrictions($userId, Collection $errors)
	{
		// no restrictions, free access
		return $userId;
	}

	protected static function checkExecuteDispatcher($request, Collection $errors, array $auxParams = array())
	{
		return false; // query dispatching is off for that component
	}

	protected function checkParameters()
	{
		static::tryParseNonNegativeIntegerParameter($this->arParams['ID']);
		if(!$this->arParams['ID']) // no task add allowed, only read existing
		{
			$this->errors->add('NO_TASK_ID_SPECIFIED', 'No task id specified');
		}

		parent::checkParameters();

		static::tryParseEnumerationParameter($this->arParams['ENTITY'], array('TASK', 'COMMENT'), 'TASK');
		static::tryParseEnumerationParameter($this->arParams['ENTITY_ACTION'], array('ADD', 'UPDATE'));

		static::tryParseNonNegativeIntegerParameter($this->arParams['USER_ID']);
		static::tryParseNonNegativeIntegerParameter($this->arParams['RECIPIENT_ID'], \Bitrix\Tasks\Util\User::getId()); // Bob

		$this->arParams['PREVIOUS_FIELDS'] = \Bitrix\Tasks\Util\Type::unSerializeArray($this->arParams['~PREVIOUS_FIELDS']); // tilda is required, as "PREVIOUS_FIELDS" contains broken serialization

		$this->arParams['SUB_ENTITY_SELECT'] = array(Task\CheckList::getCode(), Task\Tag::getCode(), Task\ParentTask::getCode());
		$this->arParams['AUX_DATA_SELECT'] = array('USER_FIELDS');

		static::tryParseStringParameter($this->arParams["URL"], \Bitrix\Tasks\Integration\Mail\Task::getDefaultPublicPath($this->arParams['ID']));
	}

	protected function getDataAux()
	{
		parent::getDataAux();

		$this->arResult['AUX_DATA']['CHANGES'] = $this->getChanges();
		$this->arResult['AUX_DATA']['SITES'] = array();

		$sites = \Bitrix\Tasks\Util\Site::getPair();
		$this->arResult['AUX_DATA']['SITE'] = $sites['INTRANET'];

		$this->senderId = $this->arResult['DATA']['TASK']['CREATED_BY']; // Alice

		// for file download in forum comments
		if (
			isset($this->arParams["RECIPIENT_ID"])
			&& intval($this->arParams["RECIPIENT_ID"]) > 0
		)
		{
			$backUrl = \Bitrix\Tasks\Integration\Mail\Task::getBackUrl(
				intval($this->arParams["RECIPIENT_ID"]),
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
		$fileData = array();
		$ufCode = \Bitrix\Tasks\Integration\Disk\UserField::getMainSysUFCode();
		if(is_array($this->arResult['DATA']['TASK'][$ufCode]))
		{
			$fileData = \Bitrix\Tasks\Integration\Disk::getAttachmentData($this->arResult['DATA']['TASK'][$ufCode]);
			foreach($fileData as $k => &$v)
			{
				if((string) $v['URL'] != '')
				{
					$v['URL'] = \Bitrix\Tasks\Integration\Mail\Task::getBackUrl(
						intval($this->arParams["RECIPIENT_ID"]),
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

	protected function collectTaskMembers()
	{
		parent::collectTaskMembers();

		$this->users2Get[] = $this->senderId;
		$this->users2Get[] = $this->arParams['RECIPIENT_ID'];

		if(is_array($this->arResult['AUX_DATA']['CHANGES']))
		{
			foreach($this->arResult['AUX_DATA']['CHANGES'] as $k => $v)
			{
				if($k == 'AUDITORS' || $k == 'ACCOMPLICES')
				{
					$this->collectMembersFromArray(explode(',', $v['TO_VALUE']));
				}
				if($k == 'RESPONSIBLE_ID' || $k == 'CREATED_BY')
				{
					$this->users2Get[] = $v['TO_VALUE'];
				}
			}
		}
	}

	protected function formatData()
	{
		parent::formatData();

		$this->arResult['DATA']['MEMBERS'] = array(
			'SENDER' => $this->arResult['DATA']['USER'][$this->senderId],
			'RECEIVER' => $this->arResult['DATA']['USER'][$this->arParams['RECIPIENT_ID']]
		);
	}

	protected function getChanges()
	{
		$previousFields = $this->arParams['PREVIOUS_FIELDS'];
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

		return CTaskLog::getChanges($this->arParams['PREVIOUS_FIELDS'], $this->arResult['DATA']['TASK']);
	}
}