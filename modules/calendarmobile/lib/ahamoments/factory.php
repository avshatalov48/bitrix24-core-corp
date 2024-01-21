<?php

namespace Bitrix\CalendarMobile\AhaMoments;

use Bitrix\Calendar\Core\Base\SingletonTrait;

class Factory
{
	use SingletonTrait;
	public function getAhaInstance($name)
	{
		if ($name === 'SyncCalendar')
		{
			return new SyncCalendar();
		}

		if ($name === 'SyncError')
		{
			return new SyncError();
		}

		return null;
	}
}