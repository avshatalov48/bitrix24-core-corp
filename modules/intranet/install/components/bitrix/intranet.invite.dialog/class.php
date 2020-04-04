<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;

Loc::loadMessages(__FILE__);

class CIntranetInviteDialogComponent extends \CBitrixComponent
{
	public function executeComponent()
	{
		global $USER;

		if (
			(
				CModule::IncludeModule('bitrix24')
				&& !CBitrix24::isInvitingUsersAllowed()
			)
			|| (
				!IsModuleInstalled('bitrix24')
				&& !$USER->CanDoOperation('edit_all_users')
			)
			|| !CModule::IncludeModule('iblock')
		)
		{
			return;
		}

		CJSCore::Init(array('clipboard'));


		$this->includeComponentTemplate();
	}
}
?>