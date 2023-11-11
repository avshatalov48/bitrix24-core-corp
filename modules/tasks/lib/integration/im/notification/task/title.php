<?php

namespace Bitrix\Tasks\Integration\IM\Notification\Task;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\Registry\GroupRegistry;
use Bitrix\Tasks\Internals\TaskObject;

class Title
{
	private TaskObject $task;
	private ?string $bUrl;

	public function __construct(TaskObject $task, ?string $bUrl = null)
	{
		$this->task = $task;
		$this->bUrl = $bUrl;
	}

	public function getFormatted(?string $lang = null): string
	{
		$name = '[#' . $this->task->getId() . '] ';

		if ($this->bUrl)
		{
			$name .= '[URL=#PATH_TO_TASK#]';
		}

		$name .= $this->task->getTitle();

		if ($this->bUrl)
		{
			$name .= '[/URL]';
		}

		if ($this->task->getGroupId() && \CModule::IncludeModule('socialnetwork'))
		{
			$group = GroupRegistry::getInstance()->get($this->task->getGroupId());
			if (isset($group['NAME']))
			{
				$decodedName = \Bitrix\Main\Text\Emoji::decode($group['NAME']);
				$text = Loc::getMessage('TASKS_NOTIFICATIONS_IN_GROUP', null, $lang) . ' ' . $decodedName;
				$name .= ' (' . $text . ')';
			}
		}

		return $name;
	}
}