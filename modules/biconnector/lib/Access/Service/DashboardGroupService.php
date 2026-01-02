<?php

namespace Bitrix\BIConnector\Access\Service;

use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupBindingTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupScopeTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetScopeTable;
use Bitrix\BIConnector\Superset\Scope\ScopeService;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

class DashboardGroupService
{
	/**
	 * @param array $groupInfo {'id' => int|null, 'name' => string}
	 * @param string[] $scopeList
	 * @param array $dashboards {'id' => int, 'scopes' => array}
	 *
	 * @return Result
	 */
	public static function saveGroup(
		array $groupInfo,
		array $scopeList = [],
		array $dashboards = [],
	): Result
	{
		$result = new Result();

		if (!isset($groupInfo['id']))
		{
			$group = SupersetDashboardGroupTable::createObject()
				->setCode(self::generateGroupCode())
				->setType(SupersetDashboardGroupTable::GROUP_TYPE_CUSTOM)
				->setOwnerId((int)CurrentUser::get()->getId())
			;
		}
		else
		{
			$group = SupersetDashboardGroupTable::getById($groupInfo['id'])->fetchObject();
		}

		if (is_null($group))
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_SAVE_GROUP_ERROR_NOT_FOUND')));

			return $result;
		}

		if (
			$group->getType() === SupersetDashboardGroupTable::GROUP_TYPE_CUSTOM
			&& $group->getName() !== $groupInfo['name']
		)
		{
			if (empty($groupInfo['name']))
			{
				$result->addError(new Error(Loc::getMessage('BICONNECTOR_SAVE_GROUP_ERROR_NO_NAME')));

				return $result;
			}

			$group->setName($groupInfo['name']);

			$saveResult = $group->save();
			if (!$saveResult->isSuccess())
			{
				$result->addErrors($saveResult->getErrors());

				return $result;
			}
		}
		$groupId = $group->getId();

		if ($group->getType() === SupersetDashboardGroupTable::GROUP_TYPE_CUSTOM)
		{
			$saveScopeBindingResult = self::saveGroupScopes($groupId, $scopeList);
			if (!$saveScopeBindingResult->isSuccess())
			{
				$result->addErrors($saveScopeBindingResult->getErrors());

				return $result;
			}

			if ($saveScopeBindingResult->getData()['isScopeChanged'])
			{
				$group->setDateModify(new DateTime());
			}
		}

		$dashboardIdList = array_map('intval', array_column($dashboards, 'id'));
		$saveDashboardsBindingResult = self::saveDashboardsBindingToGroup($groupId, $dashboardIdList);
		if (!$saveDashboardsBindingResult->isSuccess())
		{
			$result->addErrors($saveDashboardsBindingResult->getErrors());

			return $result;
		}

		if ($saveDashboardsBindingResult->getData()['isDashboardBindChanged'])
		{
			$group->setDateModify(new DateTime());
		}

		$saveScopeDashboardResult = self::saveDashboardScopeList($dashboards);
		if (!$saveScopeDashboardResult->isSuccess())
		{
			$result->addErrors($saveScopeDashboardResult->getErrors());

			return $result;
		}

		if ($group->isDateModifyChanged())
		{
			$saveResult = $group->save();
			if (!$saveResult->isSuccess())
			{
				$result->addErrors($saveResult->getErrors());

				return $result;
			}
		}

		$result->setData(['id' => $groupId]);

