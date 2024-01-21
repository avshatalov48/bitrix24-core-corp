<?php

namespace Bitrix\Tasks\Internals\Task\Search\Repository;

use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\Control\Tag;
use Bitrix\Tasks\Internals\Task\Search\Exception\SearchIndexException;
use Bitrix\Tasks\Internals\Task\Search\RepositoryInterface;
use Bitrix\Tasks\Member\AbstractMemberService;
use Bitrix\Tasks\Member\Config\BaseConfig;
use Bitrix\Tasks\Member\Service\TaskMemberService;
use Bitrix\Tasks\Member\Type\Member;
use Bitrix\Tasks\Provider\Tag\TagList;
use Bitrix\Tasks\Provider\Tag\TagQuery;
use Bitrix\Tasks\Provider\TaskList;
use Bitrix\Tasks\Provider\TaskQuery;
use Exception;

class TaskRepository implements RepositoryInterface
{
	private ?array $task = null;
	private AbstractMemberService $memberService;
	private Tag $tagService;

	public function __construct(private int $taskId)
	{
		$this->memberService = new TaskMemberService($taskId);
		$this->tagService = new Tag();
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
		$membersResult = $this->memberService->get(RoleDictionary::getAvailableRoles(), new BaseConfig());
		if (!$membersResult->isSuccess())
		{
			throw new SearchIndexException($membersResult->getErrorMessages()[0]);
		}

		$members = $membersResult->getData();

		$this->task['CREATED_BY'] = (int)array_shift($members[RoleDictionary::ROLE_DIRECTOR])?->getUserId();
		$this->task['RESPONSIBLE_ID'] = (int)array_shift($members[RoleDictionary::ROLE_RESPONSIBLE])?->getUserId();
		$this->task['AUDITORS'] = array_map(
			static fn(Member $member): int => (int)$member?->getUserId(),
			$members[RoleDictionary::ROLE_AUDITOR]
		);
		$this->task['ACCOMPLICES'] = array_map(
			static fn(Member $member): int => (int)$member?->getUserId(),
			$members[RoleDictionary::ROLE_ACCOMPLICE]
		);

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
}