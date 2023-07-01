<?php

namespace Bitrix\Crm\Timeline;

class LogMessageType
{
	public const UNDEFINED = 0;
	public const CALL_INCOMING = 1;
	public const OPEN_LINE_INCOMING = 2;
	public const TODO_CREATED = 3;
	public const PING = 4;
	public const REST = 5;
	public const SMS_STATUS = 6;
	public const CALENDAR_SHARING_NOT_VIEWED = 7;
	public const CALENDAR_SHARING_VIEWED = 8;
	public const CALENDAR_SHARING_EVENT_CREATED = 9;
	public const CALENDAR_SHARING_EVENT_DOWNLOADED = 10;
	public const CALENDAR_SHARING_EVENT_CONFIRMED = 11;
	public const CALENDAR_SHARING_LINK_COPIED = 12;
}
