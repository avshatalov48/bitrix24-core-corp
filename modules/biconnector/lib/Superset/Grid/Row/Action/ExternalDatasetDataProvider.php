<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Superset\Grid\Settings\ExternalDatasetSettings;
use Bitrix\Main\Grid\Row\Action\DataProvider;

/**
 * @method ExternalDatasetSettings getSettings()
 */
class ExternalDatasetDataProvider extends DataProvider
{
	public function prepareActions(): array
	{
		if (SupersetInitializer::isSupersetLoading() || SupersetInitializer::isSupersetUnavailable())
		{
			return [];
		}

		return [
			new OpenDatasetAction(),
			new DeleteDatasetAction(),
		];
	}

	public function prepareControls(array $rawFields): array
	{
		$result = [];

		foreach ($this->prepareActions() as $actionsItem)
		{
			if (
				$rawFields['IS_DELETED'] === true &&
				!($actionsItem instanceof DeleteDatasetAction)
			)
			{
				continue;
			}

			$actionConfig = $actionsItem->getControl($rawFields);
			if (isset($actionConfig))
			{
				$result[] = $actionConfig;
			}
		}

		return $result;
	}
}
