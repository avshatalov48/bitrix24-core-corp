<?php

namespace Bitrix\BiConnector\Settings\Filter\Provider;

use Bitrix\BiConnector\Settings\Grid\Column\Provider\CreationDataProvider;
use Bitrix\BiConnector\Settings\Grid\KeysSettings;
use Bitrix\Main\Filter\DataProvider;
use Bitrix\Main\Filter\Settings;
use Bitrix\Main\Grid\Column\Column;
use Bitrix\BiConnector\Settings\Grid\Column\Provider;

class KeysDataProvider extends DataProvider
{
	public function __construct(protected Settings $filterSettings, protected KeysSettings $gridSettings)
	{
	}

	public function getSettings(): Settings
	{
		return $this->filterSettings;
	}

	public function getGridSettings(): KeysSettings
	{
		return $this->gridSettings;
	}

	/**
	 * @return Column[]
	 */
	public function getWhiteList(): array
	{
		$creationColumnDataProvider = new CreationDataProvider();
		$dashboardColumnsDataProvider = new Provider\KeysDataProvider($this->getGridSettings());

		return array_merge($creationColumnDataProvider->prepareColumns(),
			$dashboardColumnsDataProvider->prepareColumns());
	}

	public function prepareFields(): array
	{
		$result = [];
		$whiteList = $this->getWhiteList();

		foreach ($whiteList as $column)
		{
			$result[] = $this->createField(
				$column->getId(),
				[
					'name' => $column->getName(),
					'type' => $column->getType(),
					'default' => $column->isDefault(),
				]
			);
		}

		return $result;
	}

	public function prepareFieldData($fieldID): ?array
	{
		return null;
	}
}
