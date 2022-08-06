<?php

use Bitrix\Crm\Agent\Duplicate\Background;
use Bitrix\Crm\Agent\Duplicate\Volatile\IndexRebuild;
use Bitrix\Crm\Integrity\DuplicateIndexType;
use Bitrix\Crm\Integrity\DuplicateVolatileCriterion;
use Bitrix\Crm\Integrity\Volatile\Type\State;
use Bitrix\Crm\Integrity\Volatile\TypeInfo;
use Bitrix\Main\Type\DateTime;

class CCrmDedupeWizardComponentHelper
{
	protected function __construct()
	{
	}

	public static function getInstance(): CCrmDedupeWizardComponentHelper
	{
		static $instance = null;

		if ($instance === null)
		{
			$instance = new static();
		}

		return $instance;
	}

	protected function getTypeIdsByNames(array $types): array
	{
		$result = [];

		foreach ($types as $typeName)
		{
			$result[] = DuplicateIndexType::resolveID($typeName);
		}

		return $result;
	}

	protected function getTypeNamesByIds(array $typeIds): array
	{
		$result = [];

		foreach ($typeIds as $typeId)
		{
			$result[] = DuplicateIndexType::resolveName($typeId);
		}

		return $result;
	}

	protected function getVolatileTypeIds(int $entityTypeId, array $typeIds = []): array
	{
		$volatileTypeIdsByEntityId = [];
		$ids = TypeInfo::getInstance()->getIdsByEntityTypes([$entityTypeId]);
		if (isset($ids[$entityTypeId]))
		{
			$volatileTypeIdsByEntityId = $ids[$entityTypeId];
		}

		return empty($typeIds) ? $volatileTypeIdsByEntityId : array_intersect($typeIds, $volatileTypeIdsByEntityId);
	}

	protected function getNotReadyVolatileTypeIds(array $typeIds): array
	{
		$result = [];

		$typeInfo = TypeInfo::getInstance();
		foreach ($typeIds as $typeId)
		{
			$info = $typeInfo->getById($typeId);
			if (
				isset($info['STATE_ID'])
				&& (
					$info['STATE_ID'] === State::STATE_ASSIGNED
					|| $info['STATE_ID'] === State::STATE_INDEX
				)
			)
			{
				$result[] = $typeId;
			}
		}

		return $result;
	}

	protected function getVolatileIndexRebuildAgentState(int $volatileTypeId): array
	{
		$agent = IndexRebuild::getInstance($volatileTypeId);
		$state = ['IS_ACTIVE' => $agent->isActive() ? 'Y' : 'N'];
		$state += $agent->state()->getData();
		$state['STATUS'] = $agent->getStatusCode($state['STATUS']);
		$state['NEXT_STATUS'] = $agent->getStatusCode($state['NEXT_STATUS']);
		$userId = (int)($state['INITIAL_PARAMS']['USER_ID'] ?? 0);
		$userTimeZoneOffset = $userId > 0 ? CTimeZone::GetOffset($userId) : 0;
		$timeStampWithUserOffset = $state['TIMESTAMP'] - $userTimeZoneOffset;
		$state['DATETIME'] = DateTime::createFromTimestamp($timeStampWithUserOffset)->toString();

		return $state;
	}

