<?php

namespace Bitrix\BIConnector\Integration\Superset;

use Bitrix\BIConnector\Access\Install\AccessInstaller;
use Bitrix\BIConnector\Access\Role\RoleTable;
use Bitrix\BIConnector\Access\Update\DashboardGroupRights\Converter;
use Bitrix\BIConnector\Configuration\Feature;
use Bitrix\BIConnector\Integration\Superset;
use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\Repository\SupersetUserRepository;
use Bitrix\BIConnector\Superset\SystemDashboardManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\LanguageTable;

class Agent
{
	private const IS_ACCESS_ROLES_REINSTALLED_OPTION = 'is_access_roles_reinstalled';

	public static function setDashboardOwners(): string
	{
		$dashboards = SupersetDashboardTable::getList([
			'filter' => ['=TYPE' => SupersetDashboardTable::DASHBOARD_TYPE_CUSTOM],
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
		$user = (new SupersetUserRepository())->getAdmin();

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
		Option::set('biconnector', Feature::CHECK_PERMISSION_BY_GROUP_OPTION, 'Y');
		SystemDashboardManager::actualizeSystemDashboards();

		return '';
	}

	public static function reinstallRoles(): string
	{
		$isAccessRolesReinstalled = Option::get('biconnector', self::IS_ACCESS_ROLES_REINSTALLED_OPTION, 'N');
		if ($isAccessRolesReinstalled === 'Y')
		{
			return '';
		}

		Option::set('biconnector', self::IS_ACCESS_ROLES_REINSTALLED_OPTION, 'Y');
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

	/**
	 * Convert from separate dashboard rights to group dashboard rights.
	 *
	 * @return string
	 */
	public static function convertToGroupDashboardRights(): string
	{
		if (SupersetInitializer::isSupersetExist() && !Feature::isCheckPermissionsByGroup())
		{
			Converter::updateToGroup(true);
		}

		return '';
	}

	/**
	 * Restore default values for group names.
	 *
	 * @return string
	 */
	public static function restoreSystemDashboardGroupNames(): string
	{
		if (Loader::includeModule('bitrix24'))
		{
			$defaultLanguage = \CBitrix24::getLicensePrefix();
		}
		else
		{
			$defaultLanguage = LanguageTable::getList([
				'select' => ['ID'],
				'filter' => ['=ACTIVE' => 'Y', '=DEF' => 'Y'],
			])
				->fetch()['ID'] ?? null
			;
		}

		$groupCollection = SupersetDashboardGroupTable::getList([
			'filter' => ['=TYPE' =>  SupersetDashboardGroupTable::GROUP_TYPE_SYSTEM],
		])
			->fetchCollection()
		;

		foreach ($groupCollection as $group)
		{
			$group->setName(AccessInstaller::getDefaultGroupName($group->getCode(), $defaultLanguage));
		}

		$groupCollection->save();

		return '';
	}

	public static function actualizeSystemDashboards(): string
	{
		if (SupersetInitializer::getSupersetStatus() !== SupersetInitializer::SUPERSET_STATUS_DELETED)
		{
			SystemDashboardManager::actualizeSystemDashboards();
		}

		return __CLASS__ . '::' . __FUNCTION__ . '();';
	}
}
