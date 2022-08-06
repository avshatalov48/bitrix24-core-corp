<?php
namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Result;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Scrum\Form\EntityForm;
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

	public function createBacklog(EntityForm $backlog): EntityForm
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
				$this->errorCollection->setError(
					new Error(
						implode('; ', $result->getErrorMessages()),
						self::ERROR_COULD_NOT_ADD_BACKLOG
					)
				);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_ADD_BACKLOG)
			);
		}

		return $backlog;
	}

	public function changeBacklog(EntityForm $backlog): bool
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
	 * @return EntityForm
	 */
	public function getBacklogByGroupId(int $groupId): EntityForm
	{
		$backlog = new EntityForm();

		try
		{
			$queryObject = EntityTable::getList([
				'filter' => [
					'GROUP_ID' => $groupId,
					'=ENTITY_TYPE' => EntityForm::BACKLOG_TYPE
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
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_READ_BACKLOG
				)
			);
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
			new Error(implode('; ', $result->getErrorMessages()), $code)
		);
	}
}