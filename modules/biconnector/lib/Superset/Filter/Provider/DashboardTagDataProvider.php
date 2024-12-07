<?php

namespace Bitrix\BIConnector\Superset\Filter\Provider;

use Bitrix\Main\Filter\DataProvider;
use Bitrix\Main\Filter\Settings;
use Bitrix\Main\Localization\Loc;

class DashboardTagDataProvider extends DataProvider
{
	public function __construct(protected Settings $settings)
	{
	}

	public function getSettings(): Settings
	{
		return $this->settings;
	}

	public function prepareFields()
	{
		return [
			'TITLE' => $this->createField('TITLE', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_TAG_GRID_FILTER_TITLE_TITLE'),
				'default' => true,
				'type' => 'string',
				'partial' => true,
			]),
			'DASHBOARD_COUNT' => $this->createField('DASHBOARD_COUNT', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_TAG_GRID_FILTER_TITLE_DASHBOARD_COUNT'),
				'default' => false,
				'type' => 'number',
				'partial' => true,
			]),
		];
	}

	public function prepareFieldData($fieldID)
	{
		return null;
	}

	public function prepareFilterValue(array $rawFilterValue): array
	{
		$rawFilterValue = parent::prepareFilterValue($rawFilterValue);

		if (!empty($rawFilterValue['FIND']))
		{
			if (!empty($rawFilterValue['TITLE']))
			{
				$rawFilterValue['TITLE'] = [
					$rawFilterValue['TITLE'],
					"%{$rawFilterValue['FIND']}%",
				];
			}
			else
			{
				$rawFilterValue['TITLE'] = "%{$rawFilterValue['FIND']}%";
			}
		}

		return $rawFilterValue;
	}
}