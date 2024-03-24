<?php

namespace Bitrix\Tasks\Internals\Task\Search\Repository;

use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\Internals\Member\MemberFacade;
use Bitrix\Tasks\Internals\Task\Search\Exception\SearchIndexException;
use Bitrix\Tasks\Internals\Task\Search\RepositoryInterface;
use Bitrix\Tasks\Member\Service\TaskMemberService;
use Bitrix\Tasks\Provider\Tag\TagList;
use Bitrix\Tasks\Provider\Tag\TagQuery;
use Bitrix\Tasks\Provider\TaskList;
use Bitrix\Tasks\Provider\TaskQuery;
use Exception;

class TaskRepository implements RepositoryInterface
{
	private int $taskId;
	private ?array $task = null;
	private MemberFacade $memberFacade;

	public function __construct(int $taskId)
	{
		$this->taskId = $taskId;
		$this->init();
	}

	/**
	 * @throws SearchIndexException
	 */
	public function getTask(): array
	{
		if (!is_null($this->task))
		{
			return $this->task;
		}

		$query = new TaskQuery();
		$query->skipAccessCheck()->setSelect($this->getSelect())->setWhere(['=ID' => $this->taskId]);

		try
		{
			$tasks = (new TaskList())->getList($query);
		}
		catch (Exception $exception)
		{
			throw new SearchIndexException($exception->getMessage());
		}

		$this->task = $tasks[0] ?? null;
		if (is_null($this->task))
		{
			throw new SearchIndexException("Task {$this->taskId} not found");
		}

		$this->fillWithMembers()->fillWithCheckLists()->fillWithTags();

		return $this->task;
	}

	/**
	 * @throws SearchIndexException
	 */
	private function fillWithMembers(): static
	{
		$membersResult = $this->memberFacade->load();
		if (!$membersResult->isSuccess())
		{
			throw new SearchIndexException($membersResult->getErrorMessages()[0]);
		}

		$this->task['CREATED_BY'] = (int)$this->memberFacade->getCreatedByMemberId();
		$this->task['RESPONSIBLE_ID'] = (int)$this->memberFacade->getResponsibleMemberId();
		$this->task['ACCOMPLICES'] = $this->memberFacade->getAccompliceMembersIds();
		$this->task['AUDITORS'] = $this->memberFacade->getAuditorMembersIds();

		return $this;
	}

	/**
	 * @throws SearchIndexException
	 */
	private function fillWithCheckLists(): static
	{
		try
		{
			$checklists = TaskCheckListFacade::getByEntityId($this->taskId);
		}
		catch (Exception $exception)
		{
			throw new SearchIndexException($exception->getMessage());
		}
		$this->task['CHECKLIST'] = $checklists;

		return $this;
	}

	private function fillWithTags(): static
	{
		$this->task['TAGS'] = [];

		$query = (new TagQuery())->setSelect(['NAME'])->setWhere(['TASK_ID' => $this->taskId]);
		$collection = (new TagList())->getCollection($query);
		foreach ($collection as $tagObject)
		{
			$this->task['TAGS'][] = $tagObject->getName();
		}

		return $this;
	}

	private function getSelect(): array
	{
		return [
			'ID',
			'TITLE',
			'DESCRIPTION',
			'GROUP_ID',
			'UF_CRM_TASK',
		];
	}

	private function init(): void
	{
		$this->memberFacade = new MemberFacade(new TaskMemberService($this->taskId));
	}
}
