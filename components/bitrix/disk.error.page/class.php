<?php

use Bitrix\Main\Loader;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!Loader::includeModule('disk'))
{
	return false;
}
class CDiskErrorPageComponent extends \Bitrix\Disk\Internals\BaseComponent
{
	protected function processActionDefault()
	{
		$httpResponse = Bitrix\Main\Application::getInstance()
			->getContext()
			->getResponse()
		;

		$status = '404 Not Found';
		if (Loader::includeModule('bitrix24'))
		{
			$status = '403 Forbidden';
		}

		$httpResponse->setStatus($status);

		$this->includeComponentTemplate();
	}
}