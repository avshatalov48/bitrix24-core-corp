<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

class CrmOrderImportInstagramObserverAjaxController extends \Bitrix\Main\Engine\Controller
{
	public function markNotificationReadAction()
	{
		if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			\Bitrix\Crm\Order\Import\Instagram::clearNewMediaOption();
		}
	}

	public function checkMediaAction()
	{
		$success = false;

		if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			$success = \Bitrix\Crm\Order\Import\Instagram::checkNewMedia();
		}

		return $success;
	}
}