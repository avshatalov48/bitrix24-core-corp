<?php

namespace Bitrix\Crm\Integration\Report\View\MyReports;

use Bitrix\Crm\Activity\CommunicationWidgetPanel;
use Bitrix\Crm\Integration\Report\Dashboard\MyReports\CompanyBoard;
use Bitrix\Crm\Integration\Report\View\WidgetPanel;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT."/components/bitrix/crm.company/templates/.default/widget.php");

class CompanyReport extends WidgetPanel
{
	const VIEW_KEY = 'widget_company_report';

	public function getPanelGuid()
	{
		return CompanyBoard::getPanelGuid();
	}

	public function getEntityTypes()
	{
		return [\CCrmOwnerType::CompanyName];
	}

	public function getDefaultRows()
	{
		$options = \CUserOptions::GetOption('crm.widget_panel', $this->getPanelGuid(), array());
		$enableDemo = !isset($options['enableDemoMode']) || $options['enableDemoMode'] === 'Y';

		if ($enableDemo)
		{
			return CommunicationWidgetPanel::getDemoRowData(\CCrmOwnerType::Company, $this->isSuperVisor());
		}
		else
		{
			return CommunicationWidgetPanel::getRowData(\CCrmOwnerType::Company, $this->isSuperVisor());
		}
	}

	public function getPathToDemoData()
	{
		return Application::getDocumentRoot().'/bitrix/components/bitrix/crm.company/templates/.default/widget';
	}

	public function getDemoTitle()
	{
		return Loc::getMessage('CRM_COMPANY_WGT_DEMO_TITLE');
	}

	public function getDemoContent()
	{
		return Loc::getMessage(
			'CRM_COMPANY_WGT_DEMO_CONTENT',
			array(
				'#URL#' => \CCrmOwnerType::GetEditUrl(\CCrmOwnerType::Company, 0, false),
				'#CLASS_NAME#' => 'crm-widg-white-link'
			)
		);
	}
}