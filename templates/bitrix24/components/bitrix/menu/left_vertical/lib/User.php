<?php
namespace Bitrix\Intranet\LeftMenu;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class User
{
	public function isAdmin()
	{
		return IsModuleInstalled('bitrix24')
			&& $GLOBALS['USER']->CanDoOperation('bitrix24_config')
			|| !IsModuleInstalled('bitrix24')
			&& $GLOBALS['USER']->IsAdmin();
	}
}
