<?php

namespace Bitrix\BIConnector\Integration\Superset\Stepper;

use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard;
use Bitrix\Main;
use Bitrix\BIConnector\Integration\Superset\Repository\SupersetUserRepository;
use Bitrix\BIConnector\Integration\Superset\SupersetController;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorResponse;

class DashboardOwner extends Main\Update\Stepper
{
	protected static $moduleId = 'biconnector';
	private const STEPPER_PARAMS = "~dashboard_owner_stepper_params";
	private const STEPPER_IS_FINISH = "~dashboard_owner_stepper_is_finished";

	private const MAX_EXECUTION_TIME = 5;

	private array $params = [];

	public static function isFinished(): bool
	{
		return (Main\Config\Option::get(self::$moduleId, self::STEPPER_IS_FINISH, 'N') === 'Y');
	}

	private static function setFinishStatus(): void
	{
		Main\Config\Option::set(self::$moduleId, self::STEPPER_IS_FINISH, 'Y');
	}

	public function execute(array &$option)
	{
		Main\Loader::includeModule('biconnector');

		if (self::isFinished())
		{
			return self::FINISH_EXECUTION;
		}

		if (SupersetInitializer::getSupersetStatus() !== SupersetInitializer::SUPERSET_STATUS_READY)
		{
			return self::CONTINUE_EXECUTION;
		}

		$adminUserId = $this->getAdminUserId();
		if (!$adminUserId)
		{
			return self::CONTINUE_EXECUTION;
		}

		$user = (new SupersetUserRepository)->getById($adminUserId);
		if ($user && !$user->clientId)
		{
			$this->createUser($adminUserId);
			return self::CONTINUE_EXECUTION;
		}

		$this->initParams();

		$timeStart = Main\Diag\Helper::getCurrentMicrotime();

		$integrator = Integrator::getInstance();

		$customDashboardList = $this->getCustomDashboardList();
		if ($customDashboardList)
		{
			foreach ($customDashboardList as $dashboard)
			{
				$dashboardId = (int)$dashboard['ID'];
				$externalDashboardId = (int)$dashboard['EXTERNAL_ID'];
				$createdById = (int)$dashboard['OWNER_ID'];

				$ownerId = $this->isUserAcceptable($createdById) ? $createdById : $adminUserId;

				$user = (new SupersetUserRepository)->getById($ownerId);
				if ($user && !$user->clientId)
				{
					$this->createUser($ownerId);
					return self::CONTINUE_EXECUTION;
				}

				$setOwnerDashboardResult = $integrator->setDashboardOwner($externalDashboardId, $user);
				if (
					$setOwnerDashboardResult->hasErrors()
					&& $setOwnerDashboardResult->getStatus() !== IntegratorResponse::STATUS_NOT_FOUND
				)
				{
					return self::CONTINUE_EXECUTION;
				}

				$this->updateLocalDashboardIdParams($dashboardId);

				$timeEnd = Main\Diag\Helper::getCurrentMicrotime();
				if ($timeEnd - $timeStart > self::MAX_EXECUTION_TIME)
				{
					break;
				}
			}
		}
		else
		{
			$supersetDashboardList = $this->getDashboardFromSuperset();
			if ($supersetDashboardList)
			{
				$user = (new SupersetUserRepository)->getById($adminUserId);
				foreach ($supersetDashboardList as $supersetDashboardId)
				{
					$setOwnerDashboardResult = $integrator->setDashboardOwner($supersetDashboardId, $user);
					if (
						$setOwnerDashboardResult->hasErrors()
						&& $setOwnerDashboardResult->getStatus() !== IntegratorResponse::STATUS_NOT_FOUND
					)
					{
						return self::CONTINUE_EXECUTION;
					}

					$this->updateSupersetDashboardIdParams($supersetDashboardId);

					$timeEnd = Main\Diag\Helper::getCurrentMicrotime();
					if ($timeEnd - $timeStart > self::MAX_EXECUTION_TIME)
					{
						break;
					}
				}
			}
			else
			{
				self::setFinishStatus();
				return self::FINISH_EXECUTION;
			}
		}

		return self::CONTINUE_EXECUTION;
	}

	private function createUser(int $userId): void
	{
		$superset = new SupersetController(Integrator::getInstance());
		$superset->createUser($userId);
	}

