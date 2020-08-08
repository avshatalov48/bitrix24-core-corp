<?php
namespace Bitrix\Crm\Timeline;
class TimelineType
{
	const UNDEFINED = 0;
	const ACTIVITY = 1;
	const CREATION = 2;
	const MODIFICATION = 3;
	const LINK = 4;
	const UNLINK = 5;
	const MARK = 6; //WAITING/IGNORED/SUCCESS/RENEW/FAILED
	const COMMENT = 7;
	const WAIT = 8;
	const BIZPROC = 9;
	const CONVERSION = 10;
	const SENDER = 11;
	const DOCUMENT = 12;
	const RESTORATION = 13;
	const ORDER = 14;
	const ORDER_CHECK = 15;
	const SCORING = 16;
	const EXTERNAL_NOTICE = 17;
	const FINAL_SUMMARY = 18;
	const DELIVERY = 19;
}