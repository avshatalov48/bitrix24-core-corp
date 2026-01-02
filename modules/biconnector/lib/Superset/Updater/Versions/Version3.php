<?php

namespace Bitrix\BIConnector\Superset\Updater\Versions;

use Bitrix\Main;
use Bitrix\Main\Result;
use Bitrix\BIConnector;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;

/**
 * Updates fields of tracking_source_expenses.
 * Add AD_ID, AD_NAME, GROUP_ID, GROUP_NAME, UTM_MEDIUM, UTM_SOURCE, UTM_CAMPAIGN, UTM_CONTENT.
 */
final class Version3 extends BaseVersion
{
	private const TABLE_NAME = 'tracking_source_expenses';

	public function run(): Result
	{
		$result = new Result();

		if (
			SupersetInitializer::getSupersetStatus() == SupersetInitializer::SUPERSET_STATUS_DOESNT_EXISTS
			|| SupersetInitializer::getSupersetStatus() == SupersetInitializer::SUPERSET_STATUS_DELETED
		)
		{
			return $result;
		}

		if (SupersetInitializer::getSupersetStatus() !== SupersetInitializer::SUPERSET_STATUS_READY)
		{
			$result->addError(new Main\Error('Superset status is not READY'));

			return $result;
		}

		$manager = BIConnector\Manager::getInstance();
		$service = new BIConnector\Services\ApacheSuperset($manager);

		$tableFields = $service->getTableFields(self::TABLE_NAME);
		if (!$tableFields)
		{
			return $result;
		}

		$fields['table_name'] = self::TABLE_NAME;

		foreach ($tableFields as $tableField)
		{
			$fields['columns'][] = [
				'name' => $tableField['ID'],
				'type' => $tableField['TYPE'],
			];
		}

		Integrator::getInstance()->updateDataset(0, $fields);

		return $result;
	}
}
