<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;

class IntranetInvitationNotification extends CBitrixComponent
{
	public function executeComponent(): void
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return;
		}

		$this->includeComponentTemplate();
	}
}

