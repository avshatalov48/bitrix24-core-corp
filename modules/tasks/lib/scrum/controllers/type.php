<?php

namespace Bitrix\Tasks\Scrum\Controllers;

use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Scrum\Checklist\ItemChecklistFacade;
use Bitrix\Tasks\Scrum\Checklist\TypeChecklistFacade;
use Bitrix\Tasks\Scrum\Service\BacklogService;
use Bitrix\Tasks\Scrum\Service\DefinitionOfDoneService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\TypeService;
use Bitrix\Tasks\Util\User;

class Type extends Controller
{
	const ERROR_COULD_NOT_LOAD_MODULE = 'TASKS_STC_01';
	const ERROR_ACCESS_DENIED = 'TASKS_STC_02';
	const ERROR_COULD_NOT_CREATE_TYPE = 'TASKS_STC_03';

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->errorCollection = new ErrorCollection;
	}

	protected function processBeforeAction(Action $action)
	{
		if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_STC_ERROR_INCLUDE_MODULE_ERROR'),
					self::ERROR_COULD_NOT_LOAD_MODULE
				)
			);

			return false;
		}

		$post = $this->request->getPostList()->toArray();

		$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);
		$userId = User::getId();

		if (!Group::canReadGroupTasks($userId, $groupId))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_STC_ERROR_ACCESS_DENIED'),
					self::ERROR_ACCESS_DENIED
				)
			);

			return false;
		}

		return parent::processBeforeAction($action);
	}

	public function createTypeAction(int $groupId, string $name, int $sort)
	{
		$typeService = new TypeService();
		$backlogService = new BacklogService();

		$backlog = $backlogService->getBacklogByGroupId($groupId);

		if ($backlog->isEmpty())
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_STC_ERROR_COULD_NOT_CREATE_TYPE'),
					self::ERROR_COULD_NOT_CREATE_TYPE
				)
			);

			return null;
		}
		if ($backlogService->getErrors())
		{
			$this->errorCollection->add($backlogService->getErrors());

			return null;
		}

		$type = $typeService->getTypeObject();
		$type->setEntityId($backlog->getId());
		$type->setName($name);
		$type->setSort($sort);

		$createdType = $typeService->createType($type);

		if ($typeService->getErrors())
		{
			$this->errorCollection->add($typeService->getErrors());

			return null;
		}

		return $typeService->getTypeData($createdType);
	}

	public function changeTypeNameAction(int $id, string $name)
	{
		$typeService = new TypeService();

		$type = $typeService->getTypeObject();
		$type->setId($id);
		$type->setName($name);

		$typeService->changeType($type);

		if ($typeService->getErrors())
		{
			$this->errorCollection->add($typeService->getErrors());

			return null;
		}

		return '';
	}

	public function removeTypeAction(int $id)
	{
		$userId = User::getId();

		$typeService = new TypeService();
		$definitionOfDoneService = new DefinitionOfDoneService($userId);
		$itemService = new ItemService();

		$type = $typeService->getTypeObject();
		$type->setId($id);

		$typeService->removeType($type);

		if ($typeService->getErrors())
		{
			$this->errorCollection->add($typeService->getErrors());

			return null;
		}

		$definitionOfDoneService->removeList(TypeChecklistFacade::class, $type->getId());
		if ($definitionOfDoneService->getErrors())
		{
			$this->errorCollection->add($definitionOfDoneService->getErrors());

			return null;
		}

		$itemIds = $itemService->getItemIdsByTypeId($type->getId());

		foreach ($itemIds as $itemId)
		{
			$definitionOfDoneService->removeList(ItemChecklistFacade::class, $itemId);
		}

		if ($definitionOfDoneService->getErrors())
		{
			$this->errorCollection->add($definitionOfDoneService->getErrors());

			return null;
		}

		$itemService->cleanTypeIdToItems($itemIds);

		return '';
	}
}