<?php

namespace Bitrix\Sign\Type\Im\Notification;

/**
 * @see IM_NOTIFY_MESSAGE
 * @see IM_NOTIFY_CONFIRM
 * @see IM_NOTIFY_FROM
 * @see IM_NOTIFY_SYSTEM
 */
enum NotificationType: int
{
	case MESSAGE = 0;
	case CONFIRM = 1;
	case FROM = 2;
	case SYSTEM = 4;
}