<?php

namespace Bitrix\Tasks\Internals\Project\Event;

/**
 * Class Event
 *
 * @package Bitrix\Tasks\Internals\Project\Event
 */
class Event
{
	private $type;
	private $data;

	public function __construct(string $type, array $data)
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

	public function getGroupId(): int
	{
		$groupId = 0;

		switch ($this->type)
		{
			case EventTypeDictionary::EVENT_PROJECT_ADD:
			case EventTypeDictionary::EVENT_PROJECT_BEFORE_UPDATE:
			case EventTypeDictionary::EVENT_PROJECT_UPDATE:
			case EventTypeDictionary::EVENT_PROJECT_REMOVE:
				$groupId = (int)$this->data['ID'];
				break;

			case EventTypeDictionary::EVENT_PROJECT_USER_ADD:
			case EventTypeDictionary::EVENT_PROJECT_USER_UPDATE:
			case EventTypeDictionary::EVENT_PROJECT_USER_REMOVE:
				$groupId = (int)$this->data['GROUP_ID'];
				break;

			default:
				break;
		}

		return $groupId;
	}
}