<?php

namespace Bitrix\BiConnector\Settings\Grid\Row\Action\Dashboard;

use Bitrix\BiConnector\Settings\Grid\Row\Action\Settings;

class DeleteAction extends Settings\DeleteAction
{
	public function getControl(array $rawFields): ?array
	{
		$this->default = true;

		if (isset($rawFields['ID']))
		{
			$this->onclick = 'BX.BIConnector.DashboardGrid.deleteRow(' . $rawFields['ID'] . ')';
		}

		return parent::getControl($rawFields);
	}
}
