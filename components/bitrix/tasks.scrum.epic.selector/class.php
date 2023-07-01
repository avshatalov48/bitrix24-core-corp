<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Scrum\Service\EpicService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\PushService;
use Bitrix\Tasks\Util;

class TasksScrumEpicSelectorComponent extends \CBitrixComponent implements Controllerable, Errorable
{
	const ERROR_UNKNOWN_SYSTEM_ERROR = 'TASKS_TSES_01';

	private $errorCollection;

	private $userId;

	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->errorCollection = new ErrorCollection();
	}

	public function onIncludeComponentLang()
	{
		$this->includeComponentLang(basename(__FILE__));
		Loc::loadMessages(__FILE__);
	}

	public function onPrepareComponentParams($params)
	{
		$params['epic'] = (is_array($params['epic'] ?? null) ? $params['epic'] : []);
		$params['groupId'] = (is_numeric($params['groupId'] ?? null) ? (int) $params['groupId'] : 0);
		$params['taskId'] = (is_numeric($params['taskId'] ?? null) ? (int) $params['taskId'] : 0);
		$params['canEdit'] = ($params['canEdit'] ?? false);
		$params['mode'] = (($params['mode'] ?? null) === 'edit' ? 'edit' : 'view');
		$params['inputName'] = (is_string($params['inputName'] ?? null) ? $params['inputName'] : '');

		return $params;
	}

	public function executeComponent()
	{
		try
		{
			$this->checkModules();
			$this->init();

			$this->arResult['groupId'] = $this->arParams['groupId'];
			$this->arResult['taskId'] = $this->arParams['taskId'];
			$this->arResult['epic'] = $this->arParams['epic'];
			$this->arResult['canEdit'] = $this->arParams['canEdit'] ? 'Y' : 'N';
			$this->arResult['mode'] = $this->arParams['mode'];
			$this->arResult['inputName'] = $this->arParams['inputName'];

			$this->includeComponentTemplate();
		}
		catch (SystemException $exception)
		{
			$this->includeErrorTemplate($exception->getMessage());
		}
	}

	public function changeTaskEpicAction(int $taskId, int $epicId)
	{
		$this->checkModules();

		$userId = Util\User::getId();

		if (!TaskAccessController::can($userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->errorCollection->setError(new Error('Access denied.'));

			return null;
		}

		$queryObject = TaskTable::getList([
			'filter' => [
				'ID' => $taskId,
			],
			'select' => ['GROUP_ID'],
		]);
		$taskData = $queryObject->fetch();

		$groupId = $taskData ? (int) $taskData['GROUP_ID'] : 0;

		if (!$this->canReadGroupTasks($userId, $groupId))
		{
			$this->errorCollection->setError(new Error('Access denied.'));

			return null;
		}

		$epicService = new EpicService();

		$epic = $epicService->getEpic($epicId);
		if ($epic->getId() && $epic->getGroupId() !== $groupId)
		{
			$this->errorCollection->setError(new Error('Access denied.'));

			return null;
		}

		$itemService = new ItemService();
		$pushService = (Loader::includeModule('pull') ? new PushService() : null);

		$scrumItem = $itemService->getItemBySourceId($taskId);

		$scrumItem->setEpicId($epicId);

		$itemService->changeItem($scrumItem, $pushService);
		if ($itemService->getErrors())
		{
			$this->errorCollection->setError(new Error('System error.', self::ERROR_UNKNOWN_SYSTEM_ERROR));

			return null;
		}

		return $epic->getId() ? $epic->toArray() : null;
	}

	public function configureActions()
	{
		return [];
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * @throws SystemException
	 */
	private function checkModules()
	{
		try
		{
			if (!Loader::includeModule('tasks'))
			{
				throw new SystemException('Cannot connect required modules.');
			}
			if (!Loader::includeModule('socialnetwork'))
			{
				throw new SystemException('Cannot connect required modules.');
			}
		}
		catch (LoaderException $exception)
		{
			throw new SystemException('Cannot connect required modules.');
		}
	}

	private function init()
	{
		$this->userId = Util\User::getId();
	}

	private function canReadGroupTasks(int $userId, int $groupId): bool
	{
		return Group::canReadGroupTasks($userId, $groupId);
	}

	private function includeErrorTemplate(string $errorMessage, string $code = ''): void
	{
		$this->arResult['ERROR'] = $errorMessage;
		$this->arResult['ERROR_CODE'] = ($code ? $code : self::ERROR_UNKNOWN_SYSTEM_ERROR);

		$this->includeComponentTemplate('error');
	}
}