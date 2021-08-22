<?php
namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Result;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Scrum\Internal\EntityTable;

class BacklogService implements Errorable
{
	const ERROR_COULD_NOT_ADD_BACKLOG = 'TASKS_BS_01';
	const ERROR_COULD_NOT_READ_BACKLOG = 'TASKS_BS_02';
	const ERROR_COULD_NOT_UPDATE_BACKLOG = 'TASKS_BS_03';

	private $errorCollection;

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	public function createBacklog(EntityTable $backlog): EntityTable
	{
		try
		{
			$result = EntityTable::add($backlog->getFieldsToCreateBacklog());

			if ($result->isSuccess())
			{
				$backlog->setId($result->getId());
			}
			else
			{
				$this->errorCollection->setError(new Error(
					implode('; ', $result->getErrorMessages()),
					self::ERROR_COULD_NOT_ADD_BACKLOG
				));
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_ADD_BACKLOG));
		}

		return $backlog;
	}

	public function changeBacklog(EntityTable $backlog): bool
	{
		try
		{
			$result = EntityTable::update($backlog->getId(), $backlog->getFieldsToUpdateEntity());

			if ($result->isSuccess())
			{
				return true;
			}
			else
			{
				$this->setErrors($result, self::ERROR_COULD_NOT_UPDATE_BACKLOG);

				return false;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_UPDATE_BACKLOG
				)
			);

			return false;
		}
	}

	/**
	 * Returns an object with backlog data by scrum group id.
	 *
	 * @param int $groupId Scrum group id.
	 * @param ItemService|null $itemService Item service object.
	 * @param PageNavigation|null $nav For item navigation.
	 * @param array $filteredSourceIds If you need to get filtered items.
	 * @return EntityTable
	 */
	public function getBacklogByGroupId(
		int $groupId,
		ItemService $itemService = null,
		PageNavigation $nav = null,
		array $filteredSourceIds = []
	): EntityTable
	{
		$backlog = EntityTable::createEntityObject();

		try
		{
			$queryObject = EntityTable::getList([
				'filter' => [
					'GROUP_ID' => $groupId,
					'ENTITY_TYPE' => EntityTable::BACKLOG_TYPE
				]
			]);
			if ($backlogData = $queryObject->fetch())
			{
				$backlog->setId($backlogData['ID']);
				$backlog->setGroupId($groupId);
				$backlog->setCreatedBy($backlogData['CREATED_BY']);
				$backlog->setModifiedBy($backlogData['MODIFIED_BY']);
				$backlog->setEntityType($backlogData['ENTITY_TYPE']);
				$backlog->setInfo($backlogData['INFO']);

				if ($itemService)
				{
					$backlog->setChildren($itemService->getHierarchyChildItems($backlog, $nav, $filteredSourceIds));
				}
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_BACKLOG));
		}

		return $backlog;
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	private function setErrors(Result $result, string $code): void
	{
		$this->errorCollection->setError(
			new Error(
				implode('; ', $result->getErrorMessages()),
				$code
			)
		);
	}
}