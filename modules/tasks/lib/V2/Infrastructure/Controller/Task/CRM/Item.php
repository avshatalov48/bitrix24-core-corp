<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Task\CRM;

use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\CloseSession;
use Bitrix\Tasks\V2\Infrastructure\Controller\BaseController;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Access\Task\Permission;
use Bitrix\Tasks\V2\Internal\Integration\CRM\Command\AddItemsCommand;
use Bitrix\Tasks\V2\Internal\Integration\CRM\Command\DeleteItemsCommand;
use Bitrix\Tasks\V2\Internal\Integration\CRM\Command\SetItemsCommand;
use Bitrix\Tasks\V2\Internal\Integration\CRM\Entity\CrmItemCollection;
use Bitrix\Tasks\V2\Internal\Integration\CRM\Provider\CrmItemProvider;
use Bitrix\Tasks\V2\Internal\Integration\CRM\Access\Task;

class Item extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Task.CRM.Item.list
	 */
	#[CloseSession]
	public function listAction(
		#[Permission\Read]
		Entity\Task $task,
		CrmItemProvider $crmItemProvider,
	): ?CrmItemCollection
	{
		return $crmItemProvider->getByIds(
			ids: (array)$task->crmItemIds,
			taskId: $task->id,
			userId: $this->userId,
		);
	}

	/**
	 * @ajaxAction tasks.V2.Task.CRM.Item.set
	 */
	public function setAction(
		#[Permission\Read]
		#[Task\Item\Permission\ReadChangedItems]
		Entity\Task $task,
	): ?bool
	{
		$result = (new SetItemsCommand(
			taskId: $task->id,
			userId: $this->userId,
			crmItemIds: $task->crmItemIds
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}

	/**
	 * @ajaxAction tasks.V2.Task.CRM.Item.delete
	 */
	public function deleteAction(
		#[Permission\Read]
		#[Task\Item\Permission\ReadItems]
		Entity\Task $task,
	): ?bool
	{
		$result = (new DeleteItemsCommand(
			taskId: $task->id,
			userId: $this->userId,
			crmItemIds: $task->crmItemIds
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}

	/**
	 * @ajaxAction tasks.V2.Task.CRM.Item.add
	 */
	public function addAction(
		#[Permission\Read]
		#[Task\Item\Permission\ReadItems]
		Entity\Task $task,
	): ?bool
	{
		$result = (new AddItemsCommand(
			taskId: $task->id,
			userId: $this->userId,
			crmItemIds: $task->crmItemIds
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}
}