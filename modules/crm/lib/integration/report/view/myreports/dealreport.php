<?php

namespace Bitrix\Crm\Integration\Report\View\MyReports;

use Bitrix\Crm\Integration\Report\Dashboard\MyReports\DealBoard;
use Bitrix\Crm\Integration\Report\View\WidgetPanel;
use Bitrix\Crm\Widget\Layout\DealWidget;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT."/components/bitrix/crm.deal/templates/.default/widget.php");

class DealReport extends WidgetPanel
{
	const VIEW_KEY = 'widget_deal_report';

	public function getPanelGuid()
	{
		return DealBoard::getPanelGuid();
	}

	public function getEntityTypes()
	{
		return [\CCrmOwnerType::DealName];
	}

	public function getDefaultRows()
	{
		$filterExtras = [];
		$currentCategory = DealBoard::getCurrentCategory();
		if($currentCategory > 0)
		{
			$filterExtras['dealCategoryID'] = '?';
		}
		return DealWidget::getDefaultRows([
			'isSupervisor' => $this->isSuperVisor(),
			'filterExtras' => $filterExtras
		]);
	}

	public function getPathToDemoData()
	{
		return Application::getDocumentRoot().'/bitrix/components/bitrix/crm.deal/templates/.default/widget';
	}

	public function getDemoTitle()
	{
		return DealWidget::getDemoTitle();
	}

	public function getDemoContent()
	{
		return DealWidget::getDemoContent();
	}

	public function getDisplayComponentTemplate()
	{
		return 'dealboard';
	}

	public function getDisplayComponentParams()
	{
		$contextData = [];

		$currentCategory = DealBoard::getCurrentCategory();
		if($currentCategory >= 0)
		{
			$contextData['dealCategoryID'] = $currentCategory;
		}

		$result = parent::getDisplayComponentParams();
		$result['CONTEXT_DATA'] = $contextData;
		return $result;
	}
}