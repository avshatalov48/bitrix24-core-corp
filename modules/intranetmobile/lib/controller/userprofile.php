<?php

namespace Bitrix\IntranetMobile\Controller;

use Bitrix\Main\Engine\Controller;

class UserProfile extends Controller
{
	public function isNeedToShowMiniProfileAction(): bool
	{
		$isNeedToShow = (
			\CUserOptions::GetOption('intranetmobile', 'isNeedToShowMiniProfile', false) ?? false)
			&& !(\CUserOptions::GetOption('intranetmobile', 'isMiniProfileShowed', false) ?? false)
		;

		if ($isNeedToShow)
		{
			\CUserOptions::SetOption('intranetmobile', 'isMiniProfileShowed', true);
			\CUserOptions::DeleteOption('intranetmobile', 'isNeedToShowMiniProfile');
		}

		return $isNeedToShow;
	}
}