<?php

namespace Bitrix\BIConnector\Superset\Grid\Settings;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\Main\Type\Collection;

final class DashboardSettings extends \Bitrix\Main\Grid\Settings
{
	private bool $isSupersetAvailable;
	private ?array $ormFilter;
	private array $availableGroupIds;

	public function __construct(array $params)
	{
		$this->isSupersetAvailable = $params['IS_SUPERSET_AVAILABLE'] ?? true;

		$availableGroupIds = AccessController::getCurrent()->getAllowedGroupValue(ActionDictionary::ACTION_BIC_DASHBOARD_VIEW) ?? [];
		Collection::normalizeArrayValuesByInt($availableGroupIds);
		$this->availableGroupIds = $availableGroupIds;
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

	public function getAvailableGroupIds(): array
	{
		return $this->availableGroupIds;
	}
}
