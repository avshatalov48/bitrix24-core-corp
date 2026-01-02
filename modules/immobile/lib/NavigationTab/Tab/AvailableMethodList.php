<?php

namespace Bitrix\ImMobile\NavigationTab\Tab;

enum AvailableMethodList: string
{
	case RECENT_LIST = 'recentList';
	case USER_DATA = 'userData';
	case PORTAL_COUNTERS = 'portalCounters';
	case IM_COUNTERS = 'imCounters';
	case MOBILE_REVISION = 'mobileRevision';
	case SERVER_TIME = 'serverTime';
	case DESKTOP_STATUS = 'desktopStatus';
	case PROMOTION = 'promotion';
	case DEPARTMENT_COLLEAGUES = 'departmentColleagues';
	case TARIFF_RESTRICTION = 'tariffRestriction';
	case ACTIVE_CALLS = 'activeCalls';
	case ANCHORS = 'anchors';
	case CHATS_LIST = 'chatsList';
	case COPILOT_LIST = 'copilotList';
	case CHANNEL_LIST = 'channelList';
	case COLLAB_LIST = 'collabList';

	case TASK_LIST = 'taskList';
}
