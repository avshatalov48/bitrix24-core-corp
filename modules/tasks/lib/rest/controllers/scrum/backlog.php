<?php

namespace Bitrix\Tasks\Rest\Controllers\Scrum;

use Bitrix\Main\Error;
use Bitrix\Tasks\Rest\Controllers\Base;
use Bitrix\Tasks\Scrum\Form\EntityForm;
use Bitrix\Tasks\Scrum\Service\BacklogService;
use Bitrix\Tasks\Scrum\Service\EntityService;
use Bitrix\Tasks\Scrum\Service\ItemService;

class Backlog extends Base
{
	use UserTrait;

	/**
	 * Returns available fields.
	 *
	 * @return array
	 */
	public function getFieldsAction(): array
	{
		return [
			'fields' => [
				'groupId' => [
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
	 * Returns backlog.
	 *
	 * @param int $id Group id.
	 * @return null
	 */
	public function getAction(int $id)
	{
		$id = (int) $id;
		if (!$id)
		{
			$this->errorCollection->add([new Error('Group id not found')]);

			return null;
		}

		$backlogService = new BacklogService();

		$backlog = $backlogService->getBacklogByGroupId($id);

		if ($backlog->isEmpty())
		{
			$this->errorCollection->add([new Error('Backlog not found')]);

			return null;
		}

		if (!$this->checkAccess($backlog->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		return [
			'id' => $backlog->getId(),
			'groupId' => $backlog->getGroupId(),
			'createdBy' => $backlog->getCreatedBy(),
			'modifiedBy' => $backlog->getModifiedBy(),
		];
	}

	/**
	 * Adds backlog.
	 *
	 * @param array $fields Backlog fields.
	 * @return array|null
	 */
	public function addAction(array $fields)
	{
		$groupId = array_key_exists('groupId', $fields) ? (int) $fields['groupId'] : 0;

		if (!$groupId)
		{
			$this->errorCollection->add([new Error('Group id not found')]);

			return null;
		}

		if (!$this->checkAccess($groupId))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		$backlogService = new BacklogService();

		$backlog = $backlogService->getBacklogByGroupId($groupId);
		if (!$backlog->isEmpty())
		{
			$this->errorCollection->add([new Error('Backlog already added')]);

			return null;
		}

		$createdBy = 0;
		if (array_key_exists('createdBy', $fields) && is_numeric($fields['createdBy']))
		{
			$createdBy = (int) $fields['createdBy'];
			if (!$this->existsUser($createdBy))
			{
				$this->errorCollection->add([new Error('createdBy user not found')]);

				return null;
			}
		}
		$createdBy = $createdBy ?? $this->getUserId();

		$modifiedBy = 0;
		if (array_key_exists('modifiedBy', $fields) && is_numeric($fields['modifiedBy']))
		{
			$modifiedBy = (int) $fields['modifiedBy'];
			if (!$this->existsUser($modifiedBy))
			{
				$this->errorCollection->add([new Error('modifiedBy user not found')]);

				return null;
			}
		}
		$modifiedBy = $modifiedBy ?? $this->getUserId();

		$backlog = new EntityForm();

		$backlog->setGroupId($groupId);
		$backlog->setCreatedBy($createdBy);
		$backlog->setModifiedBy($modifiedBy);

		$backlog = $backlogService->createBacklog($backlog);

		if (!empty($backlogService->getErrors()))
		{
			$this->errorCollection->add([new Error('Unable to add backlog')]);

			return null;
		}

		return [
			'id' => $backlog->getId(),
			'groupId' => $backlog->getGroupId(),
			'createdBy' => $backlog->getCreatedBy(),
			'modifiedBy' => $backlog->getModifiedBy(),
		];
	}

	/**
	 * Updates backlog.
	 *
	 * @param int $id Backlog id.
	 * @param array $fields Backlog fields.
	 * @return array|null
	 */
	public function updateAction(int $id, array $fields)
	{
		$id = (int) $id;
		if (!$id)
		{
			$this->errorCollection->add([new Error('Backlog id not found')]);

			return null;
		}

		$entityService = new EntityService();
		$backlogService = new BacklogService();

		$backlog = $entityService->getEntityById($id);

		if ($backlog->isEmpty())
		{
			$this->errorCollection->add([new Error('Backlog not found')]);

			return null;
		}

		if ($backlog->getEntityType() !== EntityForm::BACKLOG_TYPE)
		{
			$this->errorCollection->add([new Error('Backlog not found')]);

			return null;
		}

		if (!$this->checkAccess($backlog->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		if (array_key_exists('groupId', $fields) && is_numeric($fields['groupId']))
		{
			$groupId = (int) $fields['groupId'];
			$itemService = new ItemService();

			$isGroupUpdatingAction = ($backlog->getGroupId() !== $groupId);
			$hasBacklogItems = (!empty($itemService->getItemIdsByEntityId($backlog->getId())));

			if ($isGroupUpdatingAction)
			{
				if ($hasBacklogItems)
				{
					$this->errorCollection->add([new Error('It is forbidden move a backlog with items')]);

					return null;
				}

				$targetBacklog = $backlogService->getBacklogByGroupId($groupId);
				if (!$targetBacklog->isEmpty())
				{
					$this->errorCollection->add([new Error('The target group already has a backlog')]);

					return null;
				}
			}

			$backlog->setGroupId($groupId);
		}

		if (array_key_exists('createdBy', $fields) && is_numeric($fields['createdBy']))
		{
			$createdBy = (int) $fields['createdBy'];
			if (!$this->existsUser($createdBy))
			{
				$this->errorCollection->add([new Error('createdBy user not found')]);

				return null;
			}

			$backlog->setCreatedBy($createdBy);
		}

		$modifiedBy = 0;
		if (array_key_exists('modifiedBy', $fields) && is_numeric($fields['modifiedBy']))
		{
			$modifiedBy = (int) $fields['modifiedBy'];
			if (!$this->existsUser($modifiedBy))
			{
				$this->errorCollection->add([new Error('modifiedBy user not found')]);

				return null;
			}
		}
		$backlog->setModifiedBy($modifiedBy ?? $this->getUserId());

		$backlogService->changeBacklog($backlog);

		if (!empty($backlogService->getErrors()))
		{
			$this->errorCollection->add([new Error('Unable to update backlog')]);

			return null;
		}

		return [
			'id' => $backlog->getId(),
			'groupId' => $backlog->getGroupId(),
			'createdBy' => $backlog->getCreatedBy(),
			'modifiedBy' => $backlog->getModifiedBy(),
		];
	}

	/**
	 * Removes backlog.
	 *
	 * @param int $id Backlog id.
	 * @return array|null
	 */
	public function deleteAction(int $id)
	{
		$id = (int) $id;
		if (!$id)
		{
			$this->errorCollection->add([new Error('Backlog id not found')]);

			return null;
		}

		$entityService = new EntityService();

		$backlog = $entityService->getEntityById($id);

		if ($backlog->isEmpty())
		{
			$this->errorCollection->add([new Error('Backlog not found')]);

			return null;
		}

		if ($backlog->getEntityType() !== EntityForm::BACKLOG_TYPE)
		{
			$this->errorCollection->add([new Error('Backlog not found')]);

			return null;
		}

		if (!$this->checkAccess($backlog->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		$itemService = new ItemService();

		$hasBacklogItems = (!empty($itemService->getItemIdsByEntityId($backlog->getId())));

		if ($hasBacklogItems)
		{
			$this->errorCollection->add([new Error('It is forbidden remove a backlog with items')]);

			return null;
		}

		if (!$entityService->removeEntity($id))
		{
			$this->errorCollection->add([new Error('Backlog not deleted')]);
		}

		return [];
	}
}