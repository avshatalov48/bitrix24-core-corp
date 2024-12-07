<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Superset\Grid\Settings\DashboardSettings;
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
		return [
			new OpenAction(),
			new EditAction(),
			new CopyAction(),
			new DeleteAction(),
			new PublishAction(),
			new SetDraftAction(),
			new OpenSettingsAction(),
			new ExportAction(),
			new AddToTopMenuAction(),
			new DeleteFromTopMenuAction(),
		];
	}

	public function prepareControls(array $rawFields): array
	{
		$result = [];

		$settings = $this->getSettings();
		if (
			($settings !== null && !$settings->isSupersetAvailable())
			|| SupersetInitializer::isSupersetLoading()
			|| $rawFields["STATUS"] === SupersetDashboardTable::DASHBOARD_STATUS_LOAD
		)
		{
			return [];
		}

		foreach ($this->prepareActions() as $actionsItem)
		{
			if (
				$rawFields['EDIT_URL'] === '' &&
				!($actionsItem instanceof DeleteAction)
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
