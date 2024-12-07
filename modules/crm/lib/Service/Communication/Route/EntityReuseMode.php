<?php

namespace Bitrix\Crm\Service\Communication\Route;

use Bitrix\Main\Localization\Loc;

enum EntityReuseMode: int
{
	case new = 1;
	case exist = 2;

	public function title(): string
	{
		return match($this)
		{
			self::new => Loc::getMessage('CRM_COMMUNICATION_ROUTE_SETTINGS_ERM_NEW'),
			self::exist => Loc::getMessage('CRM_COMMUNICATION_ROUTE_SETTINGS_ERM_EXIST'),
		};
	}

	public static function getInstanceByValue(int $value): ?self
	{
		if ($value === self::new->value)
		{
			return self::new;
		}

		if ($value === self::exist->value)
		{
			return self::exist;
		}

		return null;
	}
}
