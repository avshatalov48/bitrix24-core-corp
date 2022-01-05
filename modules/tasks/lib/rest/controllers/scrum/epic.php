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
use Bitrix\Tasks\Scrum\Form\EpicForm;
use Bitrix\Tasks\Scrum\Service\EpicService;
use Bitrix\Tasks\Util\User;

class Epic extends Base
{
	use UserTrait;

	/**
	 * @return string[][][]
	 */
	public function getFieldsAction(): array
	{
		return [
			'fields' => [
				'name' => [
					'type' => 'string',
				],
				'description' => [
					'type' => 'string',
				],
				'groupId' => [
					'type' => 'integer',
				],
				'color' => [
					'type' => 'string',
				],
				'files' => [
					'type' => 'array',
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
	 * @return array|null
	 */
	public function getAction($id)
	{
		$id = (int) $id;
		if (!$id)
		{
			$this->errorCollection->add([new Error('Epic not found')]);

			return null;
		}

		$epicService = new EpicService();

		$epic = $epicService->getEpic($id);
		if (!$epic->getId())
		{
			$this->errorCollection->add([new Error('Epic not found')]);

			return null;
		}
		if (!$this->checkAccess($epic->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		return $epic->toArray();
	}

	/**
	 * @param $fields
	 * @return array|null
	 */
	public function addAction($fields)
	{
		$epic = new EpicForm();

		$epic->setGroupId($fields['groupId']);
		$epic->setName($fields['name']);
		$epic->setDescription($fields['description']);
		$epic->setCreatedBy($fields['createdBy'] ?? User::getId());
		$epic->setColor($fields['color']);

		$files = (is_array($fields['files']) ? $fields['files'] : []);

		if (!$epic->getGroupId())
		{
			$this->errorCollection->add([new Error('Bad request')]);

			return null;
		}

		if (!$this->checkAccess($epic->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		$epicService = new EpicService(User::getId());

		$epic = $epicService->createEpic($epic);
		if ($epicService->getErrors())
		{
			$this->errorCollection->add([new Error('Epic not created')]);

			return null;
		}

		$epicService->attachFiles($this->getUserFieldManager(), $epic->getId(), $files);
		if ($epicService->getErrors())
		{
			$this->errorCollection->add([new Error('Epic files not attached')]);

			return null;
		}

		return $epic->toArray();
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
			$this->errorCollection->add([new Error('Epic not found')]);

			return null;
		}

		$epicService = new EpicService(User::getId());

		$epic = $epicService->getEpic($id);
		if (!$epic->getId())
		{
			$this->errorCollection->add([new Error('Epic not found')]);

			return null;
		}
		if (!$this->checkAccess($epic->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		$inputEpic = new EpicForm();

		$inputEpic->setGroupId($fields['groupId']);
		$inputEpic->setName($fields['name']);
		$inputEpic->setDescription($fields['description']);
		$inputEpic->setCreatedBy($fields['createdBy'] ?? User::getId());
		$inputEpic->setModifiedBy($fields['modifiedBy'] ?? User::getId());
		$inputEpic->setColor($fields['color']);

		$files = (is_array($fields['files']) ? $fields['files'] : []);

		$epicService->updateEpic($epic->getId(), $inputEpic);
		if ($epicService->getErrors())
		{
			$this->errorCollection->add([new Error('Epic not updated')]);

			return null;
		}

		$epicService->attachFiles($this->getUserFieldManager(), $epic->getId(), $files);
		if ($epicService->getErrors())
		{
			$this->errorCollection->add([new Error('Epic files not attached')]);

			return null;
		}

		return $epic->toArray();
	}

	/**
	 * @param $id
	 * @return array|null
	 */
	public function deleteAction($id)
	{
		$id = (int) $id;
		if (!$id)
		{
			$this->errorCollection->add([new Error('Epic not found')]);

			return null;
		}

		$epicService = new EpicService();

		$epic = $epicService->getEpic($id);
		if (!$epic->getId())
		{
			$this->errorCollection->add([new Error('Epic not found')]);

			return null;
		}
		if (!$this->checkAccess($epic->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		if (!$epicService->removeEpic($epic))
		{
			$this->errorCollection->add([new Error('Epic not deleted')]);
		}

		return [];
	}

	/**
	 * @param PageNavigation $nav
	 * @param array $filter
	 * @param array $select
	 * @param array $order
	 * @return array|null
	 */
	public function listAction(
		PageNavigation $nav,
		array $filter = [],
		array $select = [],
		array $order = []
	)
	{
		$epicService = new EpicService($this->getUserId());

		$queryResult = $epicService->getList($select, $filter, $order, $nav);

		if (!$queryResult)
		{
			$this->errorCollection->add([new Error('Could not load list')]);

			return [];
		}

		$epics = [];

		$n = 0;
		while ($data = $queryResult->fetch())
		{
			$n++;
			if ($n > $nav->getPageSize())
			{
				break;
			}

			$epic = new EpicForm();
			$epic->fillFromDatabase($data);

			$epics[] = $epic->toArray();
		}

		$nav->setRecordCount($nav->getOffset() + $n);

		return $epics;
	}

	/**
	 * @return \CUserTypeManager|\UfMan
	 */
	private function getUserFieldManager()
	{
		global $USER_FIELD_MANAGER;

		return $USER_FIELD_MANAGER;
	}
}