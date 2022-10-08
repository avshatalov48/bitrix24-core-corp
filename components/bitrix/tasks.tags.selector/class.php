<?php

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2023 Bitrix
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class TasksTagsSelector extends \CBitrixComponent
	implements \Bitrix\Main\Errorable, \Bitrix\Main\Engine\Contract\Controllerable
{
	private $errorCollection;
	private $userId = 0;

	/**
	 * @param null $component
	 */
	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->init();
	}

	public function configureActions()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return [];
		}

		return [
			'updateTags' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
		];
	}

	/**
	 * @return array|\Bitrix\Main\Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}


	public function getErrorByCode($code)
	{

	}

	public function updateTagsAction($taskId, $tagIds = [])
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

		$oldTask = TaskModel::createFromId($taskId);
		$newTask = TaskModel::createFromRequest(['TAGS' => $tagIds]);

		$isAccess = (new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_SAVE, $oldTask, $newTask);

		if (!$isAccess)
		{
			$this->addForbiddenError();
			return $result;
		}

		$this->updateTask(
			$taskId,
			[
				'TAGS' => $tagIds,
			]
		);

		if ($this->errorCollection->checkNoFatals())
		{
			return null;
		}

		return [];
	}

	public function executeComponent()
	{
		$this->initParams();
		$this->includeComponentTemplate();
	}

	private function initParams(): void
	{
		$this->arResult['VALUE'] = [];

		if (array_key_exists('VALUE', $this->arParams))
		{
			if (is_array($this->arParams['VALUE']))
			{
				$this->arResult['VALUE'] = $this->arParams['VALUE'];
			}
			else if ($this->arParams['VALUE'])
			{
				$this->arResult['VALUE'] = explode(',', $this->arParams['VALUE']);
			}
		}
		$this->arResult['VALUE'] = array_map('trim', $this->arResult['VALUE']);
		$this->arResult['NAME'] = htmlspecialcharsbx($this->arParams['NAME']);

		if (isset($this->arParams['PATH_TO_TASKS']) && !empty($this->arParams['PATH_TO_TASKS']))
		{
			$this->arResult['PATH_TO_TASKS'] = $this->arParams["PATH_TO_TASKS"];
		}
		else
		{
			$this->arResult['PATH_TO_TASKS'] = "/company/personal/user/{$this->userId}/tasks/";
		}

		$this->arResult['CAN_EDIT'] = ($this->arParams['CAN_EDIT'] ?? false);

		$this->arResult['GROUP_ID'] = 0;
		if (array_key_exists('GROUP_ID', $this->arParams))
		{
			$this->arResult['GROUP_ID'] = (int) $this->arParams['GROUP_ID'];
		}

		$this->arResult['TASK_ID'] = 0;
		if (array_key_exists('TASK_ID', $this->arParams))
		{
			$this->arResult['TASK_ID'] = (int)$this->arParams['TASK_ID'];
		}

		$this->arResult['TEMPLATE_ID'] = 0;
		if (array_key_exists('TEMPLATE_ID', $this->arParams))
		{
			$this->arResult['TEMPLATE_ID'] = (int)$this->arParams['TEMPLATE_ID'];
		}

		$this->arResult['IS_SCRUM_TASK'] = ($this->arParams['IS_SCRUM_TASK'] ?? false);
	}

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
			$this->errorCollection->add('UNKNOWN_EXCEPTION', Loc::getMessage('TASKS_TS_UNEXPECTED_ERROR'), false, ['ui' => 'notification']);
		}
	}

	private function init()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}
		$this->userId = (int) \Bitrix\Tasks\Util\User::getId();
		$this->errorCollection = new \Bitrix\Tasks\Util\Error\Collection();
	}

	private function addForbiddenError()
	{
		$this->errorCollection->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'));
	}
}