<?php

namespace Bitrix\Crm\Timeline;

class TimelineType
{
	public const UNDEFINED = 0;
	public const ACTIVITY = 1;
	public const CREATION = 2;
	public const MODIFICATION = 3;
	public const LINK = 4;
	public const UNLINK = 5;
	public const MARK = 6; //WAITING/IGNORED/SUCCESS/RENEW/FAILED
	public const COMMENT = 7;
	public const WAIT = 8;
	public const BIZPROC = 9;
	public const CONVERSION = 10;
	public const SENDER = 11;
	public const DOCUMENT = 12;
	public const RESTORATION = 13;
	public const ORDER = 14;
	public const ORDER_CHECK = 15;
	public const SCORING = 16;
	public const EXTERNAL_NOTICE = 17;
	public const FINAL_SUMMARY = 18;
	public const DELIVERY = 19;
	public const FINAL_SUMMARY_DOCUMENTS = 20;
	public const STORE_DOCUMENT = 21;
	public const PRODUCT_COMPILATION = 22;
	public const SIGN_DOCUMENT = 23;
	public const SIGN_DOCUMENT_LOG = 24;
	public const LOG_MESSAGE = 25;
	public const CALENDAR_SHARING = 26;
	public const TASK = 27;
	public const AI_CALL_PROCESSING = 28;
	public const SIGN_B2E_DOCUMENT = 29;
	public const SIGN_B2E_DOCUMENT_LOG = 30;
	public const BOOKING = 31;
}
