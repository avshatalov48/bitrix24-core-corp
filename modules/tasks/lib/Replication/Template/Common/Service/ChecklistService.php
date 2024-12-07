<?php

namespace Bitrix\Tasks\Replication\Template\Common\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\CheckList\Internals\CheckList;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListFacade;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Replication\RepositoryInterface;

class ChecklistService
{
	public function __construct(
		private RepositoryInterface $repository,
		private TaskObject $task,
		private int $userId
	)
	{
	}

	public function copyToTask(): Result
	{
		$result = new Result();
		try
		{
			$checkListItems = TemplateCheckListFacade::getByEntityId($this->repository->getEntity()->getId());
			$checkListItems = array_map(
				static function ($item) {
					$item['COPIED_ID'] = $item['ID'];
					unset($item['ID']);
					return $item;
				},
				$checkListItems
			);

			$checkListRoots = TaskCheckListFacade::getObjectStructuredRoots($checkListItems, $this->task->getId(),
				$this->userId);
			foreach ($checkListRoots as $root)
			{
				/** @var CheckList $root */
				$checkListSaveResult = $root->save();
				if (!$checkListSaveResult->isSuccess())
				{
					foreach ($checkListSaveResult->getErrors() as $error)
					{
						$result->addError(new Error($error->getMessage()));
					}
					return $result;
				}
			}
		}
		catch (SystemException $exception)
		{
			$result->addError(new Error($exception->getMessage()));
			return $result;
		}

		return $result;
	}
}