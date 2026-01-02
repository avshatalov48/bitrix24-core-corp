<?php

namespace Bitrix\Crm\Agent\OldEntityView;

use Bitrix\Crm\Component\Utils\OldEntityViewDisableHelper;

class Sunset
{
	public static function run(): void
	{
		OldEntityViewDisableHelper::migrateToNewLayout();
	}
}
