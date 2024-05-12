<?php

namespace Bitrix\BIConnector\Integration\Superset;


use Bitrix\BIConnector\Integration\Superset\Model\SupersetUserTable;
use Bitrix\BIConnector\Integration\Superset\Integrator\SupersetIntegrator;
use Bitrix\BIConnector\Integration\Superset\Repository\DashboardRepository;
use Bitrix\BIConnector\Integration\Superset\Repository\SupersetUserRepository;
use Bitrix\Main;

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

	public function createUser(int $userId): Main\Result
	{
		$result = new Main\Result();

		$user = (new SupersetUserRepository)->getById($userId);
		if (!$user)
		{
			$result->addError(new Main\Error("User with id \"{$userId}\" not found"));
			return $result;
		}

		if ($user->clientId)
		{
			$result->addError(new Main\Error('User already exists'));
			return $result;
		}

		$response = $this->integrator->createUser($user);
		if ($response->hasErrors())
		{
			$result->addErrors($response->getErrors());
			return $result;
		}

		$data = $response->getData();

		$addResult = SupersetUserTable::addClientId($user->id, $data['client_id']);
		if ($addResult->isSuccess())
		{
			$user->clientId = $data['client_id'];
			$result->setData([
				'user' => $user,
			]);
		}
		else
		{
			$result->addErrors($addResult->getErrors());
		}

		return $result;
	}

	public function getLoginUrl(): ?string
	{
		$response = $this->integrator->getLoginUrl();
		if ($response->hasErrors())
		{
			return null;
		}

		$data = $response->getData();
		return $data['url'];
	}

	public function isExternalServiceAvailable(): bool
	{
		return $this->integrator->ping();
	}
}