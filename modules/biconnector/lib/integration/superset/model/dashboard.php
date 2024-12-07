<?php

namespace Bitrix\BIConnector\Integration\Superset\Model;

use Bitrix\BIConnector\Integration\Superset\Integrator\Dto;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorResponse;
use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Superset\Dashboard\EmbeddedFilter;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

final class Dashboard
{
	private ?Dto\DashboardEmbeddedCredentials $embeddedCredentials = null;

	public function __construct(
		private SupersetDashboard $ormObject,
		private ?Dto\Dashboard $dashboardData = null
	)
	{
	}

	public function getId(): int
	{
		return $this->ormObject->getId();
	}

	/**
	 * ID that represents dashboard in superset
	 * @return int|null
	 */
	public function getExternalId(): ?int
	{
		return $this->ormObject->getExternalId();
	}

	public function getTitle(): string
	{
		return $this->dashboardData?->title ?? $this->ormObject->getTitle();
	}

	public function getType(): string
	{
		return $this->ormObject->getType();
	}

	public function getStatus(): string
	{
		return $this->ormObject->getStatus();
	}

	public function isEditable(): bool
	{
		return $this->getType() === SupersetDashboardTable::DASHBOARD_TYPE_CUSTOM;
	}

	public function isSystemDashboard(): bool
	{
		return $this->getType() === SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM;
	}

	public function isMarketDashboard(): bool
	{
		return $this->getType() === SupersetDashboardTable::DASHBOARD_TYPE_MARKET;
	}

	public function isAvailableDashboard(): bool
	{
		return in_array($this->getStatus(), SupersetDashboard::getActiveDashboardStatuses(), true);
	}

	public function getUrl(): string
	{
		return $this->dashboardData?->url ?? '';
	}

	public function getEditUrl(): string
	{
		return $this->dashboardData?->editUrl ?? '';
	}

	public function setEditUrl(string $editUrl): self
	{
		if ($this->dashboardData !== null)
		{
			$this->dashboardData->editUrl  = $editUrl;
		}

		return $this;
	}

	public function getAppId(): string
	{
		return $this->ormObject->getAppId() ?? '';
	}

	public function getField($fieldName): mixed
	{
		return $this->ormObject->get($fieldName) ?? null;
	}

	public function getOrmObject(): SupersetDashboard
	{
		return $this->ormObject;
	}

	public function getEmbeddedCredentials(): ?Dto\DashboardEmbeddedCredentials
	{
		return $this->embeddedCredentials;
	}

	public function getNativeFiltersConfig(): array
	{
		return $this->dashboardData?->nativeFilterConfig ?? [];
	}

	/**
	 * @return array
	 */
	public function toArray(): array
	{
		$embeddedFilterFields = $this->getNativeFilterFields();

		return [
			'ID' => $this->getId(),
			'EXTERNAL_ID' => $this->getExternalId(),
			'STATUS' => $this->getStatus(),
			'URL' => $this->getUrl() ?? '',
			'DETAIL_URL' => $this->ormObject->getDetailUrl()->getUri() ?? '',
			'EDIT_URL' => $this->getEditUrl() ?? '',
			'TITLE' => $this->getTitle() ?? '',
			'SOURCE_ID' => $this->ormObject->getSourceId(),
			'SOURCE_TITLE' => $this->ormObject->getSource()?->getTitle(),
			'APP_ID' => $this->ormObject->getAppId(),
			'TYPE' => $this->getType(),
			'CREATED_BY_ID' => $this->ormObject->get('CREATED_BY_ID'),
			'OWNER_ID' => $this->ormObject->get('OWNER_ID'),
			'DATE_CREATE' => $this->ormObject->get('DATE_CREATE'),
			'DATE_MODIFY' => $this->ormObject->get('DATE_MODIFY'),
			'FILTER_PERIOD' => $embeddedFilterFields['FILTER_PERIOD'],
			'DATE_FILTER_START' => $embeddedFilterFields['DATE_FILTER_START'],
			'DATE_FILTER_END' => $embeddedFilterFields['DATE_FILTER_END'],
			'INCLUDE_LAST_FILTER_DATE' => $embeddedFilterFields['INCLUDE_LAST_FILTER_DATE'],
		];
	}

