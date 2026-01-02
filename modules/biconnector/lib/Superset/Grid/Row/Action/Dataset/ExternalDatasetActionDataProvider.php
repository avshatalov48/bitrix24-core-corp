<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action\Dataset;

use Bitrix\BIConnector\ExternalSource\Type;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Superset\Grid\Settings\ExternalDatasetSettings;
use Bitrix\Main\Grid\Row\Action\DataProvider;

/**
 * @method ExternalDatasetSettings getSettings()
 */
class ExternalDatasetActionDataProvider extends DataProvider
{
	public function prepareActions(): array
	{
		if (SupersetInitializer::isSupersetLoading() || SupersetInitializer::isSupersetUnavailable())
		{
			return [];
		}

		return [
			new OpenDatasetAction(),
			new CreateChartAction(),
			new ExportCsvDatasetAction(),
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

			if (
				$rawFields['TYPE'] !== Type::Csv->value &&
				$actionsItem instanceof ExportCsvDatasetAction
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