	private function getAdminUserId(): ?int
	{
		$user = Main\UserGroupTable::getList([
			'select' => ['USER_ID'],
			'filter' => [
				'=GROUP_ID' => 1,
				'=DATE_ACTIVE_TO' => null,
				'=USER.ACTIVE' => 'Y',
				'=USER.IS_REAL_USER' => 'Y',
			],
			'order' => ['USER_ID' => 'ASC'],
			'limit' => 1,
		])
			->fetch()
		;

		if ($user)
		{
			return (int)$user['USER_ID'];
		}

		return null;
	}

	private function getCustomDashboardList(): array
	{
		$filter['=TYPE'] = SupersetDashboardTable::DASHBOARD_TYPE_CUSTOM;

		if ($this->params['last_updated_local_dashboard_id' ] !== null)
		{
			$filter['>ID'] = $this->params['last_updated_local_dashboard_id' ];
		}

		return $this->getDashboardList($filter);
	}

	private function getDashboardList(array $filter = []): array
	{
		$parameters = [
			'select' => ['ID', 'EXTERNAL_ID', 'OWNER_ID'],
			'order' => ['ID' => 'ASC'],
			'cache' => ['ttl' => 3600],
			'count_total' => true,
			'filter' => ['=STATUS' => SupersetDashboard::getActiveDashboardStatuses()],
		];

		if ($filter)
		{
			$parameters['filter'] += $filter;
		}

		return SupersetDashboardTable::getList($parameters)->fetchAll();
	}

	private function getDashboardFromSuperset(): array
	{
		$localDashboards = $this->getDashboardList();
		$localDashboardsIds = array_column($localDashboards, 'EXTERNAL_ID');
		$localDashboardsIds = array_map('intval', $localDashboardsIds);

		$integrator = Integrator::getInstance();
		$integratorResult = $integrator->getDashboardList($localDashboardsIds);

		if ($integratorResult->hasErrors())
		{
			return [];
		}

		$dashboardList = $integratorResult->getData();
		if ($dashboardList === null)
		{
			return [];
		}

		$dashboardIds = [];
		foreach ($dashboardList->dashboards as $item)
		{
			$dashboardIds[] = $item->id;
		}

		sort($dashboardIds);

		$result = [];
		foreach ($dashboardIds as $dashboardId)
		{
			if (!in_array($dashboardId, $localDashboardsIds, true))
			{
				if ($this->params['last_updated_superset_dashboard_id' ] !== null)
				{
					$lastSupersetDashboardId = (int)$this->params['last_updated_superset_dashboard_id' ];
					if ($dashboardId <= $lastSupersetDashboardId)
					{
						continue;
					}
				}

				$result[] = $dashboardId;
			}
		}

		return $result;
	}

	private function isUserAcceptable(int $userId): bool
	{
		return (bool)Main\UserTable::getRow([
			'select' => ['ID'],
			'filter' => [
				'=ID' => $userId,
				'=ACTIVE' => 'Y',
				'=IS_REAL_USER' => 'Y',
			],
		]);
	}

	private function initParams(): void
	{
		$params = Main\Config\Option::get(self::$moduleId, self::STEPPER_PARAMS);
		if ($params !== '' && CheckSerializedData($params))
		{
			$params = unserialize($params, ['allowed_classes' => false]);
		}

		$this->params = (is_array($params) ? $params : []);
		if (empty($this->params))
		{
			$this->params = [
				'last_updated_local_dashboard_id' => null,
				'last_updated_superset_dashboard_id' => null,
			];
		}
	}

	/**
	 * @param $dashboardId
	 */
	private function updateLocalDashboardIdParams($dashboardId): void
	{
		$this->params['last_updated_local_dashboard_id'] = $dashboardId;

		Main\Config\Option::set(self::$moduleId, self::STEPPER_PARAMS, serialize($this->params));
	}

	/**
	 * @param $dashboardId
	 */
	private function updateSupersetDashboardIdParams($dashboardId): void
	{
		$this->params['last_updated_superset_dashboard_id'] = $dashboardId;

		Main\Config\Option::set(self::$moduleId, self::STEPPER_PARAMS, serialize($this->params));
	}
}
