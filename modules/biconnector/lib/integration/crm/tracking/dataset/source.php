<?php

namespace Bitrix\BIConnector\Integration\Crm\Tracking\Dataset;

use Bitrix\BIConnector\Configuration\Feature;
use Bitrix\BIConnector\DataSource\BIBuilderDataset;
use Bitrix\BIConnector\DataSource\Field\ArrayStringField;
use Bitrix\BIConnector\DataSource\Field\IntegerField;
use Bitrix\BIConnector\DataSource\Field\StringField;
use Bitrix\BIConnector\DataSourceConnector\Connector\Base;
use Bitrix\BIConnector\DataSourceConnector\FieldCollection;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;

class Source extends BIBuilderDataset
{
	protected const FIELD_NAME_PREFIX = 'TRACKING_SOURCE_FIELD_';

	protected function getResultTableName(): string
	{
		return 'tracking_source';
	}

	public function getSqlTableAlias(): string
	{
		return 'TS';
	}

	protected function getConnectionTableName(): string
	{
		return 'external_tracking_source';
	}

	protected function getTableDescription(): string
	{
		return $this->getMessage('TRACKING_SOURCE_TABLE');
	}

	/**
	 * @return Result
	 */
	protected function onBeforeEvent(): Result
	{
		$result = parent::onBeforeEvent();

		if (!Loader::includeModule('crm'))
		{
			$result->addError(new Error('Module is not installed'));
		}

		if (!Feature::isSourceExpensesEnabled())
		{
			$result->addError(new Error('Feature is not enabled for this dataset'));
		}

		return $result;
	}

	protected function getFields(): array
	{
		return [
			(new IntegerField('ID')),
			(new StringField('NAME')),
			(new ArrayStringField('UTM_SOURCE_LIST')),
		];
	}

	/**
	 * @param string $name
	 * @param FieldCollection $fields
	 * @param array $datasetInfo
	 *
	 * @return Base
	 */
	protected function getConnector(string $name, FieldCollection $fields, array $datasetInfo): Base
	{
		return new SourceConnector($name, $fields, $datasetInfo);
	}
}
