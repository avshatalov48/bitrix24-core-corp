<?php

namespace Bitrix\DocumentGenerator\Service;

use Bitrix\DocumentGenerator\Document;
use Bitrix\DocumentGenerator\Model\ActualizeQueueTable;
use Bitrix\Main\Application;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\Type\DateTime;

class ActualizeQueue
{
	/**
	 * @var ActualizeQueueTable
	 */
	protected $actualizeQueueTable = ActualizeQueueTable::class;

	/**
	 * @var ActualizeQueue\Task[]
	 */
	protected array $cache = [];

	public function addTask(ActualizeQueue\Task $task): self
	{
		if ($task->isPositionImmediately())
		{
			$queuedTask = $this->getTask($task->getDocumentId());
			if ($queuedTask)
			{
				if ($task->getDocument())
				{
					$queuedTask->setDocument($task->getDocument());
				}
				$this->processTask($queuedTask);
			}

			return $this;
		}

		$this->saveTask($task);

		if ($task->isPositionBackground())
		{
			Application::getInstance()->addBackgroundJob(function() use ($task) {
				$this->processTask($task);
			});
		}

		return $this;
	}

	protected function processTask(ActualizeQueue\Task $task): void
	{
		$this->removeTask($task->getDocumentId());
		$document = $task->getDocument() ?? Document::loadById($task->getDocumentId());
		if (!$document)
		{
			return;
		}

		$addedTime = $task->getAddedTime();
		if (!$addedTime || $document->UPDATE_TIME < $addedTime)
		{
			$document->actualize($task->getUserId());
		}
	}

	protected function hasTask(int $documentId): bool
	{
		return $this->getTask($documentId) !== null;
	}

	protected function removeTask(int $documentId): void
	{
		unset($this->cache[$documentId]);
		$this->actualizeQueueTable::delete($documentId);
	}

	protected function getTask(int $documentId): ?ActualizeQueue\Task
	{
		if (!array_key_exists($documentId, $this->cache))
		{
			$this->cache[$documentId] = null;
			$record = $this->loadRecord($documentId);
			if ($record)
			{
				$this->cache[$documentId] = $this->createTaskByRecord($record);
			}
		}

		return $this->cache[$documentId];
	}

	protected function createTaskByRecord(EntityObject $record): ActualizeQueue\Task
	{
		return (new ActualizeQueue\Task($record->get('DOCUMENT_ID')))
			->setUserId($record->get('USER_ID'))
			->setAddedTime($record->get('ADDED_TIME'))
		;
	}

	protected function loadRecord(int $documentId): ?EntityObject
	{
		return $this->actualizeQueueTable::getByPrimary($documentId)->fetchObject();
	}

	protected function saveTask(ActualizeQueue\Task $task): self
	{
		if (!$this->hasTask($task->getDocumentId()))
		{
			$task->setAddedTime(new DateTime());
			$this->actualizeQueueTable::add([
				'DOCUMENT_ID' => $task->getDocumentId(),
				'USER_ID' => $task->getUserId(),
			]);
			$this->cache[$task->getDocumentId()] = $task;
		}

		return $this;
	}

	/**
	 * @param int $count
	 * @return ActualizeQueue\Task[]
	 */
	protected function getNextPack(int $count): array
	{
		$collection = $this->actualizeQueueTable::getList([
			'order' => [
				'ADDED_TIME' => 'ASC',
			],
			'limit' => $count,
		])->fetchCollection();

		$result = [];

		foreach ($collection as $record)
		{
			$result[] = $this->createTaskByRecord($record);
		}

		return $result;
	}

	public static function process(int $count = 5): ?string
	{
		if (!Application::getInstance()->getConnectionPool()->getConnection()->isTableExists(ActualizeQueueTable::getTableName()))
		{
			return '\\Bitrix\\DocumentGenerator\\Service\\ActualizeQueue::process('.$count.');';
		}

		$queue = ServiceLocator::getInstance()->get('documentgenerator.service.actualizeQueue');
		$tasks = $queue->getNextPack($count);
		foreach ($tasks as $task)
		{
			$queue->processTask($task);
		}

		return '\\Bitrix\\DocumentGenerator\\Service\\ActualizeQueue::process('.$count.');';
	}
}
