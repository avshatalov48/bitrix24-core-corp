<?php

namespace Bitrix\CrmMobile\AhaMoments;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\ArgumentException;

class Factory
{
	use Singleton;

	public function getAhaInstance($name): Base
	{
		if ($name === 'GoToChat')
		{
			return GoToChat::getInstance();
		}

		if ($name === 'yoochecks')
		{
			return Yoochecks::getInstance();
		}

		throw new ArgumentException('Unknown ahamoment: ' . $name);
	}
}
