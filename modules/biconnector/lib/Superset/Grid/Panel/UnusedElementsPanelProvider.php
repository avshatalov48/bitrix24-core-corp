<?php

namespace Bitrix\BIConnector\Superset\Grid\Panel;

use Bitrix\BIConnector\Superset\Grid\Panel\Action\UnusedElements\DeleteAction;
use Bitrix\Main\Grid\Panel\Action\DataProvider;

class UnusedElementsPanelProvider extends DataProvider
{
	public function prepareActions(): array
	{
		return [
			new DeleteAction(),
		];
	}
}
