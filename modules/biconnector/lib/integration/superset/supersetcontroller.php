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
	 * @return string status of superset startup
	 */
	public function startupSuperset(): string
	{
		return SupersetInitializer::startupSuperset();
	}

	public function createSuperset(): string
	{
		return SupersetInitializer::createSuperset();
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
}