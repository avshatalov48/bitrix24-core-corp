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
use Bitrix\Tasks\Scrum\Service\TaskService;
use Bitrix\Tasks\Util\User;

class Epic extends Base
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
	 * Returns epic.
	 *
	 * @param int $id Epic id.
	 * @return array|null
	 */
	public function getAction(int $id)
	{
		global $USER_FIELD_MANAGER;

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

		$userFields = $epicService->getFilesUserField($USER_FIELD_MANAGER, $epic->getId());
		if ($epicService->getErrors())
		{
			$this->errorCollection->add($epicService->getErrors());

			return null;
		}

		$result = $epic->toArray();
		$result['files'] = $userFields['UF_SCRUM_EPIC_FILES'];

		return $result;
	}

	/**
	 * Adds epic.
	 *
	 * @param array $fields Epic fields.
	 * @return array|null
	 */
	public function addAction(array $fields)
	{
		$epic = new EpicForm();

		$epicService = new EpicService($this->getUserId());

		$epic->setGroupId($fields['groupId']);
		$epic->setName($fields['name']);

		if (array_key_exists('description', $fields) && is_string($fields['description']))
		{
			$userFields = $epicService->getFilesUserField($this->getUserFieldManager(), $epic->getId());
			$epic->setDescription($this->sanitizeText($fields['description'], $userFields));
		}

		$createdBy = null;
		if (array_key_exists('createdBy', $fields) && is_numeric($fields['createdBy']))
		{
			$createdBy = (int) $fields['createdBy'];
			if (!$this->existsUser($createdBy))
			{
				$this->errorCollection->add([new Error('createdBy user not found')]);

				return null;
			}
		}
		$epic->setCreatedBy($createdBy ?? $this->getUserId());

		$epic->setColor($fields['color']);

		$files = (is_array($fields['files']) ? $fields['files'] : []);

		if (!$epic->getGroupId())
		{
			$this->errorCollection->add([new Error('Group id not found')]);

			return null;
		}

		if (!$epic->getName())
		{
			$this->errorCollection->add([new Error('Name not found')]);

			return null;
		}

		if (!$this->checkAccess($epic->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		$epic = $epicService->createEpic($epic);
		if (!empty($epicService->getErrors()))
		{
			$this->errorCollection->add([new Error('Epic not created')]);
			return null;
		}

		if (!empty($files))
		{
			$epicService->attachFiles($this->getUserFieldManager(), $epic->getId(), $files);
			if (!empty($epicService->getErrors()))
			{
				$this->errorCollection->add([new Error('Epic files not attached')]);
				return null;
			}
		}

		return $epic->toArray();
	}

	/**
	 * Updates epic.
	 *
	 * @param int $id Epic id.
	 * @param array $fields Epic fields.
	 * @return array|null
	 */
	public function updateAction(int $id, array $fields)
	{
		$id = (int) $id;
		if (!$id)
		{
			$this->errorCollection->add([new Error('Epic not found')]);

			return null;
		}

		$epicService = new EpicService($this->getUserId());

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

		if (array_key_exists('groupId', $fields) && is_numeric($fields['groupId']))
		{
			if (!$this->checkAccess($fields['groupId']))
			{
				$this->errorCollection->add([new Error('Access denied')]);

				return null;
			}

			$inputEpic->setGroupId($fields['groupId']);
		}

		if (array_key_exists('name', $fields) && is_string($fields['name']))
		{
			$inputEpic->setName($fields['name']);
		}

		if (array_key_exists('description', $fields) && is_string($fields['description']))
		{
			$userFields = $epicService->getFilesUserField($this->getUserFieldManager(), $epic->getId());

			$inputEpic->setDescription($this->sanitizeText($fields['description'], $userFields));
		}

		if (array_key_exists('createdBy', $fields) && is_numeric($fields['createdBy']))
		{
			$createdBy = (int) $fields['createdBy'];
			if (!$this->existsUser($createdBy))
			{
				$this->errorCollection->add([new Error('createdBy user not found')]);

				return null;
			}

			$inputEpic->setCreatedBy($createdBy);
		}

		$modifiedBy = null;
		if (array_key_exists('modifiedBy', $fields) && is_numeric($fields['modifiedBy']))
		{
			$modifiedBy = (int) $fields['modifiedBy'];
			if (!$this->existsUser($modifiedBy))
			{
				$this->errorCollection->add([new Error('modifiedBy user not found')]);

				return null;
			}
		}
		$inputEpic->setModifiedBy($modifiedBy ?? $this->getUserId());

		if (array_key_exists('color', $fields) && is_string($fields['color']))
		{
			$inputEpic->setColor($fields['color']);
		}

		$epicService->updateEpic($epic->getId(), $inputEpic);
		if (!empty($epicService->getErrors()))
		{
			$this->errorCollection->add([new Error('Epic not updated')]);

			return null;
		}

		if (array_key_exists('files', $fields))
		{
			$files = (is_array($fields['files']) ? $fields['files'] : []);

			$epicService->attachFiles($this->getUserFieldManager(), $epic->getId(), $files);
			if (!empty($epicService->getErrors()))
			{
				$this->errorCollection->add([new Error('Epic files not attached')]);

				return null;
			}
		}

		$epic = $epicService->getEpic($id);

		return $epic->toArray();
	}

	/**
	 * Removes epic.
	 *
	 * @param int $id Epic id.
	 * @return array|null
	 */
	public function deleteAction(int $id)
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

		$epicService->deleteFiles($this->getUserFieldManager(), $epic->getId());

		return [];
	}

	/**
	 * Returns list epics.
	 *
	 * @param PageNavigation $nav
	 * @param array $filter
	 * @param array $select
	 * @param array $order
	 * @return array|null
	 */
	public function listAction(
		array $filter = [],
		array $select = [],
		array $order = [],
		PageNavigation $nav = null
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

	private function sanitizeText(string $text, array $userFields = []): string
	{
		$text = (new \CBXSanitizer)->sanitizeHtml($text);

		$taskService = new TaskService($this->getUserId());

		return $taskService->convertDescription($text, $userFields);
	}
}