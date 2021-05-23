<?php

use Bitrix\Crm\Integration\Report\Dashboard\MyReports\DealBoard;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class CrmReportContentWidgetPanelAjax extends \Bitrix\Main\Engine\Controller
{
	public function __construct(\Bitrix\Main\Request $request = null)
	{
		parent::__construct($request);
		\Bitrix\Main\Loader::includeModule('crm');
	}

	public function setDealCategoryIdAction($categoryId)
	{
		DealBoard::setCurrentCategory($categoryId);
		return true;
	}
}