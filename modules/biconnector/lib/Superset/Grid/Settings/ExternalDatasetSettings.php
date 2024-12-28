<?php

namespace Bitrix\BIConnector\Superset\Grid\Settings;

final class ExternalDatasetSettings extends \Bitrix\Main\Grid\Settings
{
	private ?array $ormFilter;

	public function setOrmFilter(?array $filter): void
	{
		$this->ormFilter = $filter;
	}

	public function getOrmFilter(): ?array
	{
		return $this->ormFilter;
	}

}
