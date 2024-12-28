<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\BIConnector\Superset\Grid\Settings\ExternalSourceSettings;
use Bitrix\Main\Grid\Row\Action\DataProvider;

/**
 * @method ExternalSourceSettings getSettings()
 */
class ExternalSourceDataProvider extends DataProvider
{
	public function prepareActions(): array
	{
		return [
			new EditSourceAction(),
			new ActivateSourceAction(),
			new DeactivateSourceAction(),
			new DeleteSourceAction(),
		];
	}

}
