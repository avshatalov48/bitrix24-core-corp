<?php

/**
 * Bitrix Framework
 *
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Forum\MessageTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\Access\ResultAccessController;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Action\Filter\BooleanFilter;
use Bitrix\Tasks\Helper\Analytics;
use Bitrix\Tasks\Internals\Marketing\OneOff\ResultTutorial;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\Result\ResultManager;
use Bitrix\Tasks\Internals\Task\Result\ResultTable;
use Bitrix\Tasks\Util\User;

class TasksWidgetResult extends CBitrixComponent implements Errorable, Controllerable
{
	private const AVATAR_SIZE = 100;

	private ErrorCollection $errorCollection;
	private int $userId;

	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->errorCollection = new ErrorCollection();
		$this->userId = CurrentUser::get()->getId();
	}

	/**
	 * @throws LoaderException
	 */
	public function configureActions(): array
	{
		if (!Loader::includeModule('tasks'))
		{
			return [];
		}

		return [
			'createFromComment' => [
				'+prefilters' => [
					new BooleanFilter(),
				],
			],
			'deleteFromComment' => [
				'+prefilters' => [
					new BooleanFilter(),
				],
			],
			'getResults' => [
				'+prefilters' => [
					new BooleanFilter(),
				],
			],
			'disableTutorial' => [
				'+prefilters' => [
					new BooleanFilter(),
				],
			],
		];
	}

	public function executeComponent()
	{
		try
		{
			$this->init();

			$access = TaskAccessController::can($this->arParams['USER_ID'], ActionDictionary::ACTION_TASK_READ,
				$this->arParams['TASK_ID']);
			if (!$access)
			{
				return;
			}

			$this->loadData();
			$this->includeComponentTemplate();
		}
		catch (SystemException)
		{
		}
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws ArgumentTypeException
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function createFromCommentAction($commentId)
	{
		$commentId = (int)$commentId;
		if (!$commentId)
		{
			return null;
		}

		if (
			!Loader::includeModule('tasks')
			|| !Loader::includeModule('forum')
		)
		{
			return null;
		}

		$comment = MessageTable::getById($commentId)->fetchObject();
		if (!$comment)
		{
			return null;
		}

		$taskId = (int)str_replace('TASK_', '', $comment->getXmlId());

		if (
			(
				!UserModel::createFromId($this->userId)->isAdmin()
				&& $comment->getAuthorId() !== $this->userId
			)
			|| !TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskId)
		)
		{
			return null;
		}

		$result = (new ResultManager($this->userId))->createFromComment($commentId, false);
		if ($result)
		{
			Analytics::getInstance($this->userId)->onStatusSummaryAdd(Analytics::ELEMENT['comment_context_menu']);
		}

		return null;
	}

	/**
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function deleteFromCommentAction(int $commentId)
	{
		if ($commentId <= 0)
		{
			return null;
		}

		if (
			!Loader::includeModule('tasks')
			|| !Loader::includeModule('forum')
		)
		{
			return null;
		}

		$result = ResultTable::getByCommentId($commentId);
		if (is_null($result))
		{
			return null;
		}

		if (
			!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $result->getTaskId())
			|| !ResultAccessController::can($this->userId, ActionDictionary::ACTION_TASK_REMOVE_RESULT,
				$result->getId())
		)
		{
			return null;
		}

		(new ResultManager($this->userId))->deleteByComment($commentId);
		return null;
	}

	public function getResultsAction($taskId, $mode = null)
	{
		$taskId = (int)$taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$this->arParams['USER_ID'] = User::getId();
		$this->arParams['TASK_ID'] = $taskId;

		$access = TaskAccessController::can($this->arParams['USER_ID'], ActionDictionary::ACTION_TASK_READ, $taskId);
		if (!$access)
		{
			return null;
		}

		if ($mode === 'mobile')
		{
			$this->setTemplateName('mobile');
		}

		try
		{
			$this->loadData();

			ob_start();
			$this->includeComponentTemplate('results');
			$html = ob_get_clean();

			return $html;
		}
		catch (SystemException)
		{
			return null;
		}
	}

	/**
	 * @throws LoaderException
	 */
	public function disableTutorialAction()
	{
		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		(new ResultTutorial($this->userId))->disable();
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
	private function init(): void
	{
		$this->arParams['USER_ID'] = User::getId();

		$this->arParams['TASK_ID'] = (int)$this->arParams['TASK_ID'];
		if (!$this->arParams['TASK_ID'])
		{
			throw new SystemException(Loc::getMessage('TASKS_RESULTS_SYSTEM_ERROR'));
		}

		$this->arParams['RESPONSIBLE'] = $this->arParams['RESPONSIBLE'] ? (int)$this->arParams['RESPONSIBLE'] : 0;
		$this->arParams['ACCOMPLICES'] = $this->arParams['ACCOMPLICES'] ?? [];
	}

	private function loadData(): void
	{
		$this->arResult['RESULT_LIST'] = (new ResultManager($this->arParams['USER_ID']))->getTaskResults($this->arParams['TASK_ID']);

		$userIds = [];
		foreach ($this->arResult['RESULT_LIST'] as $result)
		{
			$userIds[] = $result->getCreatedBy();
		}

		$this->loadTask($this->arParams['TASK_ID']);
		$this->loadUsers($userIds);
		$this->loadUfInfo();
	}

	private function loadTask(int $taskId)
	{
		$this->arResult['TASK_DATA'] = TaskRegistry::getInstance()->get($taskId);
	}

	private function loadUfInfo()
	{
		global $USER_FIELD_MANAGER;
		$this->arResult['UF'] = $USER_FIELD_MANAGER->getUserFields(ResultTable::getUfId());
	}

	private function loadUsers(array $userIds): void
	{
		$this->arResult['USERS'] = [];
		if (empty($userIds))
		{
			return;
		}

		$this->arResult['USERS'] = User::getData(array_unique($userIds));

		foreach ($this->arResult['USERS'] as $userId => $user)
		{
			$fileFields = CFile::resizeImageGet(
				$user['PERSONAL_PHOTO'],
				['width' => self::AVATAR_SIZE, 'height' => self::AVATAR_SIZE],
				BX_RESIZE_IMAGE_EXACT,
				false,
				false,
				true
			);

			$this->arResult['USERS'][$userId]['AVATAR'] = ($fileFields && $fileFields['src'] ? $fileFields['src'] : '');
		}
	}
}