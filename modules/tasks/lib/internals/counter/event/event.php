<?php
namespace Bitrix\Tasks\Internals\Counter\Event;


/**
 * Class Event
 *
 * @package Bitrix\Tasks\Internals\Counter\Event
 */
class Event
{
	/* @var int $id  */
	private $id = 0;
	/* @var string $hitId */
	private $hitId;
	/* @var string $type */
	private $type;
	/* @var array $data */
	private $data = [];

	/**
	 * CounterEvent constructor.
	 * @param string $type
	 * @param array $data
	 */
	public function __construct(string $hitId, string $type)
	{
		$this->hitId = $hitId;
		$this->type = $type;
	}

	/**
	 * @param int $id
	 * @return $this
	 */
	public function setId(int $id): self
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @param array $data
	 * @return $this
	 */
	public function setData(array $data): self
	{
		$this->data = $this->prepareData($data);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getHitId(): string
	{
		return $this->hitId;
	}

	/**
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}

	/**
	 * @return int
	 */
	public function getUserId(): int
	{
		$userId = 0;

		switch ($this->type)
		{
			case EventDictionary::EVENT_AFTER_TASK_VIEW:
			case EventDictionary::EVENT_AFTER_COMMENTS_READ_ALL:
			case EventDictionary::EVENT_AFTER_PROJECT_READ_ALL:
			case EventDictionary::EVENT_AFTER_SCRUM_READ_ALL:
			case EventDictionary::EVENT_PROJECT_USER_ADD:
			case EventDictionary::EVENT_PROJECT_USER_UPDATE:
			case EventDictionary::EVENT_PROJECT_USER_DELETE:
			case EventDictionary::EVENT_AFTER_TASK_MUTE:
			case EventDictionary::EVENT_GARBAGE_COLLECT:
				$userId = (int) $this->data['USER_ID'];
				break;
		}

		return $userId;
	}

	/**
	 * @return int
	 */
	public function getTaskId(): int
	{
		$taskId = 0;

		switch ($this->type)
		{
			case EventDictionary::EVENT_AFTER_TASK_VIEW:
			case EventDictionary::EVENT_AFTER_COMMENT_ADD:
			case EventDictionary::EVENT_AFTER_COMMENT_DELETE:
			case EventDictionary::EVENT_AFTER_TASK_MUTE:
			case EventDictionary::EVENT_AFTER_TASK_UPDATE:
				$taskId = (int) $this->data['TASK_ID'];
				break;

			case EventDictionary::EVENT_AFTER_TASK_ADD:
			case EventDictionary::EVENT_AFTER_TASK_DELETE:
			case EventDictionary::EVENT_AFTER_TASK_RESTORE:
			case EventDictionary::EVENT_TASK_EXPIRED:
			case EventDictionary::EVENT_TASK_EXPIRED_SOON:
				$taskId = (int) $this->data['ID'];
				break;
		}

		return $taskId;
	}

	/**
	 * @return int
	 */
	public function getGroupId(): int
	{
		$groupId = 0;

		switch ($this->type)
		{
			case EventDictionary::EVENT_AFTER_COMMENTS_READ_ALL:
			case EventDictionary::EVENT_AFTER_PROJECT_READ_ALL:
			case EventDictionary::EVENT_AFTER_SCRUM_READ_ALL:
			case EventDictionary::EVENT_PROJECT_DELETE:
			case EventDictionary::EVENT_PROJECT_USER_ADD:
			case EventDictionary::EVENT_PROJECT_USER_UPDATE:
			case EventDictionary::EVENT_PROJECT_USER_DELETE:
				$groupId = (int) $this->data['GROUP_ID'];
				break;
		}

		return $groupId;
	}

	/**
	 * @param array $data
	 * @return array
	 */
	private function prepareData(array $data): array
	{
		$validFields = [
			'ROLE',
			'FEATURE_PERM',
			'USER_ID',
			'TASK_ID',
			'ID',
			'OLD_RECORD',
			'GROUP_ID',
		];

		foreach ($data as $key => $row)
		{
			if (!in_array($key, $validFields))
			{
				unset($data[$key]);
			}
		}

		if (isset($data['OLD_RECORD']['ID']))
		{
			$data['TASK_ID'] = $data['OLD_RECORD']['ID'];
			unset($data['OLD_RECORD']);
		}

		return $data;
	}
}