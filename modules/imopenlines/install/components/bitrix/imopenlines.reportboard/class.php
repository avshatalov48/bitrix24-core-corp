<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class CImOpenLinesReportBoardComponent extends \CBitrixComponent
{
	protected function checkAccess()
	{
		$userPermissions = \Bitrix\ImOpenlines\Security\Permissions::createWithCurrentUser();
		if(!$userPermissions->canViewStatistics())
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_ACCESS_DENIED'));
			return false;
		}

		return true;
	}

	public function executeComponent()
	{
		$this->includeComponentLang('class.php');
		if (!$this->checkAccess())
			return false;

		$this->includeComponentTemplate();
	}
}