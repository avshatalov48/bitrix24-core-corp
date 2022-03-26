<?php
namespace Bitrix\Intranet\UI\LeftMenu;

class User
{
	public function isAdmin()
	{
		global $USER;
		return IsModuleInstalled('bitrix24')
			&& $USER->CanDoOperation('bitrix24_config')
			|| !IsModuleInstalled('bitrix24')
			&& $USER->IsAdmin();
	}
}
