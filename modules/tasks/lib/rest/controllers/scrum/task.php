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
use Bitrix\Tasks\Scrum\Internal\ItemTable;
use Bitrix\Tasks\Scrum\Service\EntityService;
use Bitrix\Tasks\Scrum\Service\ItemService;

class Task extends Base
{
	use UserTrait;

	private $itemService;

	/**
	 * @return \string[][][]
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
	 * @param $id
	 * @param $fields
	 * @return bool|null
	 */
	public function updateAction($id, $fields)
	{
		$id = (int) $id;
		if (!$id)
		{
			$this->errorCollection->add([new Error('Item not found')]);
			return null;
		}

		if(array_key_exists('entityId', $fields))
		{
			$newEntity = (new EntityService())->getEntityById((int)$fields['entityId']);
			if (!$newEntity->getId() || !$this->checkAccess($newEntity->getGroupId()))
			{
				$this->errorCollection->add([new Error('Access denied')]);
				return null;
			}
		}

		$scrumTask = $this->getItemService()->getItemBySourceId($id);
		if (!$scrumTask)
		{
			$scrumTask = $this->createScrumTask($id, $fields);
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

		if (!$scrumTask)
		{
			$this->errorCollection->add([new Error('Item not found')]);
			return null;
		}

		if(array_key_exists('entityId', $fields))
		{
			$scrumTask->setEntityId((int) $fields['entityId']);
		}

		if (array_key_exists('storyPoints', $fields) && is_string($fields['storyPoints']))
		{
			$scrumTask->setStoryPoints($fields['storyPoints']);
		}

		if (array_key_exists('epicId', $fields) && is_numeric($fields['epicId']))
		{
			$scrumTask->setEpicId((int) $fields['epicId']);
		}

		if (array_key_exists('sort', $fields) && is_numeric($fields['sort']))
		{
			$scrumTask->setSort((int)$fields['sort']);
		}

		if (array_key_exists('createdBy', $fields) && is_numeric($fields['createdBy']))
		{
			$scrumTask->setCreatedBy((int)$fields['createdBy']);
		}

		if (array_key_exists('modifiedBy', $fields) && is_numeric($fields['modifiedBy']))
		{
			$scrumTask->setModifiedBy((int)$fields['modifiedBy']);
		}

		return $this->getItemService()->changeItem($scrumTask);
	}

	/**
	 * @param int $id
	 * @param array $fields
	 * @return ItemTable|null
	 */
	private function createScrumTask(int $id, array $fields): ?ItemTable
	{
		$entityId = array_key_exists('entityId', $fields) ? (int)$fields['entityId'] : 0;
		if (!$entityId)
		{
			return null;
		}

		$entity = (new EntityService($this->getUserId()))->getEntityById($entityId);
		if (!$entity->getId())
		{
			$this->errorCollection->add([new Error('Entity not found.')]);

			return null;
		}
		if (!$this->checkAccess($entity->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
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

			return null;
		}

		if (
			!$this->checkAccess((int)$task['GROUP_ID'])
			|| (int)$task['GROUP_ID'] !== (int)$entity->getGroupId()
		)
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		$scrumItem = ItemTable::createItemObject();
		$createdBy = $fields['createdBy'] ? (int)$fields['createdBy'] : $this->getUserId();
		$scrumItem->setCreatedBy($createdBy);
		$scrumItem->setEntityId($entityId);
		$scrumItem->setSourceId((int)$task['ID']);
		$scrumItem->setSort(1);

		$epicId = $fields['epicId'] ? (int)$fields['epicId'] : 0;
		if ($epicId)
		{
			$scrumItem->setEpicId($epicId);
		}

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