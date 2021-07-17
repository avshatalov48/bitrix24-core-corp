<?php

namespace Bitrix\Tasks\Internals\Project\Event;

/**
 * Class EventTypeDictionary
 *
 * @package Bitrix\Tasks\Internals\Project\Event
 */
class EventTypeDictionary
{
	public const EVENT_PROJECT_ADD = 'project_add';
	public const EVENT_PROJECT_BEFORE_UPDATE = 'project_before_update';
	public const EVENT_PROJECT_UPDATE = 'project_update';
	public const EVENT_PROJECT_REMOVE = 'project_remove';
	public const EVENT_PROJECT_USER_ADD = 'project_user_add';
	public const EVENT_PROJECT_USER_UPDATE = 'project_user_update';
	public const EVENT_PROJECT_USER_REMOVE = 'project_user_remove';
}