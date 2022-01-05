<?php

namespace Bitrix\Tasks\Scrum\Controllers;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
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
	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->errorCollection = new ErrorCollection;
	}

	public function createTypeAction()
	{
		try
		{
			if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
			{
				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$userId = User::getId();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

			if (!$this->canReadGroupTasks($userId, $groupId))
			{
				return null;
			}

			$name = (is_string($post['name']) ? $post['name'] : '');
			$sort = (is_numeric($post['sort']) ? (int) $post['sort'] : 0);

			$typeService = new TypeService();
			$backlogService = new BacklogService();

			$backlog = $backlogService->getBacklogByGroupId($groupId);

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
		catch (\Exception $exception)
		{
			return null;
		}
	}

	public function changeTypeNameAction()
	{
		try
		{
			if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
			{
				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$userId = User::getId();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

			if (!$this->canReadGroupTasks($userId, $groupId))
			{
				return null;
			}

			$id = (is_numeric($post['id']) ? (int) $post['id'] : 0);
			$name = (is_string($post['name']) ? $post['name'] : '');

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
		catch (\Exception $exception)
		{
			return null;
		}
	}

	public function removeTypeAction()
	{
		try
		{
			if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
			{
				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$userId = User::getId();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

			if (!$this->canReadGroupTasks($userId, $groupId))
			{
				return null;
			}

			$id = (is_numeric($post['id']) ? (int) $post['id'] : 0);

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

			$itemIds = $itemService->getItemIdsByTypeId($type->getId());

			foreach ($itemIds as $itemId)
			{
				$definitionOfDoneService->removeList(ItemChecklistFacade::class, $itemId);
			}

			$itemService->cleanTypeIdToItems($itemIds);

			return '';
		}
		catch (\Exception $exception)
		{
			return null;
		}
	}

	private function canReadGroupTasks(int $userId, int $groupId): bool
	{
		return Group::canReadGroupTasks($userId, $groupId);
	}
}