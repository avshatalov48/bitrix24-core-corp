<?php

namespace Bitrix\BIConnector\Integration\Superset;

use Bitrix\BIConnector\Access\Install\AccessInstaller;
use Bitrix\BIConnector\Access\Role\RoleTable;
use Bitrix\Main;
use Bitrix\BIConnector\Integration\Superset;
use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\Repository\SupersetUserRepository;
use Bitrix\Rest;

class Agent
{
	private const IS_ACCESS_ROLES_REINSTALLED_OPTION = 'is_access_roles_reinstalled';

	public static function sendRestStatistic(): string
	{
		if (
			Main\Loader::includeModule('rest')
			&& is_callable(['\Bitrix\Rest\UsageStatTable', 'logBISuperset'])
			&& Superset\SupersetInitializer::isSupersetReady()
		)
		{
			$dashboardIterator = Superset\Model\SupersetDashboardTable::getList([
				'select' => [
					'TYPE',
					'REST_APP_CLIENT_ID' => 'APP.CLIENT_ID'
				],
			]);
			while ($dashboard = $dashboardIterator->fetch())
			{
				if ($dashboard['REST_APP_CLIENT_ID'])
				{
					Rest\UsageStatTable::logBISuperset($dashboard['REST_APP_CLIENT_ID'], $dashboard['TYPE']);
				}
			}

			Rest\UsageStatTable::finalize();
		}

		return __CLASS__ . '::' . __FUNCTION__ . '();';
	}

	public static function setDashboardOwners(): string
	{
		$dashboards = SupersetDashboardTable::getList([
			'filter' => ['=TYPE' => SupersetDashboardTable::DASHBOARD_TYPE_CUSTOM]
		])
			->fetchCollection()
		;
		foreach ($dashboards as $dashboard)
		{
			if (!$dashboard->getOwnerId())
			{
				$dashboard->setOwnerId($dashboard->getCreatedById());
				$dashboard->save();
			}
		}

		return '';
	}

	/**
	 * Sets admin as dashboard's owner if the previous owner was fired.
	 *
	 * @param int $previousOwnerId User id of previous owner.
	 *
	 * @return string
	 */
	public static function setDefaultOwnerForDashboards(int $previousOwnerId): string
	{
		$user = (new SupersetUserRepository)->getAdmin();

		$integrator = Integrator::getInstance();
		if ($user && !$user->clientId)
		{
			$superset = new SupersetController($integrator);
			$createResult = $superset->createUser($user->id);
			$user = $createResult->getData()['user'];
			if (!$user)
			{
				return __CLASS__ . '::' . __FUNCTION__ . '(' . $previousOwnerId . ');';
			}
		}

		$dashboards = SupersetDashboardTable::getList(['filter' => ['=OWNER_ID' => $previousOwnerId]])->fetchCollection();
		foreach ($dashboards as $dashboard)
		{
			$dashboard
				->setOwnerId($user->id)
				->save()
			;
			$integrator->setDashboardOwner($dashboard->getExternalId(), $user);
		}

		return '';
	}

	/**
	 * Send request to delete superset instance.
	 *
	 * @return string
	 */
	public static function deleteInstance(): string
	{
		Superset\SupersetInitializer::deleteInstance();

		return '';
	}

	/**
	 * Create default roles using agent after installing the module in a new portal.
	 *
	 * @return string
	 */
	public static function createDefaultRoles(): string
	{
		if (RoleTable::getCount() > 0)
		{
			return '';
		}

		AccessInstaller::install();

		return '';
	}

	public static function reinstallRoles(): string
	{
		$isAccessRolesReinstalled = \Bitrix\Main\Config\Option::get('biconnector', self::IS_ACCESS_ROLES_REINSTALLED_OPTION, 'N');
		if ($isAccessRolesReinstalled === 'Y')
		{
			return '';
		}

		\Bitrix\Main\Config\Option::set('biconnector', self::IS_ACCESS_ROLES_REINSTALLED_OPTION, 'Y');
		AccessInstaller::reinstall();

		return '';
	}

	/**
	 * Set default roles using agent after updating the module in an existed portal.
	 *
	 * @return string
	 */
	public static function installDefaultRoles(): string
	{
		if (RoleTable::getCount() === 0)
		{
			AccessInstaller::install(false);
		}

		return '';
	}
}