	public function getNativeFilter(): string
	{
		$filters = [
			new EmbeddedFilter\DateTime($this),
		];

		$formatted = [];
		foreach ($filters as $filter)
		{
			$formatted[] = $filter->getFormatted();
		}

		return implode(',', $formatted);
	}

	public function isSupersetDashboardDataLoaded(): bool
	{
		return $this->dashboardData !== null;
	}

	public function getNativeFilterFields(): array
	{
		$dateFilter = new EmbeddedFilter\DateTime($this);

		return [
			'FILTER_PERIOD' => $dateFilter->getPeriod(),
			'DATE_FILTER_START' => $dateFilter->getDateStart(),
			'DATE_FILTER_END' => $dateFilter->getDateEnd(),
			'INCLUDE_LAST_FILTER_DATE' => $dateFilter->needIncludeLastFilterDate(),
		];
	}

	/**
	 * Update title for dashboard in superset and bitrix
	 *
	 * @param string $title
	 * @return Result
	 */
	public function changeTitle(string $title): Result
	{
		$result = new Result();

		if ($this->ormObject->getType() !== SupersetDashboardTable::DASHBOARD_TYPE_CUSTOM)
		{
			return $result->addError(new Error("Title can be changed only for custom dashboard"));
		}

		$externalId = $this->getOrmObject()->getExternalId();
		if ($externalId <= 0)
		{
			return $result->addError(new Error("Cannot change title without external id"));
		}

		$response = Integrator::getInstance()->updateDashboard($externalId, ['dashboard_title' => $title]);
		if ($response->getStatus() !== IntegratorResponse::STATUS_OK || $response->hasErrors())
		{
			return $result->addError(new Error("Error while changing title in superset"));
		}

		$changedFields = $response->getData();
		if (!isset($changedFields['dashboard_title']))
		{
			return $result->addError(new Error("Dashboard title not change while update fields"));
		}

		if (isset($this->dashboardData))
		{
			$this->dashboardData->title = $changedFields['dashboard_title'];
		}

		$this->ormObject->setTitle($changedFields['dashboard_title']);
		$this->ormObject->save();

		return $result;
	}

	/**
	 * Publish or make draft dashboard.
	 *
	 * @param bool $published
	 * @return Result
	 */
	public function toggleDraft(bool $published): Result
	{
		$result = new Result();

		if ($this->ormObject->getType() !== SupersetDashboardTable::DASHBOARD_TYPE_CUSTOM)
		{
			return $result->addError(new Error("Changing publish property is available only for custom dashboard"));
		}

		$externalId = $this->getOrmObject()->getExternalId();
		if ($externalId <= 0)
		{
			return $result->addError(new Error("Cannot change publish property without external id"));
		}

		$response = Integrator::getInstance()->updateDashboard($externalId, ['published' => $published]);
		if ($response->getStatus() !== IntegratorResponse::STATUS_OK || $response->hasErrors())
		{
			return $result->addError(new Error("Error while changing publish property in superset"));
		}

		$changedFields = $response->getData();
		if (!isset($changedFields['published']))
		{
			return $result->addError(new Error("Published property didn`t change while update fields"));
		}

		$status =
			$published
				? SupersetDashboardTable::DASHBOARD_STATUS_READY
				: SupersetDashboardTable::DASHBOARD_STATUS_DRAFT
		;

		$this->ormObject->setStatus($status);
		$this->ormObject->save();

		return $result;
	}

	public function loadCredentials(): self
	{
		$integrator = Integrator::getInstance();
		$credentialsResponse = $integrator->getDashboardEmbeddedCredentials($this->getExternalId());

		$credentials = $credentialsResponse->getData();
		if (!empty($credentials))
		{
			$this->embeddedCredentials = $credentials;
		}

		return $this;
	}

	public function loadProxyData(): self
	{
		$integrator = Integrator::getInstance();
		$integratorResult = $integrator->getDashboardById($this->getExternalId());
		if ($integratorResult->hasErrors())
		{
			return $this;
		}
		$dashboardData = $integratorResult->getData();
		if (!empty($dashboardData))
		{
			$this->dashboardData = $dashboardData;
		}

		return $this;
	}
}
