<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action\UnusedElements;

use Bitrix\Main\Grid\Row\Action\DataProvider;

class UnusedElementsActionProvider extends DataProvider
{
	public function prepareActions(): array
	{
		return [
			new OpenAction(),
			new DeleteAction(),
		];
	}
}
