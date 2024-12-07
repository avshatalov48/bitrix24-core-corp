<?php

namespace Bitrix\Crm\Activity;

use Bitrix\Crm\Activity\Provider\Base;
use Bitrix\Crm\Activity\Provider\OpenLine;
use Bitrix\Crm\Activity\Provider\ToDo\ToDo;
use Bitrix\Crm\Service\Communication\Channel\Provider\Dummy;

final class ProviderFactory
{
	public static function getProviderInstance(string $id): ?Base
	{
		if ($id === ToDo::PROVIDER_ID)
		{
			return new ToDo();
		}

		if ($id === OpenLine::getId())
		{
			return new OpenLine();
		}

		return null;
	}

	public static function getProviderInstanceByChannelCode(string $code): ?Base
	{
		if ($code === Dummy::getCode())
		{
			return new ToDo();
		}

		return null;
	}
}
