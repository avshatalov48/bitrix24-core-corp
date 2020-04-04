<?php
use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Loc::loadMessages(__FILE__);

class CDiskInterfaceGridComponent extends BaseComponent
{
	protected function processActionDefault()
	{
		$this->includeComponentTemplate();
	}
}