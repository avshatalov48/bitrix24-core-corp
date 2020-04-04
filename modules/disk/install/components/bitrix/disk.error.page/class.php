<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!\Bitrix\Main\Loader::includeModule('disk'))
{
	return false;
}
class CDiskErrorPageComponent extends \Bitrix\Disk\Internals\BaseComponent
{
	protected function processActionDefault()
	{
		Bitrix\Main\Application::getInstance()
			->getContext()
			->getResponse()
			->setStatus('404 Not Found')
		;

		$this->includeComponentTemplate();
	}
}