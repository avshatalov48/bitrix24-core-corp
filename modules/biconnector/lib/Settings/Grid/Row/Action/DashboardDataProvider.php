<?php

namespace Bitrix\BiConnector\Settings\Grid\Row\Action;

use Bitrix\BiConnector\Settings\Grid\DashboardSettings;
use Bitrix\BiConnector\Settings\Grid\Row\Action\Dashboard\DeleteAction;
use Bitrix\BiConnector\Settings\Grid\Row\Action\Settings\EditAction;
use Bitrix\BiConnector\Settings\Grid\Row\Action\Settings\OpenAction;
use Bitrix\Main\Grid\Row\Action\DataProvider;

/**
 * @method DashboardSettings getSettings()
 */
class DashboardDataProvider extends DataProvider
{
	public function __construct(?DashboardSettings $settings = null)
	{
		parent::__construct($settings);
	}

	public function prepareActions(): array
	{
		$result = [];

		if ($this->getSettings()->isCanRead())
		{
			$result[] = new OpenAction($this->getSettings()->getViewUrl(), 2000);
		}

		if ($this->getSettings()->isCanWrite())
		{
			$result[] = new EditAction($this->getSettings()->getEditUrl(), 650, 'biconnector:create-dashboard');
			$result[] = new DeleteAction();
		}

		return $result;
	}
}
