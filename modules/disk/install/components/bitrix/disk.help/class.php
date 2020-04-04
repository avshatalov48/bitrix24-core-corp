<?php
use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Loc::loadMessages(__FILE__);

class CDiskHelpComponent extends BaseComponent
{
	protected function processActionDefault()
	{
		$this->application->setTitle(Loc::getMessage('DISK_HELP_PAGE_TITLE'));
		$this->includeComponentTemplate();
	}
}