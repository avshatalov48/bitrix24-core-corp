<?php

namespace Bitrix\Crm\Activity\Analytics;

use Bitrix\Crm\Integration\Analytics;

final class Dictionary
{
	public const TOOL = Analytics\Dictionary::TOOL_CRM;
	public const OPERATIONS_CATEGORY = 'activity_operations';
	public const TOUCH_EVENT = 'activity_touch';
	public const ADD_EVENT = 'activity_create';
	public const EDIT_EVENT = 'activity_edit';
	public const COMPLETE_EVENT = 'activity_complete';

	public const TODO_TYPE = 'todo_activity';
	public const REPEAT_SALE_TYPE = 'repeat_sale';

	public const REPEAT_SALE_ELEMENT_SYS = 'repeat_sale_sys';
	public const REPEAT_SALE_ELEMENT_USER = 'repeat_sale_user';

	public const LIST_SUB_SECTION = Analytics\Dictionary::SUB_SECTION_LIST;
	public const KANBAN_SUB_SECTION = Analytics\Dictionary::SUB_SECTION_KANBAN;
	public const KANBAN_DROPZONE_SUB_SECTION = 'kanban_dropzone';
	public const ACTIVITIES_SUB_SECTION = Analytics\Dictionary::SUB_SECTION_ACTIVITIES;
	public const DEADLINES_SUB_SECTION = Analytics\Dictionary::SUB_SECTION_DEADLINES;
	public const DETAILS_SUB_SECTION = Analytics\Dictionary::SUB_SECTION_DETAILS;
	public const NOTIFICATION_POPUP_SUB_SECTION = 'notification_popup';
	public const COMPLETE_BUTTON_ELEMENT = 'complete_button';
	public const CHECKBOX_ELEMENT = 'checkbox';
}
