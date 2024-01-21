<?php

namespace Bitrix\BIConnector\Superset\Filter\Provider;

use Bitrix\Main\Filter\DataProvider;
use Bitrix\Main\Filter\Settings;
use Bitrix\Main\Localization\Loc;

class DashboardDataProvider extends DataProvider
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
		return [];
	}

	public function prepareFieldData($fieldID)
	{
		return null;
	}
}