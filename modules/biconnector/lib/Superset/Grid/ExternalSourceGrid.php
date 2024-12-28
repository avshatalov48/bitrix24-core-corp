<?php

namespace Bitrix\BIConnector\Superset\Grid;


use Bitrix\BIConnector\Superset\Grid\Row\Assembler\ExternalSourceRowAssembler;
use Bitrix\BIConnector\Superset\Grid\Settings\ExternalSourceSettings;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Filter\Settings;
use Bitrix\Main\Grid\Column\Columns;
use Bitrix\Main\Grid\Grid;
use Bitrix\Main\Grid\Row\Rows;

/**
 * @method ExternalSourceSettings getSettings()
 */
final class ExternalSourceGrid extends Grid
{
	public function __construct(\Bitrix\Main\Grid\Settings $settings)
	{
		parent::__construct($settings);
		$this->getSettings()->setOrmFilter($this->getOrmFilter());
	}

	protected function createColumns(): Columns
	{
		return new Columns(
			new \Bitrix\BIConnector\Superset\Grid\Column\Provider\ExternalSourceDataProvider()
		);
	}

	protected function createRows(): Rows
	{
		$rowAssembler = new ExternalSourceRowAssembler(
			[
				'TITLE',
				'TYPE',
				'ACTIVE',
				'DATE_CREATE',
				'CREATED_BY_ID',
				'DESCRIPTION'
			],
			$this->getSettings()
		);

		return new Rows(
			$rowAssembler,
			new \Bitrix\BIConnector\Superset\Grid\Row\Action\ExternalSourceDataProvider($this->getSettings())
		);
	}

	protected function createFilter(): ?Filter
	{
		return new Filter(
			$this->getId(),
			new \Bitrix\BiConnector\Superset\Filter\Provider\ExternalSourceDataProvider(
				new Settings(['ID' => $this->getId()])
			),
		);
	}

}