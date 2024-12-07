<?php

namespace Bitrix\BIConnector\Access\Superset;

use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard;
use Bitrix\Main;
use Bitrix\BIConnector\Integration\Superset\Repository\SupersetUserRepository;
use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Access\Permission\PermissionDictionary;
use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetUserTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;

final class Synchronizer
{
	private const ROLE_WRITER_NAME = 'writer';
	private const ROLE_READER_NAME = 'reader';
	private const ROLE_EMPTY_NAME = 'empty';

	public function __construct(private readonly int $userId)
	{
	}

	public function sync(): Main\Result
	{
		$syncResult = new Main\Result();

		$user = (new SupersetUserRepository())->getById($this->userId);
		if (!$user || empty($user->clientId))
		{
			$syncResult->addError(new Main\Error("user with id {$this->userId} not found."));
			return $syncResult;
		}

		$accessController = new AccessController($this->userId);

		$viewValues = $accessController->getPermissionValue(ActionDictionary::ACTION_BIC_DASHBOARD_VIEW);
		$editValues = $accessController->getPermissionValue(ActionDictionary::ACTION_BIC_DASHBOARD_EDIT);

		$currentHash = $this->calculateHash($viewValues, $editValues);
		if ($user->permissionHash === $currentHash)
		{
			return $syncResult;
		}

		$dashboardsExternalIdList = [];
		if (is_array($editValues) && count($editValues) === 1 && current($editValues) === PermissionDictionary::VALUE_VARIATION_ALL)
		{
			$dashboardsExternalIdList = self::getDashboardExternalIdList();
		}
		else
		{
			$dashboardsExternalIdList = self::getDashboardExternalIdList(['=OWNER_ID' => $this->userId]);

			if (is_array($editValues))
			{
				$dashboardsExternalIdList = array_merge(
					$dashboardsExternalIdList,
					self::getDashboardExternalIdList(['=ID' => $editValues])
				);

				$dashboardsExternalIdList = array_unique($dashboardsExternalIdList);
			}
		}

		$role = self::ROLE_EMPTY_NAME;
		if ($viewValues)
		{
			$role = self::ROLE_READER_NAME;
		}

		if ($editValues || !empty($dashboardsExternalIdList))
		{
			$role = self::ROLE_WRITER_NAME;
		}

		$integrator = Integrator::getInstance();
		$syncProfileResult = $integrator->syncProfile(
			$user,
			[
				'role' => $role,
				'dashboardIdList' => $dashboardsExternalIdList,
			]
		);
		if ($syncProfileResult->hasErrors())
		{
			$syncResult->addErrors($syncProfileResult->getErrors());
			return $syncResult;
		}

		SupersetUserTable::updatePermissionHash($user->id, $currentHash);

		return $syncResult;
	}

	private static function getDashboardExternalIdList(array $filter = []): array
	{
		$filter += [
			'=STATUS' => SupersetDashboard::getActiveDashboardStatuses(),
			'=TYPE' => SupersetDashboardTable::DASHBOARD_TYPE_CUSTOM,
		];

		$parameters = [
			'select' => ['EXTERNAL_ID'],
			'filter' => $filter,
			'cache' => ['ttl' => 3600],
		];


		$dashboards = SupersetDashboardTable::getList($parameters)->fetchAll();

		return array_map('intval', array_column($dashboards, 'EXTERNAL_ID'));
	}

	private function calculateHash($viewValues, $editValues): string
	{
		$currentViewHash =
			$viewValues
				? md5(is_array($viewValues) ? implode('', $viewValues) : $viewValues)
				: ''
		;

		$currentEditHash =
			$editValues
				? md5(is_array($editValues) ? implode('', $editValues) : $editValues)
				: ''
		;

		return md5(
			$currentViewHash
			. $currentEditHash
			. implode('', self::getDashboardExternalIdList())
			. implode('', self::getDashboardExternalIdList(['=OWNER_ID' => $this->userId]))
		);
	}
}
