<?php

namespace Bitrix\BiConnector\Settings\Grid;

use Bitrix\BiConnector\Settings\Grid\Column\Provider;
use Bitrix\BiConnector\Settings\Grid\Row\Action\KeysDataProvider;
use Bitrix\BiConnector\Settings\Grid\Row\Assembler\KeysRowAssembler;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Filter\Settings;
use Bitrix\Main\Grid\Column;
use Bitrix\Main\Grid\Grid;
use Bitrix\Main\Grid\Row\Rows;

/**
 * @method KeysSettings getSettings()
 */
class KeysGrid extends Grid
{
	protected function createColumns(): Column\Columns
	{
		return new Column\Columns(
			new Provider\KeysDataProvider($this->getSettings()),
			new Provider\CreationDataProvider(),
		);
	}

	protected function createRows(): Rows
	{
		$rowsAssembler = new KeysRowAssembler([
			'ACTIVE',
			'ACCESS_KEY',
			'BICONNECTOR_KEY_APPLICATION_APP_NAME',
			'LAST_ACTIVITY_DATE',
			'CREATED_BY',
			'DATE_CREATE',
			'TIMESTAMP_X',
			'NAME',
			'URL',
		]);

		if ($this->getSettings()->isCanWrite())
		{
			return new Rows($rowsAssembler, new KeysDataProvider($this->getSettings()));
		}

		return new Rows($rowsAssembler);
	}

	protected function createFilter(): ?Filter
	{
		return new Filter(
			$this->getId(),
			new \Bitrix\BiConnector\Settings\Filter\Provider\KeysDataProvider(new Settings(['ID' => $this->getId()]),
				$this->getSettings()),
		);
	}
}
