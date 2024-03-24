<?php

namespace Bitrix\Tasks\Integration\AI\User;

use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Member\Config\WorkConfig;
use Bitrix\Tasks\Member\Type\Member;

class Author
{
	private const ROLE = RoleDictionary::ROLE_DIRECTOR;
	private int $taskId;
	private Member $creator;

	public function __construct(int $taskId)
	{
		$this->taskId = $taskId;
		$this->init();
	}

	public function getName(): string
	{
		return trim($this->creator->getName());
	}

	public function getWorkPosition(): string
	{
		return $this->creator->getWorkPosition();
	}

	public function toMeta(): array
	{
		return [
			'author' => [
				'name' => $this->getName(),
				'work_position' => $this->getWorkPosition(),
			],
		];
	}

	private function init(): void
	{
		$this->creator = TaskObject::wakeUpObject(['ID' => $this->taskId])
				->getMemberService()
				->get([static::ROLE], new WorkConfig())
				->getMembers(static::ROLE)
				->pop();
	}
}