		return $result;
	}

	public static function generateGroupCode(): string
	{
		$code = mb_substr(uniqid('CUSTOM_'), 0, 50);
		$existGroup = SupersetDashboardGroupTable::getList([
			'filter' => ['CODE' => $code],
			'limit' => 1,
		]);

		if ($existGroup->fetch())
		{
			return self::generateGroupCode();
		}

		return $code;
	}

	/**
	 * @param int $groupId
	 * @param string[] $scopeCodeList
	 *
	 * @return Result
	 */
	private static function saveGroupScopes(int $groupId, array $scopeCodeList): Result
	{
		$result = new Result();

		$existCodeList = SupersetDashboardGroupScopeTable::getList([
			'select' => [
				'SCOPE_CODE',
			],
			'filter' => [
				'GROUP_ID' => $groupId,
			],
		])
			?->fetchCollection()
			?->getScopeCodeList()
		;
		$existCodeList ??= [];

		$addScopeList = array_diff($scopeCodeList, $existCodeList);
		if (!empty($addScopeList))
		{
			$rows = array_map(function ($scopeCode) use ($groupId) {
				return [
					'SCOPE_CODE' => $scopeCode,
					'GROUP_ID' => $groupId,
				];
			}, $addScopeList);

			$addResult = SupersetDashboardGroupScopeTable::addMulti($rows);
			if (!$addResult->isSuccess())
			{
				$result->addErrors($addResult->getErrors());

				return $result;
			}
		}

		$deleteScopeList = array_diff($existCodeList, $scopeCodeList);
		if (!empty($deleteScopeList))
		{
			SupersetDashboardGroupScopeTable::deleteByFilter([
				'@SCOPE_CODE' => $deleteScopeList,
				'GROUP_ID' => $groupId,
			]);
		}

		$isScopeChanged = !empty($addScopeList) || !empty($deleteScopeList);
		$result->setData(['isScopeChanged' => $isScopeChanged]);

		return $result;
	}

	/**
	 * @param int $groupId
	 * @param int[] $dashboardIdList
	 *
	 * @return Result
	 */
	private static function saveDashboardsBindingToGroup(int $groupId, array $dashboardIdList): Result
	{
		$result = new Result();

		$existDashboardIdList = SupersetDashboardGroupBindingTable::getList([
			'select' => [
				'DASHBOARD_ID',
			],
			'filter' => [
				'GROUP_ID' => $groupId,
			],
		])
			?->fetchCollection()
			?->getDashboardIdList()
		;
		$existDashboardIdList ??= [];

		$addDashboardBinding = array_diff($dashboardIdList, $existDashboardIdList);
		if (!empty($addDashboardBinding))
		{
			$rows = array_map(function ($dashboardId) use ($groupId) {
				return [
					'DASHBOARD_ID' => $dashboardId,
					'GROUP_ID' => $groupId,
				];
			}, $addDashboardBinding);

			$addResult = SupersetDashboardGroupBindingTable::addMulti($rows);
			if (!$addResult->isSuccess())
			{
				$result->addErrors($addResult->getErrors());

				return $result;
			}
		}

		$deleteDashboardBinding =  array_diff($existDashboardIdList, $dashboardIdList);
		if (!empty($deleteDashboardBinding))
		{
			SupersetDashboardGroupBindingTable::deleteByFilter([
				'@DASHBOARD_ID' => $deleteDashboardBinding,
				'GROUP_ID' => $groupId,
			]);
		}

		$isDashboardBindChanged = !empty($addDashboardBinding) || !empty($deleteDashboardBinding);
		$result->setData(['isDashboardBindChanged' => $isDashboardBindChanged]);

		return $result;
	}

	/**
	 * @param array $dashboards{'id' => int, 'scopes' => array}
	 *
	 * @return Result
	 */
	private static function saveDashboardScopeList(array $dashboards): Result
	{
		$result = new Result();

		if (empty($dashboards))
		{
			return $result;
		}

		$dashboardList = [];
		foreach ($dashboards as $dashboard)
		{
			$dashboardList[$dashboard['id']] = !empty($dashboard['scopes'])
				? array_column($dashboard['scopes'], 'code')
				: [];
		}

		$existDashboards = SupersetDashboardTable::getList([
			'select' => [
				'ID',
				'SCOPE',
			],
			'filter' => [
				'@ID' => array_keys($dashboardList),
			],
		])
			?->fetchCollection()
		;
		$addRows = [];
		$deleteRows = [];

		foreach ($dashboardList as $dashboardId => $scopes)
		{
			$existScopeCollection = $existDashboards
				->getByPrimary($dashboardId)
				->getScope()
			;

			$scopeToAdd = array_diff($scopes, $existScopeCollection->getScopeCodeList());
			if (!empty($scopeToAdd))
			{
				foreach ($scopeToAdd as $scopeCode)
				{
					$addRows[] = [
						'DASHBOARD_ID' => $dashboardId,
						'SCOPE_CODE' => $scopeCode,
					];
				}
			}

			foreach ($existScopeCollection as $existScope)
			{
				if (!in_array($existScope->getScopeCode(), $scopes, true))
				{
					$deleteRows[] = $existScope->getId();
				}
			}
		}

		if (!empty($addRows))
		{
			$addResult = SupersetScopeTable::addMulti($addRows);
			if (!$addResult->isSuccess())
			{
				$result->addErrors($addResult->getErrors());

				return $result;
			}
		}

		if (!empty($deleteRows))
		{
			SupersetScopeTable::deleteByFilter([
				'@ID' => $deleteRows,
			]);
		}

		return $result;
	}

	/**
	 * @param int[] $groupIdList
	 *
	 * @return Result
	 */
	public static function deleteGroupList(array $groupIdList): Result
	{
		$result = new Result();

		if (empty($groupIdList))
		{
			return $result;
		}

		$customDashboardIdList = SupersetDashboardGroupTable::getList([
			'select' => ['ID'],
			'filter' => [
				'TYPE' => SupersetDashboardGroupTable::GROUP_TYPE_CUSTOM,
				'@ID' => $groupIdList,
			],
		])
			->fetchCollection()
			->getIdList()
		;

		if (empty($customDashboardIdList))
		{
			return $result;
		}

		SupersetDashboardGroupTable::deleteByFilter([
			'@ID' => $customDashboardIdList,
		]);

		SupersetDashboardGroupBindingTable::deleteByFilter([
			'@GROUP_ID' => $customDashboardIdList,
		]);

		SupersetDashboardGroupScopeTable::deleteByFilter([
			'@GROUP_ID' => $customDashboardIdList,
		]);

		return $result;
	}

	public static function getAdditionalScopeMap(): array
	{
		$scopes = ScopeService::getInstance()->getScopeList();
		$additionalScopeCodes = [
			ScopeService::BIC_SCOPE_SHOP => [ScopeService::BIC_SCOPE_STORE],
			ScopeService::BIC_SCOPE_BIZPROC => [ScopeService::BIC_SCOPE_WORKFLOW_TEMPLATE],
			ScopeService::BIC_SCOPE_TASKS => [
				ScopeService::BIC_SCOPE_TASKS_EFFICIENCY,
				ScopeService::BIC_SCOPE_TASKS_FLOWS,
				ScopeService::BIC_SCOPE_TASKS_FLOWS_FLOW,
			],
		];
		foreach ($scopes as $scope)
		{
			if (str_starts_with($scope, ScopeService::BIC_SCOPE_AUTOMATED_SOLUTION_PREFIX))
			{
				$additionalScopeCodes[ScopeService::BIC_SCOPE_BIZPROC][] = $scope;
			}
		}

		return $additionalScopeCodes;
	}
}
