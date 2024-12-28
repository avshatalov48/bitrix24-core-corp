<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Enums;

enum Category: string
{
	case LIVEFEED = 'livefeed';
	case READONLY_LIVEFEED = 'readonly_livefeed';
	case LIVEFEED_COMMENTS = 'livefeed_comments';
	case TASKS = 'tasks';
	case TASKS_COMMENTS = 'tasks_comments';
	case MAIL = 'mail';
	case MAIL_CRM = 'mail_crm';
	case LANDING = 'landing';
	case LANDING_SETTING = 'landing_setting';
	case CALENDAR = 'calendar';
	case CALENDAR_COMMENTS = 'calendar_comments';
	case CRM_ACTIVITY = 'crm_activity';
	case CRM_COMMENT_FIELD = 'crm_comment_field';
	case CRM_TIMELINE_COMMENT = 'crm_timeline_comment';
	case CRM_CALL_ASSESSMENT = 'crm_call_assessment';
	case CHAT = 'chat';
	case SYSTEM = 'system';
	case LIST = 'list';
	case PRODUCT_DESCRIPTION = 'product_description';
}
