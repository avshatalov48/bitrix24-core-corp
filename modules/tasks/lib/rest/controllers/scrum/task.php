<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Rest\Controllers\Scrum;

use Bitrix\Main\Error;
use Bitrix\Tasks\Rest\Controllers\Base;
use Bitrix\Tasks\Scrum\Form\ItemForm;
use Bitrix\Tasks\Scrum\Service\EntityService;
use Bitrix\Tasks\Scrum\Service\EpicService;
use Bitrix\Tasks\Scrum\Service\ItemService;

class Task extends Base
{
	use UserTrait;

	private $itemService;

	/**
	 * Returns available fields.
	 *
	 * @return array
	 */
	public function getFieldsAction(): array
	{
		return [
			'fields' => [
				'entityId' => [
					'type' => 'integer',
				],
				'storyPoints' => [
					'type' => 'string',
				],
				'epicId' => [
					'type' => 'integer',
				],
				'sort' => [
					'type' => 'integer',
				],
				'createdBy' => [
					'type' => 'integer',
				],
				'modifiedBy' => [
					'type' => 'integer',
				],
			],
		];
	}

	/**
	 * Returns item data.
	 *
	 * @param int $id Task id.
	 * @return null
	 */
	public function getAction(int $id)
	{
		$taskId = (int) $id;
		if (!$taskId)
		{
			$this->errorCollection->add([new Error('Task id not found')]);

			return null;
		}

		$scrumTask = $this->getItemService()->getItemBySourceId($taskId);

		if (!$scrumTask->getId())
		{
			$this->errorCollection->add([new Error('Task not found')]);

			return null;
		}

		$entity = (new EntityService())->getEntityById($scrumTask->getEntityId());
		if (!$entity->getId() || !$this->checkAccess($entity->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		return [
			'entityId' => $scrumTask->getEntityId(),
			'storyPoints' => $scrumTask->getStoryPoints(),
			'epicId' => $scrumTask->getEpicId(),
			'sort' => $scrumTask->getSort(),
			'createdBy' => $scrumTask->getCreatedBy(),
			'modifiedBy' => $scrumTask->getModifiedBy(),
		];
	}

	/**
	 * Updates item fields.
	 *
	 * @param int $id Task id.
	 * @param array $fields Item fields.
	 * @return bool|null
	 */
	public function updateAction(int $id, array $fields)
	{
		$taskId = (int) $id;
		if (!$taskId)
		{
			$this->errorCollection->add([new Error('Task id not found')]);

			return null;
		}

		$inputEntityId = (array_key_exists('entityId', $fields) ? (int) $fields['entityId'] : 0);

		if ($inputEntityId)
		{
			$newEntity = (new EntityService())->getEntityById($inputEntityId);
			if (!$newEntity->getId() || !$this->checkAccess($newEntity->getGroupId()))
			{
				$this->errorCollection->add([new Error('Access denied')]);

				return null;
			}
		}

		$scrumTask = $this->getItemService()->getItemBySourceId($taskId);
		if (!$scrumTask->getId())
		{
			if (!$inputEntityId)
			{
				$this->errorCollection->add([new Error('Entity id not found')]);

				return null;
			}

			$scrumTask = $this->createScrumTask($taskId, $fields);
		}
		else
		{
			$entity = (new EntityService())->getEntityById($scrumTask->getEntityId());
			if (!$entity->getId() || !$this->checkAccess($entity->getGroupId()))
			{
				$this->errorCollection->add([new Error('Access denied')]);

				return null;
			}
		}

		if (!$scrumTask->getId())
		{
			$this->errorCollection->add([new Error('Item not created')]);

			return null;
		}

		if ($inputEntityId)
		{
			$scrumTask->setEntityId($inputEntityId);
		}

		if (array_key_exists('storyPoints', $fields) && is_string($fields['storyPoints']))
		{
			$scrumTask->setStoryPoints($fields['storyPoints']);
		}

		if (array_key_exists('epicId', $fields) && is_numeric($fields['epicId']))
		{
			$epicService = new EpicService($this->getUserId());
			$epic = $epicService->getEpic((int) $fields['epicId']);
			if (!$epic->getId())
			{
				$this->errorCollection->add([new Error('Epic not found')]);

				return null;
			}

			$scrumTask->setEpicId($epic->getId());
		}

		if (array_key_exists('sort', $fields) && is_numeric($fields['sort']))
		{
			$scrumTask->setSort((int) $fields['sort']);
		}

		if (array_key_exists('createdBy', $fields) && is_numeric($fields['createdBy']))
		{
			$createdBy = (int) $fields['createdBy'];
			if (!$this->existsUser($createdBy))
			{
				$this->errorCollection->add([new Error('createdBy user not found')]);

				return null;
			}

			$scrumTask->setCreatedBy($createdBy);
		}

		if (array_key_exists('modifiedBy', $fields) && is_numeric($fields['modifiedBy']))
		{
			$modifiedBy = (int) $fields['modifiedBy'];
			if (!$this->existsUser($modifiedBy))
			{
				$this->errorCollection->add([new Error('modifiedBy user not found')]);

				return null;
			}

			$scrumTask->setModifiedBy($modifiedBy);
		}

		$result = $this->getItemService()->changeItem($scrumTask);
		if (!$result)
		{
			$this->errorCollection->add([new Error('Unable to update task')]);

			return null;
		}

		return true;
	}

	/**
	 * @param int $id
	 * @param array $fields
	 * @return ItemForm
	 */
	private function createScrumTask(int $id, array $fields): ItemForm
	{
		$scrumItem = new ItemForm();

		$entityId = array_key_exists('entityId', $fields) ? (int)$fields['entityId'] : 0;
		if (!$entityId)
		{
			return $scrumItem;
		}

		$entity = (new EntityService($this->getUserId()))->getEntityById($entityId);
		if (!$entity->getId())
		{
			$this->errorCollection->add([new Error('Entity not found.')]);

			return $scrumItem;
		}
		if (!$this->checkAccess($entity->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return $scrumItem;
		}

		$task = null;
		try
		{
			$task = (new \CTaskItem($id, $this->getUserId()))->getData();
		}
		catch (\Exception $e)
		{

		}

		if (!$task)
		{
			$this->errorCollection->add([new Error('Task not found.')]);

			return $scrumItem;
		}

		if (
			!$this->checkAccess((int) $task['GROUP_ID'])
			|| (int) $task['GROUP_ID'] !== $entity->getGroupId()
		)
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return $scrumItem;
		}

		$createdBy = 0;
		if (array_key_exists('createdBy', $fields) && is_numeric($fields['createdBy']))
		{
			$createdBy = (int) $fields['createdBy'];
			if (!$this->existsUser($createdBy))
			{
				$this->errorCollection->add([new Error('createdBy user not found')]);

				return $scrumItem;
			}
		}
		$scrumItem->setCreatedBy($createdBy ?? $this->getUserId());

		$scrumItem->setEntityId($entityId);
		$scrumItem->setSourceId((int) $task['ID']);

		$sort = 1;
		if (array_key_exists('sort', $fields) && is_numeric($fields['sort']))
		{
			$sort = (int) $fields['sort'];
		}
		$scrumItem->setSort($sort);

		return $this->getItemService()->createTaskItem($scrumItem);
	}

	/**
	 * @return ItemService
	 */
	private function getItemService(): ItemService
	{
		if (!$this->itemService)
		{
			$this->itemService = new ItemService();
		}
		return $this->itemService;
	}
}