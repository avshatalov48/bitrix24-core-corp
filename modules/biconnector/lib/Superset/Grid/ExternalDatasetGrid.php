<?php

namespace Bitrix\BIConnector\Superset\Grid;

use Bitrix\BIConnector\Superset\Grid\Row\Assembler\ExternalDatasetRowAssembler;
use Bitrix\BIConnector\Superset\Grid\Settings\ExternalDatasetSettings;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Filter\Settings;
use Bitrix\Main\Grid\Column\Columns;
use Bitrix\Main\Grid\Grid;
use Bitrix\Main\Grid\Row\Rows;

/**
 * @method ExternalDatasetSettings getSettings()
 */
final class ExternalDatasetGrid extends Grid
{
	public function __construct(\Bitrix\Main\Grid\Settings $settings)
	{
		parent::__construct($settings);
		$this->getSettings()->setOrmFilter($this->getOrmFilter());
	}

	protected function createColumns(): Columns
	{
		return new Columns(
			new \Bitrix\BIConnector\Superset\Grid\Column\Provider\ExternalDatasetDataProvider()
		);
	}

	protected function createRows(): Rows
	{
		$rowAssembler = new ExternalDatasetRowAssembler(
			[
				'ID',
				'TYPE',
				'NAME',
				'SOURCE',
				'DESCRIPTION',
				'DATE_CREATE',
				'DATE_UPDATE',
				'CREATED_BY_ID',
				'UPDATED_BY_ID',
			],
			$this->getSettings()
		);

		return new Rows(
			$rowAssembler,
			new \Bitrix\BIConnector\Superset\Grid\Row\Action\ExternalDatasetDataProvider($this->getSettings())
		);
	}

	protected function createFilter(): ?Filter
	{
		return new Filter(
			$this->getId(),
			new \Bitrix\BiConnector\Superset\Filter\Provider\ExternalDatasetDataProvider(
				new Settings(['ID' => $this->getId()])
			),
		);
	}

	public function getOrmParams(): array
	{
		$ormParams = parent::getOrmParams();
		if (!in_array('ID', $ormParams['select'], true))
		{
			$ormParams['select'][] = 'ID';
		}

		if (!in_array('TYPE', $ormParams['select'], true))
		{
			$ormParams['select'][] = 'TYPE';
		}

		return $ormParams;
	}

}
