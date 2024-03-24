<?php

namespace Bitrix\BIConnector\Integration\Superset;


use Bitrix\BIConnector\Integration\Superset\Integrator\SupersetIntegrator;
use Bitrix\BIConnector\Integration\Superset\Repository\DashboardRepository;

final class SupersetController
{
	private DashboardRepository $dashboardRepository;

	public function __construct(private SupersetIntegrator $integrator)
	{
		$this->initRepositories();
	}

	private function initRepositories(): void
	{
		$this->dashboardRepository = new DashboardRepository($this->integrator);
	}

	public function getDashboardRepository(): DashboardRepository
	{
		return $this->dashboardRepository;
	}

	/**
	 * Calls on every BI-constructor page hit and call superset if it's need to up
	 *
	 * @return void
	 */
	public function initSuperset(): void
	{
		if (SupersetInitializer::isSupersetActive())
		{
			return;
		}

		SupersetInitializer::createSuperset();
	}

	public function isSupersetEnabled(): bool
	{
		return SupersetInitializer::isSupersetActive();
	}

	public function getUserCredentials(): ?Integrator\Dto\UserCredentials
	{
		$response = $this->integrator->getSupersetCommonUserCredentials();
		if ($response->hasErrors())
		{
			return null;
		}

		return $response->getData();
	}

	public function isExternalServiceAvailable(): bool
	{
		return $this->integrator->ping();
	}
}