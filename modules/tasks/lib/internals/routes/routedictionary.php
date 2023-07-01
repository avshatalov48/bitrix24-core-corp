<?php

namespace Bitrix\Tasks\Internals\Routes;

abstract class RouteDictionary
{
	public const PATH_TO_USER_TASK = '/company/personal/user/#user_id#/tasks/task/#action#/#task_id#/';
	public const PATH_TO_USER_TASKS_LIST = '/company/personal/user/#user_id#/tasks/';

	public const PATH_TO_GROUP_TASK = '/workgroups/group/#group_id#/tasks/task/#action#/#task_id#/';
	public const PATH_TO_GROUP_TASKS_LIST = '/workgroups/group/#group_id#/tasks/';

	public const PATH_TO_USER_TEMPLATE = '/company/personal/user/#user_id#/tasks/templates/template/#action#/#template_id#/';
	public const PATH_TO_USER_TEMPLATES_LIST = '/company/personal/user/#user_id#/tasks/templates/';

	public const PATH_TO_USER_TAGS = '/company/personal/user/#user_id#/tasks/tags/';

	public const PATH_TO_USER = '/company/personal/user/#user_id#/';
}