<?php

namespace Bitrix\Tasks\Integration\Pull;

abstract class PushCommand
{
	public const TASK_VIEWED = 'task_view';
	public const TASK_ADDED = 'task_add';
	public const TASK_UPDATED = 'task_update';
	public const TASK_DELETED = 'task_remove';
	public const TASK_TIMER_STARTED = 'task_timer_start';
	public const TASK_TIMER_STOPPED = 'task_timer_stop';
	public const TASK_STAGE_UPDATED = 'stage_change';
	public const TASK_PULL_UNSUBSCRIBE = 'task_pull_unsubscribe';

	public const TEMPLATE_ADDED = 'template_add';
	public const TEMPLATE_UPDATED = 'template_update';

	public const FLOW_ADDED = 'flow_add';
	public const FLOW_UPDATED = 'flow_update';
	public const FLOW_DELETED = 'flow_delete';

	public const TAG_ADDED = 'tag_added';
	public const TAG_UPDATED = 'tag_changed';

	public const COMMENT_DELETED = 'comment_delete';
	public const COMMENT_ADDED = 'comment_add';

	public const COMMENTS_VIEWED = 'comment_read_all';
	public const PROJECT_COMMENTS_VIEWED = 'project_read_all';
	public const SCRUM_COMMENTS_VIEWED = 'scrum_read_all';

	public const EFFICIENCY_RECOUNTED = 'user_efficiency_counter';

	public const USER_OPTION_UPDATED = 'user_option_changed';
	public const PROJECT_USER_OPTION_UPDATED = 'project_user_option_changed';

}