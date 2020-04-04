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
}