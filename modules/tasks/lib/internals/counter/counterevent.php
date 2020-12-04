<?php
namespace Bitrix\Tasks\Internals\Counter;

/**
 * Class CounterEvent
 *
 * @package Bitrix\Tasks\Internals\Counter
 */
class CounterEvent
{
	/* @var string $type */
	private $type;
	/* @var array $data */
	private $data;

	public function __construct(string $type, array $data = [])
	{
		$this->type = $type;
		$this->data = $data;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getData(): array
	{
		return $this->data;
	}

	public function getUserId(): int
	{
		$userId = 0;

		switch ($this->type)
		{
			case CounterDictionary::EVENT_AFTER_TASK_VIEW:
			case CounterDictionary::EVENT_AFTER_COMMENTS_READ_ALL:
				$userId = (int) $this->data['USER_ID'];
				break;
		}

		return $userId;
	}

	public function getTaskId(): int
	{
		$taskId = 0;

		switch ($this->type)
		{
			case CounterDictionary::EVENT_AFTER_TASK_VIEW:
			case CounterDictionary::EVENT_AFTER_COMMENT_ADD:
			case CounterDictionary::EVENT_AFTER_COMMENT_DELETE:
			case CounterDictionary::EVENT_AFTER_TASK_MUTE:
				$taskId = (int) $this->data['TASK_ID'];
				break;

			case CounterDictionary::EVENT_AFTER_TASK_ADD:
			case CounterDictionary::EVENT_AFTER_TASK_DELETE:
			case CounterDictionary::EVENT_AFTER_TASK_RESTORE:
			case CounterDictionary::EVENT_TASK_EXPIRED:
			case CounterDictionary::EVENT_TASK_EXPIRED_SOON:
				$taskId = (int) $this->data['ID'];
				break;

			case CounterDictionary::EVENT_AFTER_TASK_UPDATE:
				$taskId = (int) $this->data['OLD_RECORD']['ID'];
				break;
		}

		return $taskId;
	}


}