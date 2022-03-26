<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if(!Main\Loader::includeModule('crm'))
{
	ShowError('Module "crm" is not installed');
	return;
}

Loc::loadMessages(__FILE__);

class CrmStoreDocumentTimeline extends CBitrixComponent
{
	public function executeComponent()
	{
		$this->arResult['ACTIVITY_EDITOR_PARAMS'] = $this->arParams['ACTIVITY_EDITOR_PARAMS'];
		$this->arResult['TIMELINE_PARAMS'] = $this->arParams['TIMELINE_PARAMS'];
		$this->includeComponentTemplate();
	}
}
