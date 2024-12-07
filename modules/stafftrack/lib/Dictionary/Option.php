<?php

namespace Bitrix\StaffTrack\Dictionary;

enum Option: string
{
	case DEFAULT_MESSAGE = 'default_message';
	case DEFAULT_LOCATION = 'default_location';
	case DEFAULT_CUSTOM_LOCATION = 'default_custom_location';
	case SEND_MESSAGE = 'send_message';
	case SEND_GEO = 'send_geo';
	case TIMEZONE_OFFSET = 'timezone_offset';
	case LAST_SELECTED_DIALOG_ID = 'last_selected_dialog_id';
	case SELECTED_DEPARTMENT_ID = 'selected_department_id';
	case IS_FIRST_HELP_VIEWED = 'is_first_help_viewed';
	case TIMEMAN_INTEGRATION_ENABLED = 'timeman_integration_enabled';
}