	protected function getVolatileTypeAgentStateMap(array $volatileTypeIds = [], bool $refresh = false): array
	{
		static $volatileTypeAgentStateMap = null;

		$allVolatileTypeIds = DuplicateVolatileCriterion::getAllSupportedDedupeTypes();
		if ($volatileTypeAgentStateMap === null || $refresh)
		{
			$progressStatusNames = [
				'STATUS_PENDING_CONTINUE',
				'STATUS_RUNNING',
				'STATUS_PENDING_START',
			];
			$progressNextStatusNames = [
				'STATUS_PENDING_CONTINUE',
				'STATUS_PENDING_START',
			];
			foreach ($allVolatileTypeIds as $volatileTypeId)
			{
				$agentState = $this->getVolatileIndexRebuildAgentState($volatileTypeId);
				$agentState['IS_IN_PROGRESS'] =
					(
						in_array($agentState['STATUS'], $progressStatusNames, true)
						|| in_array($agentState['NEXT_STATUS'], $progressNextStatusNames, true)
					)
						? 'Y'
						: 'N'
				;
				$volatileTypeAgentStateMap[$volatileTypeId] = $agentState;
			}
			unset($progressStatusNames);
		}

		$result = [];

		if (empty($volatileTypeIds))
		{
			$volatileTypeIds = $allVolatileTypeIds;
		}

		foreach ($volatileTypeIds as $volatileTypeId)
		{
			if (isset($volatileTypeAgentStateMap[$volatileTypeId]))
			{
				$result[$volatileTypeId] = $volatileTypeAgentStateMap[$volatileTypeId];
			}
		}

		return $result;
	}

	protected function startVolatileTypePrepareAgent(int $userId, int $volatileTypeId, array $typeMap)
	{
		$agent = IndexRebuild::getInstance($volatileTypeId);
		if (is_object($agent))
		{
			$agent->start(['USER_ID' => $userId]);
			$progressData = $agent->state()->getData();
			$finalActionsInfo = $progressData['FINAL_ACTIONS'] ?? [];
			$finalActionsInfo['USER_TYPE_MAP'] = $finalActionsInfo['USER_TYPE_MAP'] ?? [];
			$finalActionsInfo['USER_TYPE_MAP'][$userId] = $typeMap;
			$agent->setFinalActionsInfo($finalActionsInfo);
		}
	}

	protected function clearVolatileTypePrepareAgentUserContext(int $userId, int $volatileTypeId)
	{
		$agent = IndexRebuild::getInstance($volatileTypeId);
		if (is_object($agent))
		{
			$progressData = $agent->state()->getData();
			$finalActionsInfo = $progressData['FINAL_ACTIONS'] ?? [];
			$finalActionsInfo['USER_TYPE_MAP'] = $finalActionsInfo['USER_TYPE_MAP'] ?? [];
			if (isset($finalActionsInfo['USER_TYPE_MAP'][$userId]))
			{
				unset($finalActionsInfo['USER_TYPE_MAP'][$userId]);
				$agent->setFinalActionsInfo($finalActionsInfo);
			}
		}
	}

	/**
	 * @param int $userId
	 * @param string $entityTypeName
	 * @param string $agentName
	 * @return Background\IndexRebuild|Background\Merge|null
	 */
	public function getAgent(int $userId, string $entityTypeName, string $agentName)
	{
		/** @var Background\IndexRebuild|Background\Merge $agentClassName */
		$agentClassName = Background\Helper::getInstance()->getAgentClassName($entityTypeName, $agentName);

		return $agentClassName::getInstance($userId);
	}

	public function getIndexAgent(int $userId, string $entityTypeName): Background\IndexRebuild
	{
		return $this->getAgent($userId, $entityTypeName, 'IndexRebuild');
	}

	public function getMergeAgent(int $userId, string $entityTypeName): Background\Merge
	{
		return $this->getAgent($userId, $entityTypeName, 'Merge');
	}

	protected function startIndexAgent(string $entityTypeName, int $userId, array $types, string $scope)
	{
		/** @var Background\IndexRebuild $agentClassName */
		$agentClassName = Background\Helper::getInstance()->getAgentClassName($entityTypeName, 'IndexRebuild');
		$agent = $agentClassName::getInstance($userId);
		$agent->start($types, $scope);
	}

	protected function getIndexAgentState(int $userId, string $entityTypeName): array
	{
		return Background\Helper::getInstance()->getAgentState(
			$userId,
			$entityTypeName,
			'IndexRebuild'
		);
	}

	public function getMergeAgentState(int $userId, string $entityTypeName): array
	{
		return Background\Helper::getInstance()->getAgentState($userId, $entityTypeName, 'Merge');
	}

