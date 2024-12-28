<?php

namespace Bitrix\Sign\Type\MyDocumentsGrid;

use Bitrix\Sign\Type\ValuesTrait;

enum FilterStatus: string
{
	use ValuesTrait;

	case SIGNED = 'SIGNED';
	case IN_PROGRESS = 'INPROGRESS';
	case NEED_ACTION = 'NEEDACTION';
	case MY_REVIEW = 'MYREVIEW';
	case MY_SIGNED = 'MYSIGNED';
	case MY_EDITED = 'MYEDITED';
	case MY_STOPPED = 'MYSTOPPED';
	case STOPPED = 'STOPPED';
	/**
	 * @deprecated only used in mobile app
	 */
	case MY_ACTION_DONE = 'MYACTIONDONE';
}