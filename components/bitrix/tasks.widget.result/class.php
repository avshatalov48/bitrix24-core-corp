<?php

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\Task\Result\ResultManager;

class TasksWidgetResult extends \CBitrixComponent
	implements \Bitrix\Main\Errorable, \Bitrix\Main\Engine\Contract\Controllerable
{
	private const AVATAR_SIZE = 100;

	private $errorCollection;

	/**
	 * @param null $component
	 */
	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->errorCollection = new \Bitrix\Main\ErrorCollection();
	}

	public function configureActions()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return [];
		}

		return [
			'createFromComment' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'deleteFromComment' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'getResults' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'disableTutorial' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			]
		];
	}

	/**
	 * @return mixed|void|null
	 */
	public function executeComponent()
	{
		try
		{
			$this->init();

			$access = \Bitrix\Tasks\Access\TaskAccessController::can($this->arParams['USER_ID'], \Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_READ, $this->arParams['TASK_ID']);
			if (!$access)
			{
				return;
			}

			$this->loadData();
			$this->includeComponentTemplate();
		}
		catch (\Bitrix\Main\SystemException $exception)
		{

		}
	}

	/**
	 * @param $commentId
	 * @return null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function createFromCommentAction($commentId)
	{
		$commentId = (int) $commentId;
		if (!$commentId)
		{
			return null;
		}

		if (
			!\Bitrix\Main\Loader::includeModule('tasks')
			|| !\Bitrix\Main\Loader::includeModule('forum')
		)
		{
			return null;
		}

		$userId = \Bitrix\Tasks\Util\User::getId();

		$comment = \Bitrix\Forum\MessageTable::getById($commentId)->fetchObject();
		if (!$comment)
		{
			return null;
		}

		$taskId = (int) str_replace('TASK_', '', $comment->getXmlId());

		if (
			(
				!\Bitrix\Tasks\Access\Model\UserModel::createFromId($userId)->isAdmin()
				&& $comment->getAuthorId() !== $userId
			)
			|| !\Bitrix\Tasks\Access\TaskAccessController::can($userId, \Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_READ, $taskId)
		)
		{
			return null;
		}

		$result = (new ResultManager($userId))->createFromComment($commentId, false);
		return null;
	}

	public function deleteFromCommentAction($commentId)
	{
		$commentId = (int) $commentId;
		if (!$commentId)
		{
			return null;
		}

		if (
			!\Bitrix\Main\Loader::includeModule('tasks')
			|| !\Bitrix\Main\Loader::includeModule('forum')
		)
		{
			return null;
		}

		$userId = \Bitrix\Tasks\Util\User::getId();

		$comment = \Bitrix\Forum\MessageTable::getById($commentId)->fetchObject();
		if (!$comment)
		{
			return null;
		}

		$taskId = (int) str_replace('TASK_', '', $comment->getXmlId());

		if (
			(
				!\Bitrix\Tasks\Access\Model\UserModel::createFromId($userId)->isAdmin()
				&& $comment->getAuthorId() !== $userId
			)
			|| !\Bitrix\Tasks\Access\TaskAccessController::can($userId, \Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_READ, $taskId)
		)
		{
			return null;
		}

		(new ResultManager($userId))->deleteByComment($commentId, $userId);
		return null;
	}

	/**
	 * @param $taskId
	 * @param null $mode
	 * @return false|string|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getResultsAction($taskId, $mode = null)
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

		$this->arParams['USER_ID'] = \Bitrix\Tasks\Util\User::getId();
		$this->arParams['TASK_ID'] = $taskId;

		$access = \Bitrix\Tasks\Access\TaskAccessController::can($this->arParams['USER_ID'], \Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_READ, $taskId);
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
		catch (\Bitrix\Main\SystemException $exception)
		{
			return null;
		}
	}

	public function disableTutorialAction()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		$userId = \Bitrix\Tasks\Util\User::getId();
		(new \Bitrix\Tasks\Internals\Marketing\OneOff\ResultTutorial($userId))->disable();
	}

	/**
	 * @return array|\Bitrix\Main\Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * @param string $code
	 * @return \Bitrix\Main\Error|null
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * @throws \Bitrix\Main\SystemException
	 */
	private function init(): void
	{
		$this->arParams['USER_ID'] = \Bitrix\Tasks\Util\User::getId();

		$this->arParams['TASK_ID'] = (int)$this->arParams['TASK_ID'];
		if (!$this->arParams['TASK_ID'])
		{
			throw new \Bitrix\Main\SystemException(Loc::getMessage('TASKS_RESULTS_SYSTEM_ERROR'));
		}

		$this->arParams['RESPONSIBLE'] = $this->arParams['RESPONSIBLE'] ? (int) $this->arParams['RESPONSIBLE'] : 0;
		$this->arParams['ACCOMPLICES'] = isset($this->arParams['ACCOMPLICES']) ? $this->arParams['ACCOMPLICES'] : [];
	}

	/**
	 *
	 */
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

	/**
	 * @param int $taskId
	 */
	private function loadTask(int $taskId)
	{
		$this->arResult['TASK_DATA'] = \Bitrix\Tasks\Internals\Registry\TaskRegistry::getInstance()->get($taskId);
	}

	/**
	 *
	 */
	private function loadUfInfo()
	{
		global $USER_FIELD_MANAGER;
		$this->arResult['UF'] = $USER_FIELD_MANAGER->getUserFields(\Bitrix\Tasks\Internals\Task\Result\ResultTable::getUfId());
	}

	/**
	 * @param array $userIds
	 */
	private function loadUsers(array $userIds): void
	{
		$this->arResult['USERS'] = [];
		if (empty($userIds))
		{
			return;
		}

		$this->arResult['USERS'] = \Bitrix\Tasks\Util\User::getData(array_unique($userIds));

		foreach ($this->arResult['USERS'] as $userId => $user)
		{
			$fileFields = \CFile::resizeImageGet(
				$user['PERSONAL_PHOTO'],
				[ 'width' => self::AVATAR_SIZE, 'height' => self::AVATAR_SIZE ],
				BX_RESIZE_IMAGE_EXACT,
				false,
				false,
				true
			);

			$this->arResult['USERS'][$userId]['AVATAR'] = ($fileFields && $fileFields['src'] ? $fileFields['src'] : '');
		}
	}
}