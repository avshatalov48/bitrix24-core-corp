<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;

final class TimemanReportStatusComponent extends CBitrixComponent
{
	/**
	 * @return void
	 * @throws LoaderException
	 */
	public function executeComponent(): void
	{
		if (!Loader::includeModule('timeman'))
		{
			return;
		}

		$workReportUser = new \CUserReportFull;

		$this->arResult['reportData'] = $workReportUser->getReportData();

		$this->includeComponentTemplate();
	}
}
