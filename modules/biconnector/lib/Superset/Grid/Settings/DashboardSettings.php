<?php

namespace Bitrix\BIConnector\Superset\Grid\Settings;

final class DashboardSettings extends \Bitrix\Main\Grid\Settings
{
	private bool $isSupersetAvailable;
	private ?array $ormFilter;

	public function __construct(array $params)
	{
		$this->isSupersetAvailable = $params['IS_SUPERSET_AVAILABLE'] ?? true;
		parent::__construct($params);
	}

	public function isSupersetAvailable(): bool
	{
		return $this->isSupersetAvailable;
	}

	public function setSupersetAvailability(bool $isSupersetAvailable): void
	{
		$this->isSupersetAvailable = $isSupersetAvailable;
	}

	public function setOrmFilter(?array $filter): void
	{
		$this->ormFilter = $filter;
	}

	public function getOrmFilter(): ?array
	{
		return $this->ormFilter;
	}
}
