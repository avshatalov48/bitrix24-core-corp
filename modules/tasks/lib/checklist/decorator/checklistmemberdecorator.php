<?php

namespace Bitrix\Tasks\CheckList\Decorator;

use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\CheckList\Exception\CheckListException;
use Bitrix\Tasks\CheckList\Node\Nodes;
use Bitrix\Tasks\Control\Exception\TaskNotFoundException;
use Bitrix\Tasks\Control\Exception\TaskUpdateException;
use Bitrix\Tasks\Control\Task;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;

class CheckListMemberDecorator extends CheckListDecorator
{
	protected Task $handler;

	/**
	 * @throws CheckListException
	 * @throws TaskNotFoundException
	 * @throws TaskUpdateException
	 */
	public function mergeNodes(int $entityId, Nodes $nodes): Nodes
	{
		$mergeResult = $this->source::merge($entityId, $this->userId, $nodes->toArray());
		if (!$mergeResult->isSuccess())
		{
			$message = $mergeResult->getErrors()?->getMessages()[0] ?? null;
			$message = is_string($message) ? $message : $message['text'] ?? null;
			$message = is_string($message) ? $message : 'Unknown error';

			throw new CheckListException($message);
		}

		$updatedNodes = Nodes::createFromArray($mergeResult->getData()['TRAVERSED_ITEMS'] ?? []);

		$members = $this->extractMembers($nodes);
		if (empty($members))
		{
			return $updatedNodes;
		}

		$task = TaskRegistry::getInstance()->getObject($entityId);
		if (null === $task)
		{
			throw new CheckListException('No such task.');
		}

		$members['AUDITORS'] = array_unique(array_merge($members['AUDITORS'], $task->getAuditorMembersIds()));
		$members['ACCOMPLICES'] = array_unique(array_merge($members['ACCOMPLICES'], $task->getAccompliceMembersIds()));

		$this->handler->update($entityId, $members);

		return $updatedNodes;
	}

	protected function extractMembers(Nodes $nodes): array
	{
		$members = [
			'AUDITORS' => [],
			'ACCOMPLICES' => [],
		];

		foreach ($nodes as $node)
		{
			$members['AUDITORS'] = array_merge($members['AUDITORS'], $node->getAuditors());
			$members['ACCOMPLICES'] = array_merge($members['ACCOMPLICES'], $node->getAccomplices());
		}

		return $members;
	}

	protected static function getAccessControllerClass(): string
	{
		return TaskAccessController::class;
	}

	protected function init(): void
	{
		$this->handler = new Task($this->userId);
	}
}