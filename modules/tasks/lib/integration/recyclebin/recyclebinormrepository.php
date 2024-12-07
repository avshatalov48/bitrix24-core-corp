<?php
/**
 * Bitrix Framework
 *
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2022 Bitrix
 */

namespace Bitrix\Tasks\Integration\Recyclebin;

use Bitrix\Main\AccessDeniedException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Recyclebin\Internals\Models\EO_Recyclebin;
use Bitrix\Recyclebin\Internals\Models\EO_Recyclebin_Collection;
use Bitrix\Recyclebin\Internals\Models\RecyclebinDataTable;
use Bitrix\Recyclebin\Internals\Models\RecyclebinFileTable;
use Bitrix\Recyclebin\Internals\Models\RecyclebinTable;
use Bitrix\Recyclebin\Recyclebin;
use Bitrix\Tasks\Internals\Existence\Exception\ExistenceCheckException;
use Bitrix\Tasks\Internals\Log\LogFacade;
use Bitrix\Tasks\Internals\Task\Template\TemplateCollection;
use Bitrix\Tasks\Internals\Task\Template\TemplateObject;
use Bitrix\Tasks\Internals\TaskCollection;
use Bitrix\Tasks\Internals\TaskObject;
use Exception;
use Throwable;

class RecycleBinOrmRepository implements RecyclebinTasksRepositoryInterface
{
	private TasksMaxDaysInRecycleBin $maxDaysTTL;
	private TasksMaxToRemoveFromRecycleBin $maxTasksToRemove;
	private EO_Recyclebin_Collection $recyclebinCollection;
	private EO_Recyclebin_Collection $corruptedRecyclebinCollection;
	private TaskCollection $taskCollection;
	private TemplateCollection $templateCollection;

	public function removeTasksFromRecycleBin(
		TasksMaxDaysInRecycleBin $maxDaysTTL,
		TasksMaxToRemoveFromRecycleBin $maxTasksToRemove
	): void
	{
		$this->maxDaysTTL = $maxDaysTTL;
		$this->maxTasksToRemove = $maxTasksToRemove;

		try
		{
			$this
				->fillRecycleBinCollection()
				->fillEntityCollections()
				->clearExistingEntities()
				->removeCorruptedEntities()
				->delete();
		}
		catch (Throwable $throwable)
		{
			LogFacade::logThrowable($throwable);
		}
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function fillRecycleBinCollection(): static
	{
		$query = RecyclebinTable::query();
		$query->setSelect(['ID', 'MODULE_ID', 'ENTITY_ID', 'ENTITY_TYPE', 'TIMESTAMP'])
			->where('MODULE_ID', Manager::MODULE_ID)
			->where('TIMESTAMP', '<=', $this->maxDaysTTL->getAsDateTimeFromNow())
			->setOrder(['TIMESTAMP' => 'ASC'])
			->setLimit($this->maxTasksToRemove->getValue());

		$this->recyclebinCollection = $query->exec()->fetchCollection();

		return $this;
	}

	private function fillEntityCollections(): static
	{
		$this->taskCollection = new TaskCollection();
		$this->templateCollection = new TemplateCollection();
		foreach ($this->recyclebinCollection as $entity)
		{
			if ($this->isTask($entity))
			{
				$this->taskCollection->add(TaskObject::wakeUpObject(['ID' => (int)$entity->getEntityId()]));
			}
			elseif ($this->isTemplate($entity))
			{
				$this->templateCollection->add(TemplateObject::wakeUpObject(['ID' => (int)$entity->getEntityId()]));
			}
		}

		return $this;
	}

	/**
	 * @throws ExistenceCheckException
	 */
	private function clearExistingEntities(): static
	{
		$existingTaskIds = $this->taskCollection->clearNonExistentEntities()->getIdList();
		$existingTemplateIds = $this->templateCollection->clearNonExistentEntities()->getIdList();

		$this->corruptedRecyclebinCollection = new EO_Recyclebin_Collection();
		foreach ($this->recyclebinCollection as $entity)
		{
			if (
				(
					$this->isTask($entity)
					&& in_array((int)$entity->getEntityId(), $existingTaskIds, true)
				)
				|| (
					$this->isTemplate($entity)
					&& in_array((int)$entity->getEntityId(), $existingTemplateIds, true)
				)
			)
			{
				$this->recyclebinCollection->remove($entity);
				$this->corruptedRecyclebinCollection->add($entity);
			}
		}

		return $this;
	}

	/**
	 * Remove from the recyclebin entities
	 * that are both in the recyclebin and in their table (b_tasks, b_tasks_template)
	 * @see http://jabber.bx/view.php?id=172007
	 *
	 * @throws Exception
	 */
	private function removeCorruptedEntities(): static
	{
		foreach ($this->corruptedRecyclebinCollection as $entity)
		{
			if (RecyclebinTable::delete($entity->getId()))
			{
				RecyclebinDataTable::deleteByRecyclebinId($entity->getId());
				RecyclebinFileTable::deleteByRecyclebinId($entity->getId());
			}
		}

		return $this;
	}

	/**
	 * @throws AccessDeniedException
	 */
	private function delete(): void
	{
		foreach ($this->recyclebinCollection as $entity)
		{
			Recyclebin::remove($entity->getId(), ['skipAdminRightsCheck' => true]);
		}
	}

	private function isTask(EO_Recyclebin $entity): bool
	{
		return $entity->getEntityType() === Manager::TASKS_RECYCLEBIN_ENTITY;
	}

	private function isTemplate(EO_Recyclebin $entity): bool
	{
		return $entity->getEntityType() === Manager::TASKS_TEMPLATE_RECYCLEBIN_ENTITY;
	}
}