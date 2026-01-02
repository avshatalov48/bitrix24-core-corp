<?php

namespace Bitrix\Mobile\Profile\Enum;

enum TabType: string
{
	case COMMON = 'common';
	case TASKS = 'tasks';
	case CALENDAR = 'calendar';
	case FILES = 'files';
	case LIVE_FEED = 'live_feed';
	case GROUPS = 'groups';
	case DOCUMENTS = 'documents';
}
