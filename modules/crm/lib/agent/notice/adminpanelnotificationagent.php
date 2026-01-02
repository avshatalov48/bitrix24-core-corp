<?php

namespace Bitrix\Crm\Agent\Notice;

use Bitrix\Main\Localization\Loc;

class AdminPanelNotificationAgent
{
	public static function run(string $query, string $tag)
	{
		if (!\CAdminNotify::GetList([], ['TAG' => $tag])->Fetch())
		{
			\CAdminNotify::Add([
				'MESSAGE' => Loc::getMessage('CRM_ADMINPANEL_NOTICE', ['#QUERY#' => $query]),
				'TAG' => $tag,
				'MODULE_ID' => 'iblock',
			]);
		}

		return ''; // finish agent execution
	}
}
