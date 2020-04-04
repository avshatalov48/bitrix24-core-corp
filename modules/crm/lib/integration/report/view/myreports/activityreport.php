<?php

namespace Bitrix\Crm\Integration\Report\View\MyReports;

use Bitrix\Crm\Activity\CommunicationWidgetPanel;
use Bitrix\Crm\Integration\Report\Dashboard\MyReports\ActivityBoard;
use Bitrix\Crm\Integration\Report\View\WidgetPanel;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT."/components/bitrix/crm.activity/templates/.default/widget.php");

class ActivityReport extends WidgetPanel
{
	const VIEW_KEY = 'widget_activity_report';

	public function getPanelGuid()
	{
		return ActivityBoard::getPanelGuid();
	}

	public function getEntityTypes()
	{
		return [\CCrmOwnerType::ActivityName];
	}

	public function getDefaultRows()
	{
		$options = \CUserOptions::GetOption('crm.widget_panel', $this->getPanelGuid(), []);
		$enableDemo = !isset($options['enableDemoMode']) || $options['enableDemoMode'] === 'Y';

		if ($enableDemo)
		{
			return CommunicationWidgetPanel::getActivityDemoRowData($this->isSuperVisor());
		}
		else
		{
			return CommunicationWidgetPanel::getActivityRowData($this->isSuperVisor());
		}
	}

	public function getPathToDemoData()
	{
		return Application::getDocumentRoot().'/bitrix/components/bitrix/crm.activity/templates/.default/widget';
	}

	public function getDemoTitle()
	{
		return Loc::getMessage('CRM_ACTIVITY_WGT_DEMO_TITLE');

	}

	public function getDemoContent()
	{
		$pathToActivityList = \Bitrix\Main\Config\Option::get('crm', 'path_to_activity_list', '/crm/activity/');
		return Loc::getMessage(
			'CRM_ACTIVITY_WGT_DEMO_CONTENT',
			array(
				'#URL#' => $pathToActivityList,
				'#CLASS_NAME#' => 'crm-widg-white-link'
			)
		);
	}
}