	public function getDuplicateIndexState(int $userId, $entityTypeName, array $types = [], string $scope = ''): array
	{
		$ajaxResult = [];
		$isHalfPercentageMode = false;
		$entityTypeId = CCrmOwnerType::ResolveID($entityTypeName);
		$selectedVolatileTypeIds = [];
		if (empty($types))
		{
			$tryStart = false;
			$volatileTypeIds = $this->getVolatileTypeIds($entityTypeId);

			$volatileTypeAgentStateMap = $this->getVolatileTypeAgentStateMap($volatileTypeIds, true);

			$typesMap = [];
			$selectedVolatileTypesMap = [];
			$agentCount = 0;
			foreach ($volatileTypeAgentStateMap as $agentState)
			{
				if (
					isset($agentState['IS_ACTIVE'])
					&& $agentState['IS_ACTIVE'] === 'Y'
					&& isset($agentState['IS_IN_PROGRESS'])
					&& $agentState['IS_IN_PROGRESS'] === 'Y'
					&& isset($agentState['FINAL_ACTIONS']['USER_TYPE_MAP'][$userId])
				)
				{
					$info = $agentState['FINAL_ACTIONS']['USER_TYPE_MAP'][$userId];
					if (is_array($info['types']))
					{
						foreach ($info['types'] as $typeName)
						{
							$typeId = DuplicateIndexType::resolveID($typeName);
							if ($typeId !== DuplicateIndexType::UNDEFINED)
							{
								$typesMap[$typeId] = true;
							}
						}
					}
					if (is_array($info['notReadyVolatileTypes']))
					{
						foreach ($info['notReadyVolatileTypes'] as $typeName)
						{
							$typeId = DuplicateIndexType::resolveID($typeName);
							if ($typeId !== DuplicateIndexType::UNDEFINED)
							{
								$selectedVolatileTypesMap[$typeId] = true;
							}
						}
					}
					$agentCount++;
				}
			}
			if ($agentCount > 0)
			{
				$types = array_keys($typesMap);
				$selectedVolatileTypeIds = array_intersect(array_keys($selectedVolatileTypesMap), $types);
				unset($typesMap, $selectedVolatileTypesMap);
			}
		}
		else
		{
			$tryStart = true;
			$selectedVolatileTypeIds = $this->getVolatileTypeIds($entityTypeId, $this->getTypeIdsByNames($types));
		}
		$notReadySelectedVolatileTypeIds = $this->getNotReadyVolatileTypeIds($selectedVolatileTypeIds);
		$notReadyTypeCount = count($notReadySelectedVolatileTypeIds);
		$isNeedRefreshAgentStateMap = false;
		if ($notReadyTypeCount > 0)
		{
			$isHalfPercentageMode = true;
			$volatileTypeAgentStateMap = $this->getVolatileTypeAgentStateMap(
				$notReadySelectedVolatileTypeIds,
				$isNeedRefreshAgentStateMap
			);
			foreach ($volatileTypeAgentStateMap as $volatileTypeId => $agentState)
			{
				if (
					!isset($agentState['FINAL_ACTIONS']['USER_TYPE_MAP'][$userId])
					|| !(isset($agentState['IS_ACTIVE']) && $agentState['IS_ACTIVE'] === 'Y')
				)
				{
					if ($tryStart)
					{
						$this->startVolatileTypePrepareAgent(
							$userId,
							$volatileTypeId,
							[
								'types' => $types,
								'scope' => $scope,
								'notReadyVolatileTypes' => $this->getTypeNamesByIds($notReadySelectedVolatileTypeIds)
							]
						);
						$isNeedRefreshAgentStateMap = true;
					}
					else
					{
						$isHalfPercentageMode = false;
					}
				}
			}
		}
		
		if ($tryStart && !$isHalfPercentageMode)
		{
			$this->startIndexAgent($entityTypeName, $userId, $types, $scope);
		}

		$isActive = true;

		if ($isHalfPercentageMode)
		{
			$percentageSum = 0;
			$agentCount = 0;
			$inProgressCount = 0;
			$volatileTypeAgentStateMap = $this->getVolatileTypeAgentStateMap(
				$notReadySelectedVolatileTypeIds,
				$isNeedRefreshAgentStateMap
			);
			foreach ($volatileTypeAgentStateMap as $agentState)
			{
				if ($agentState['IS_IN_PROGRESS'] === 'Y')
				{
					if ($agentState['PROGRESS_VARS']['TOTAL_ITEMS'] <= 0)
					{
						$percentage = 0;
					}
					else
					{
						$percentage = (int)round(
							100
							* $agentState['PROGRESS_VARS']['PROCESSED_ITEMS']
							/ $agentState['PROGRESS_VARS']['TOTAL_ITEMS']
						);
					}
					$percentage = ($percentage > 100) ? 100 : $percentage;
					$inProgressCount++;
				}
				else
				{
					$percentage = 100;
				}
				$percentageSum += $percentage;
				$agentCount++;
			}
			$totalVolatilePercentage = $agentCount <= 0 ? 0 : (int)round($percentageSum / $agentCount);
			$totalVolatilePercentage = ($totalVolatilePercentage > 100) ? 100 : $totalVolatilePercentage;

			if ($inProgressCount < $notReadyTypeCount)
			{
				$isActive = false;
			}

			$ajaxResult['IS_ACTIVE'] = $isActive ? 'Y' : 'N';
			$ajaxResult['STATUS'] = 'STATUS_RUNNING';
			$ajaxResult['NEXT_STATUS'] = 'STATUS_UNDEFINED';
			$ajaxResult['PROCESSED_ITEMS'] = (int)round($totalVolatilePercentage / 2);
			$ajaxResult['TOTAL_ITEMS'] = 100;
		}
		else
		{
			$indexAgentState = $this->getIndexAgentState($userId, $entityTypeName);

			$ajaxResult['IS_ACTIVE'] = $indexAgentState['IS_ACTIVE'];
			$ajaxResult['STATUS'] = $indexAgentState['STATUS'];
			$ajaxResult['NEXT_STATUS'] = $indexAgentState['NEXT_STATUS'];
			if (
				isset($indexAgentState['INITIAL_PARAMS']['IS_HALF_PERCENTAGE_MODE'])
				&& $indexAgentState['INITIAL_PARAMS']['IS_HALF_PERCENTAGE_MODE'] === 'Y'
			)
			{
				if ($indexAgentState['TOTAL_ITEMS'] <= 0)
				{
					$indexPercentage = 0;
				}
				else
				{
					$indexPercentage = (int)round(
						50
						* $indexAgentState['PROCESSED_ITEMS']
						/ $indexAgentState['TOTAL_ITEMS']
					);
				}
				$indexPercentage = ($indexPercentage > 50) ? 50 : $indexPercentage;
				$indexPercentage += 50;
				$ajaxResult['PROCESSED_ITEMS'] = $indexPercentage;
				$ajaxResult['TOTAL_ITEMS'] = 100;
			}
			else
			{
				$ajaxResult['PROCESSED_ITEMS'] = $indexAgentState['PROCESSED_ITEMS'];
				$ajaxResult['TOTAL_ITEMS'] = $indexAgentState['TOTAL_ITEMS'];
			}
			$ajaxResult['FOUND_ITEMS'] = $indexAgentState['FOUND_ITEMS'];
			$ajaxResult['TOTAL_ENTITIES'] = $indexAgentState['TOTAL_ENTITIES'];
		}

		return $ajaxResult;
	}

	public function stopDuplicateIndex(int $userId, $entityTypeName): array
	{
		foreach ($this->getVolatileTypeIds(CCrmOwnerType::ResolveID($entityTypeName)) as $volatileTypeId)
		{
			$this->clearVolatileTypePrepareAgentUserContext($userId, $volatileTypeId);
		}

		$this->getIndexAgent($userId, $entityTypeName)->stop();

		return $this->getDuplicateIndexState($userId, $entityTypeName);
	}
}
