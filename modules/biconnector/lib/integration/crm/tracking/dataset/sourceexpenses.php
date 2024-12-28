<?php

namespace Bitrix\BIConnector\Integration\Crm\Tracking\Dataset;

use Bitrix\BIConnector\Configuration\Feature;
use Bitrix\BIConnector\DataSource\Field\DateField;
use Bitrix\BIConnector\DataSource\Field\DoubleField;
use Bitrix\BIConnector\DataSourceConnector\Connector\Base;
use Bitrix\BIConnector\DataSourceConnector\FieldCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\BIConnector\DataSource\BIBuilderDataset;
use Bitrix\BIConnector\DataSource\Field\IntegerField;
use Bitrix\BIConnector\DataSource\Field\StringField;

class SourceExpenses extends BIBuilderDataset
{
	protected const FIELD_NAME_PREFIX = 'TRACKING_SOURCE_EXPENSES_FIELD_';

	protected function getResultTableName(): string
	{
		return 'tracking_source_expenses';
	}

	public function getSqlTableAlias(): string
	{
		return 'TSE';
	}

	protected function getConnectionTableName(): string
	{
		return 'external_source_expenses';
	}

	protected function getTableDescription(): string
	{
		return $this->getMessage('TRACKING_SOURCE_EXPENSES_TABLE');
	}

	/**
	 * @return Result
	 */
	protected function onBeforeEvent(): Result
	{
		$result = parent::onBeforeEvent();

		if (!Loader::includeModule('crm'))
		{
			$result->addError(new Error('Module `crm` is not installed'));
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
			(new IntegerField('SOURCE_ID')),
			(new DoubleField('EXPENSES')),
			(new StringField('CURRENCY')),
			(new DateField('DATE')),
			(new StringField('CAMPAIGN_NAME')),
			(new StringField('CAMPAIGN_ID')),
			(new IntegerField('CLICKS')),
			(new IntegerField('IMPRESSIONS')),
			(new IntegerField('ACTIONS')),
			(new DoubleField('CPM')),
			(new DoubleField('CPC')),
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
		return new SourceExpensesConnector($name, $fields, $datasetInfo);
	}
}
