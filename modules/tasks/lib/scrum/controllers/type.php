<?php

namespace Bitrix\Tasks\Scrum\Controllers;

use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Scrum\Checklist\ItemChecklistFacade;
use Bitrix\Tasks\Scrum\Checklist\TypeChecklistFacade;
use Bitrix\Tasks\Scrum\Form\TypeForm;
use Bitrix\Tasks\Scrum\Service\BacklogService;
use Bitrix\Tasks\Scrum\Service\DefinitionOfDoneService;
use Bitrix\Tasks\Scrum\Service\EntityService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\TypeService;
use Bitrix\Tasks\Util\User;

class Type extends Controller
{
	const ERROR_COULD_NOT_LOAD_MODULE = 'TASKS_STC_01';
	const ERROR_ACCESS_DENIED = 'TASKS_STC_02';
	const ERROR_COULD_NOT_CREATE_TYPE = 'TASKS_STC_03';
	const ERROR_COULD_NOT_CHANGE_TYPE_NAME = 'TASKS_STC_04';

	protected function processBeforeAction(Action $action)
	{
		if (!Loader::includeModule('socialnetwork'))
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

	/**
	 * Creates a new task type.
	 *
	 * @param int $groupId Group id.
	 * @param string $name The type name.
	 * @param int $sort The type sort.
	 * @return array|null An array of type data.
	 */
	public function createTypeAction(int $groupId, string $name, int $sort): ?array
	{
		$typeService = new TypeService();
		$backlogService = new BacklogService();

		if (!mb_strlen($name))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_STC_ERROR_COULD_NOT_EMPTY_TYPE'),
					self::ERROR_COULD_NOT_CREATE_TYPE
				)
			);

			return null;
		}

		$backlog = $backlogService->getBacklogByGroupId($groupId);

		if ($backlog->isEmpty())
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_STC_ERROR_COULD_NOT_FOUND_TYPE'),
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

		$type = new TypeForm();
		$type->setEntityId($backlog->getId());
		$type->setName($name);
		$type->setSort($sort);

		$createdType = $typeService->createType($type);

		if ($typeService->getErrors())
		{
			$this->errorCollection->add($typeService->getErrors());

			return null;
		}

		return $createdType->toArray();
	}

	/**
	 * Changes a type name.
	 *
	 * @param int $id Type id.
	 * @param string $name New type name.
	 * @return string|null
	 */
	public function changeTypeNameAction(int $id, string $name): ?array
	{
		$typeService = new TypeService();
		$entityService = new EntityService();

		$userId = User::getId();

		if (!mb_strlen($name))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_STC_ERROR_COULD_NOT_EMPTY_TYPE'),
					self::ERROR_COULD_NOT_CHANGE_TYPE_NAME
				)
			);

			return null;
		}

		$type = $typeService->getType($id);
		if ($type->isEmpty())
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_STC_ERROR_COULD_NOT_FOUND_TYPE'),
					self::ERROR_COULD_NOT_CHANGE_TYPE_NAME
				)
			);
		}

		$entity = $entityService->getEntityById($type->getEntityId());
		if (!Group::canReadGroupTasks($userId, $entity->getGroupId()))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_STC_ERROR_ACCESS_DENIED'),
					self::ERROR_ACCESS_DENIED
				)
			);

			return null;
		}

		$typeForm = new TypeForm();

		$typeForm->setId($type->getId());
		$typeForm->setName($name);

		$typeService->changeType($typeForm);

		if ($typeService->getErrors())
		{
			$this->errorCollection->add($typeService->getErrors());

			return null;
		}

		return $typeService->getType($type->getId())->toArray();
	}

	/**
	 * Removes a type.
	 *
	 * @param int $id Type id.
	 * @return string|null
	 */
	public function removeTypeAction(int $id): ?string
	{
		$userId = User::getId();

		$typeService = new TypeService();
		$definitionOfDoneService = new DefinitionOfDoneService($userId);
		$itemService = new ItemService();
		$entityService = new EntityService();

		$type = $typeService->getType($id);
		if ($type->isEmpty())
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_STC_ERROR_COULD_NOT_FOUND_TYPE'),
					self::ERROR_COULD_NOT_CHANGE_TYPE_NAME
				)
			);
		}

		$entity = $entityService->getEntityById($type->getEntityId());
		if (!Group::canReadGroupTasks($userId, $entity->getGroupId()))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_STC_ERROR_ACCESS_DENIED'),
					self::ERROR_ACCESS_DENIED
				)
			);

			return null;
		}

		$type = new TypeForm();
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