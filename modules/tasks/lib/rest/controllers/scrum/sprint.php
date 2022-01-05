<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Rest\Controllers\Scrum;

use Bitrix\Main\Error;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Rest\Controllers\Base;
use Bitrix\Tasks\Scrum\Internal\EntityTable;
use Bitrix\Tasks\Scrum\Internal\ItemTable;
use Bitrix\Tasks\Scrum\Service\EntityService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\SprintService;
use Bitrix\Main\Type\DateTime;

class Sprint extends Base
{
	use UserTrait;

	/**
	 * @return \string[][][]
	 */
	public function getFieldsAction(): array
	{
		return [
			'fields' => [
				'groupId' => [
					'type' => 'integer',
				],
				'name' => [
					'type' => 'string',
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
				'dateStart' => [
					'type' => 'integer',
				],
				'dateEnd' => [
					'type' => 'integer',
				],
				'status' => [
					'type' => 'string',
				],
			],
		];
	}

	/**
	 * @param $id
	 * @return array|null
	 */
	public function getAction($id)
	{
		$id = (int) $id;
		if (!$id)
		{
			$this->errorCollection->add([new Error('Item not found')]);

			return null;
		}

		$sprint = (new SprintService())->getSprintById($id);

		if (empty($sprint))
		{
			$this->errorCollection->add([new Error('Item not found')]);

			return null;
		}

		if (!$sprint->getId() || !$this->checkAccess($sprint->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		return $sprint->toArray();
	}

	/**
	 * @param $fields
	 * @return array|null
	 */
	public function addAction($fields)
	{
		$groupId = array_key_exists('groupId', $fields) ? (int)$fields['groupId'] : 0;
		if (!$groupId)
		{
			$this->errorCollection->add([new Error('Item not created')]);

			return null;
		}

		if (!$this->checkAccess($groupId))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		$tmpId = array_key_exists('tmpId', $fields) ? (int)$fields['tmpId'] : 0;
		$name = array_key_exists('name', $fields) ? (string)$fields['name'] : '';
		$sort = array_key_exists('sort', $fields) ? (int)$fields['sort'] : 0;
		$createdBy = array_key_exists('createdBy', $fields) ? (int)$fields['createdBy'] : 0;
		$modifiedBy = array_key_exists('modifiedBy', $fields) ? (int)$fields['modifiedBy'] : 0;
		$dateStart = array_key_exists('dateStart', $fields) ? (int)$fields['dateStart'] : 0;
		$dateEnd = array_key_exists('dateEnd', $fields) ? (int)$fields['dateEnd'] : 0;
		$status = array_key_exists('status', $fields) ? (string)$fields['status'] : EntityTable::SPRINT_COMPLETED;

		$sprint = EntityTable::createEntityObject();
		$sprint->setGroupId($groupId);
		$sprint->setTmpId($tmpId);
		$sprint->setName($name);
		$sprint->setSort($sort);
		$sprint->setCreatedBy($createdBy);
		$sprint->setModifiedBy($modifiedBy);
		$sprint->setDateStart(DateTime::createFromTimestamp($dateStart));
		$sprint->setDateEnd(DateTime::createFromTimestamp($dateEnd));
		$sprint->setStatus($status);

		$sprintService = new SprintService();

		$sprint = $sprintService->createSprint($sprint);
		return $sprint->toArray();
	}

	/**
	 * @param $id
	 * @param $fields
	 * @return array|null
	 */
	public function updateAction($id, $fields)
	{
		$id = (int) $id;
		if (!$id)
		{
			$this->errorCollection->add([new Error('Item not found')]);
			return null;
		}

		$entityService = new EntityService();
		$sprint = $entityService->getEntityById($id);

		if (!$sprint->getId())
		{
			$this->errorCollection->add([new Error('Item not found')]);

			return null;
		}

		if ($sprint->getEntityType() !== EntityTable::SPRINT_TYPE)
		{
			$this->errorCollection->add([new Error('Item not found')]);

			return null;
		}

		if (!$this->checkAccess($sprint->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);
			return null;
		}

		if (array_key_exists('groupId', $fields) && is_numeric($fields['groupId']))
		{
			$sprint->setGroupId((int)$fields['groupId']);
		}

		if (array_key_exists('name', $fields) && is_string($fields['name']))
		{
			$sprint->setName($fields['name']);
		}

		if (array_key_exists('sort', $fields) && is_numeric($fields['sort']))
		{
			$sprint->setSort((int)$fields['sort']);
		}

		if (array_key_exists('createdBy', $fields) && is_numeric($fields['createdBy']))
		{
			$sprint->setCreatedBy((int)$fields['createdBy']);
		}

		if (array_key_exists('modifiedBy', $fields) && is_numeric($fields['modifiedBy']))
		{
			$sprint->setModifiedBy((int)$fields['modifiedBy']);
		}

		if (array_key_exists('dateStart', $fields) && is_numeric($fields['dateStart']))
		{
			$sprint->setDateStart(DateTime::createFromTimestamp((int)$fields['dateStart']));
		}

		if (array_key_exists('dateEnd', $fields) && is_numeric($fields['dateEnd']))
		{
			$sprint->setDateEnd(DateTime::createFromTimestamp((int)$fields['dateEnd']));
		}

		if (array_key_exists('status', $fields) && is_string($fields['status']))
		{
			$sprint->setStatus($fields['status']);
		}

		$sprintService = new SprintService();

		if (!$sprintService->changeSprint($sprint))
		{
			$this->errorCollection->add([new Error('Item not updated')]);
			return null;
		}

		return $sprint->toArray();
	}

	/**
	 * @param $id
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function deleteAction($id)
	{
		$id = (int) $id;
		if (!$id)
		{
			$this->errorCollection->add([new Error('Item not found')]);
			return null;
		}

		$entityService = new EntityService();
		$sprint = $entityService->getEntityById($id);

		if (!$sprint->getId())
		{
			$this->errorCollection->add([new Error('Item not found')]);

			return null;
		}
		if ($sprint->getEntityType() !== EntityTable::SPRINT_TYPE)
		{
			$this->errorCollection->add([new Error('Item not found')]);

			return null;
		}

		if (!$this->checkAccess($sprint->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		$sprintService = new SprintService();
		if (!$sprintService->removeSprint($sprint))
		{
			$this->errorCollection->add([new Error('Item not deleted')]);
		}

		return [];
	}

	/**
	 * @param $id
	 * @return array|null
	 */
	public function startAction($id)
	{
		$id = (int) $id;
		if (!$id)
		{
			$this->errorCollection->add([new Error('Item not found')]);

			return null;
		}

		$entityService = new EntityService();
		$sprint = $entityService->getEntityById($id);

		if (!$sprint->getId())
		{
			$this->errorCollection->add([new Error('Item not found')]);

			return null;
		}
		if ($sprint->getEntityType() !== EntityTable::SPRINT_TYPE)
		{
			$this->errorCollection->add([new Error('Item not found')]);

			return null;
		}

		if (!$this->checkAccess($sprint->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		$sprintService = new SprintService();
		$sprint = $sprintService->startSprint($sprint);

		return $sprint->toArray();
	}

	/**
	 * @param $id
	 * @return array|null
	 */
	public function finishAction($id)
	{
		$id = (int) $id;
		if (!$id)
		{
			$this->errorCollection->add([new Error('Item not found')]);
			return null;
		}

		$entityService = new EntityService();
		$sprint = $entityService->getEntityById($id);

		if (!$sprint->getId())
		{
			$this->errorCollection->add([new Error('Item not found')]);

			return null;
		}
		if ($sprint->getEntityType() !== EntityTable::SPRINT_TYPE)
		{
			$this->errorCollection->add([new Error('Item not found')]);

			return null;
		}

		if (!$this->checkAccess($sprint->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		$sprintService = new SprintService();
		$sprint = $sprintService->completeSprint($sprint);

		return $sprint->toArray();
	}

	/**
	 * @param PageNavigation $nav
	 * @param array $filter
	 * @param array $select
	 * @param array $order
	 * @return array
	 */
	public function listAction(
		PageNavigation $nav,
		$filter = [],
		$select = [],
		$order = []
	)
	{
		$filter['=ENTITY_TYPE'] = EntityTable::SPRINT_TYPE;

		$queryResult = (new EntityService($this->getUserId()))->getList($nav, $filter, $select, $order);

		if (!$queryResult)
		{
			$this->errorCollection->add([new Error('Couldn\'t load list.')]);
			return [];
		}

		$sprints = [];

		$n = 0;
		while ($data = $queryResult->fetch())
		{
			$n++;
			if ($nav && $n > $nav->getPageSize())
			{
				break;
			}

			$sprints[] = EntityTable::createEntityObject($data)->toArray();
		}

		if ($nav)
		{
			$nav->setRecordCount($nav->getOffset() + $n);
		}

		return $sprints;
	}
}