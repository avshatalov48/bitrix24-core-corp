<?php

namespace Bitrix\Crm\Integration\Report\View\MyReports;

use Bitrix\Crm\Activity\CommunicationWidgetPanel;
use Bitrix\Crm\Integration\Report\Dashboard\MyReports\ContactBoard;
use Bitrix\Crm\Integration\Report\View\WidgetPanel;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT."/components/bitrix/crm.contact/templates/.default/widget.php");

class ContactReport extends WidgetPanel
{
	const VIEW_KEY = 'widget_contact_report';

	public function getPanelGuid()
	{
		return ContactBoard::getPanelGuid();
	}

	public function getEntityTypes()
	{
		return [\CCrmOwnerType::ContactName];
	}

	public function getDefaultRows()
	{
		$options = \CUserOptions::GetOption('crm.widget_panel', $this->getPanelGuid(), []);
		$enableDemo = !isset($options['enableDemoMode']) || $options['enableDemoMode'] === 'Y';

		if ($enableDemo)
		{
			return CommunicationWidgetPanel::getDemoRowData(\CCrmOwnerType::Contact, $this->isSuperVisor());
		}
		else
		{
			return CommunicationWidgetPanel::getRowData(\CCrmOwnerType::Contact, $this->isSuperVisor());
		}
	}

	public function getPathToDemoData()
	{
		return Application::getDocumentRoot().'/bitrix/components/bitrix/crm.contact/templates/.default/widget';
	}

	public function getDemoTitle()
	{
		return Loc::getMessage('CRM_CONTACT_WGT_DEMO_TITLE');
	}

	public function getDemoContent()
	{
		return Loc::getMessage(
			'CRM_CONTACT_WGT_DEMO_CONTENT',
			array(
				'#URL#' => \CCrmOwnerType::GetEditUrl(\CCrmOwnerType::Contact, 0, false),
				'#CLASS_NAME#' => 'crm-widg-white-link'
			)
		);
	}
}