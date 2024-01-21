<?php

namespace Bitrix\BiConnector\Settings\Grid\Column\Provider;

use Bitrix\BiConnector\Settings\Grid\KeysSettings;
use Bitrix\Main\Grid\Column\DataProvider;
use Bitrix\Main\Grid;
use Bitrix\Main\Localization\Loc;

/**
 * @method KeysSettings getSettings()
 */
class KeysDataProvider extends DataProvider
{
	public function prepareColumns(): array
	{
		$result = [
			$this->getActiveColumn(),
			$this->getAccessKeyColumn(),
			$this->getApplicationColumn(),
		];

		if ($this->getSettings()->isWithConnections())
		{
			$result[] = $this->getConnectionColumn();
		}

		$result[] = $this->getLastActivityColumn();

		return $result;
	}

	protected function getActiveColumn(): Grid\Column\Column
	{
		return $this->createColumn('ACTIVE')
			->setType(Grid\Column\Type::HTML)
			->setDefault(true)
			->setName(Loc::getMessage('SETTINGS_GRID_COLUMN_PROVIDER_KEYS_ACTIVE'))
			->setEditable(false);
	}

	protected function getAccessKeyColumn(): Grid\Column\Column
	{
		return $this->createColumn('ACCESS_KEY')
			->setType(Grid\Column\Type::HTML)
			->setDefault(true)
			->setName(Loc::getMessage('SETTINGS_GRID_COLUMN_PROVIDER_KEYS_KEY'))
			->setEditable(false);
	}

	protected function getApplicationColumn(): Grid\Column\Column
	{
		return $this->createColumn('BICONNECTOR_KEY_APPLICATION_APP_NAME')
			->setType(Grid\Column\Type::TEXT)
			->setDefault(true)
			->setName(Loc::getMessage('SETTINGS_GRID_COLUMN_PROVIDER_KEYS_APPLICATION'))
			->setEditable(false);
	}

	protected function getConnectionColumn(): Grid\Column\Column
	{
		return $this->createColumn('CONNECTION')
			->setType(Grid\Column\Type::TEXT)
			->setDefault(false)
			->setName(Loc::getMessage('SETTINGS_GRID_COLUMN_PROVIDER_KEYS_CONNECTION'))
			->setEditable(false);
	}

	protected function getLastActivityColumn(): Grid\Column\Column
	{
		return $this->createColumn('LAST_ACTIVITY_DATE')
			->setType(Grid\Column\Type::DATE)
			->setDefault(true)
			->setName(Loc::getMessage('SETTINGS_GRID_COLUMN_PROVIDER_KEYS_LAST_ACTIVITY'))
			->setEditable(false)
			->setSort('LAST_ACTIVITY_DATE');
	}
}
