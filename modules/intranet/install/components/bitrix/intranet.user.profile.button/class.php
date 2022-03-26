<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

class IntranetUserProfileButton extends \CBitrixComponent
{
	public function executeComponent()
	{
		global $USER, $APPLICATION;

		if (!$USER->IsAuthorized())
		{
			$this->setTemplateName("auth");
			return $this->includeComponentTemplate();
		}
		$this->includeComponentTemplate();
	}
}
