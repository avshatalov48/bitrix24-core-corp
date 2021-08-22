<?php


namespace Bitrix\Crm\Controller;

use Bitrix\Crm\StatusTable;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use CCrmStatus;

class Status extends Controller
{
	const ERR_ACTION_SET_ACCESS_DENIED = 'ERR_ACCESS_DENIED';
	const ERR_ACTION_SET_INVALID_CONFIG = 'ERR_INVALID_CONFIG';
	const ERR_ACTION_SET_NO_DATA_TO_CHANGE_LIST = 'ERR_NO_DATA_TO_CHANGE_LIST';
	const ERR_ACTION_SET_SORT_LIST = 'ERR_SORT_LIST';
	const ERR_ACTION_SET_LIST_HAS_INVALID_IDENTIFIERS = 'ERR_LIST_HAS_INVALID_IDENTIFIERS';
	const ERR_ACTION_SET_LIST_HAS_INVALID_VALUES = 'ERR_LIST_HAS_INVALID_VALUES';
	const ERR_ACTION_SET_DELETED_SYSTEM_ITEMS = 'ERR_DELETED_SYSTEM_ITEMS';
	const ERR_ACTION_SET_DELETED_ITEM = 'ERR_DELETED_ITEM';
	const ERR_ACTION_SET_ADD_ITEM = 'ERR_ADD_ITEM';
	const ERR_ACTION_SET_UPDATE_ITEM = 'ERR_UPDATE_ITEM';

	public function getItemsAction(array $configData): ?array
	{
		$innerConfig = $this->prepareConfig('getItems', $configData);

		if ($this->getErrors())
		{
			return null;
		}

		if (!CCrmStatus::CheckReadPermission())
		{
			$this->addError(
				new Error(
					'Access denied.',
					self::ERR_ACTION_SET_ACCESS_DENIED
				)
			);

			return null;
		}

		return $this->getItems($innerConfig['statusType']);
	}

	public function setItemsAction(array $configData): ?array
	{
		$innerConfig = $this->prepareConfig('setItems', $configData);

		if ($this->getErrors())
		{
			return null;
		}

		if (!CCrmStatus::CheckCreatePermission())
		{
			$this->addError(
				new Error(
					'Access denied.',
					self::ERR_ACTION_SET_ACCESS_DENIED
				)
			);

			return null;
		}

		$newStatusList = $this->prepareNewStatusList($configData);

		if ($this->getErrors())
		{
			return null;
		}

		$actions = $this->makeActions($innerConfig['statusType'], $newStatusList);

		if ($this->getErrors())
		{
			return null;
		}

		$this->playActions($innerConfig['statusType'], $actions, $newStatusList);

		if ($this->getErrors())
		{
			return null;
		}

		return $newStatusList;
	}

	protected function getAllowedStatusTypes(): array
	{
		return CCrmStatus::getAllowedInnerConfigTypes();
	}

	protected function isAllowedStatusType(string $statusTypeId): bool
	{
		static $statusTypeIdMap = null;

		if ($statusTypeIdMap === null)
		{
			$statusTypeIdMap = array_fill_keys($this->getAllowedStatusTypes(), true);
		}

		return isset($statusTypeIdMap[$statusTypeId]);
	}

	protected function prepareConfig(string $actionName, array $configData): array
	{
		$result = [];

		$controllerName = 'crm.status.setItems';

		// Check configuration
		if (
			is_array($configData['innerConfig'])
			&& isset($configData['innerConfig']['type'])
			&& $configData['innerConfig']['type'] === 'crm_status'
			&& isset($configData['innerConfig']['controller'])
			&& $configData['innerConfig']['controller'] === $controllerName
			&& isset($configData['innerConfig']['statusType'])
			&& is_string($configData['innerConfig']['statusType'])
			&& $configData['innerConfig']['statusType'] !== ''
			&& $this->isAllowedStatusType($configData['innerConfig']['statusType'])
		)
		{
			$result['type'] = $configData['innerConfig']['type'];
			$result['controller'] = $configData['innerConfig']['controller'];
			$result['statusType'] = $configData['innerConfig']['statusType'];
		}
		else
		{
			$this->addError(
				new Error(
					'Invalid configuration data.',
					self::ERR_ACTION_SET_INVALID_CONFIG
				)
			);
		}

		return $result;
	}

	protected function prepareNewStatusList($configData): array
	{
		$isNewStatusListPresent = is_array($configData['enumeration']);
		$result = [];

		if (!$isNewStatusListPresent)
		{
			$this->addError(
				new Error(
					'There is no data to change the list.',
					self::ERR_ACTION_SET_NO_DATA_TO_CHANGE_LIST
				)
			);
			return $result;
		}

		$listData = $configData['enumeration'];
		$sort = [];
		$index = [];
		$map = [];
		$idMap = [];
		$i = 0;
		$isListHasInvalidId = false;
		foreach ($listData as $key => $itemInfo)
		{
			$isIdPresent = isset($itemInfo['ID']);
			$isValidId = (
				$isIdPresent
				&& is_string($itemInfo['ID'])
				&& mb_strlen($itemInfo['ID']) <= 50
			);
			$isListHasInvalidId = ($isIdPresent && !$isValidId);
			if ($isListHasInvalidId)
			{
				break;
			}
			if (
				(!$isIdPresent || (is_string($itemInfo['ID']) && mb_strlen($itemInfo['ID']) <= 50))
				&& isset($itemInfo['SORT']) && is_string($itemInfo['SORT'])
			)
			{
				if (!$isIdPresent || !isset($idMap[$itemInfo['ID']]))
				{
					$map[$i] = $key;
					$index[] = $i++;
					$sort[] = $itemInfo['SORT'];
					$idMap[$itemInfo['ID']] = true;
				}
			}
		}
		if ($isListHasInvalidId)
		{
			$this->addError(
				new Error(
					'List items contain invalid identifiers.',
					self::ERR_ACTION_SET_LIST_HAS_INVALID_IDENTIFIERS
				)
			);
			return $result;
		}
		if (!array_multisort($sort, SORT_NUMERIC, $index, SORT_NUMERIC))
		{
			$this->addError(new Error('List sorting error.', self::ERR_ACTION_SET_SORT_LIST));
			return $result;
		}
		$isListHasInvalidValue = false;
		foreach ($index as $i)
		{
			$itemInfo = $listData[$map[$i]];
			$isValidValue = (
				isset($itemInfo['VALUE'])
				&& is_string($itemInfo['VALUE'])
				&& mb_strlen($itemInfo['VALUE']) <= 100
			);
			$isListHasInvalidValue = !$isValidValue;
			if ($isListHasInvalidValue)
			{
				break;
			}
			$result[] = $itemInfo;
		}
		if ($isListHasInvalidValue)
		{
			$this->addError(
				new Error(
					'List items contain invalid values.',
					self::ERR_ACTION_SET_LIST_HAS_INVALID_VALUES
				)
			);
			return $result;
		}

		return $result;
	}

	protected function getItems(string $statusTypeId): array
	{
		$statusList = [];
		foreach (StatusTable::loadStatusesByEntityId($statusTypeId) as $statusInfo)
		{
			$statusList[] = [
				'IS_FAKE' => 'N',
				'IS_SYSTEM' => ($statusInfo['SYSTEM'] === 'Y') ? 'Y' : 'N',
				'VALUE' => $statusInfo['NAME'],
				'ID' => $statusInfo['STATUS_ID'],
				'XML_ID' => '',
				'SORT' => $statusInfo['SORT'],
			];
		}

		return $statusList;
	}

	protected function getStatusMap(string $statusTypeId): array
	{
		$statusMap = [];
		foreach (StatusTable::loadStatusesByEntityId($statusTypeId) as $statusInfo)
		{
			$statusMap[$statusInfo['STATUS_ID']] = $statusInfo;
		}

		return $statusMap;
	}

	protected function makeActions(string $statusTypeId, array $newStatusList): array
	{
		$actions = [
			'add' => [],
			'update' => [],
			'delete' => [],
		];

		$statusMap = $this->getStatusMap($statusTypeId);
		$newStatusMap = [];
		$sort = 0;
		foreach ($newStatusList as $index => $statusInfo)
		{
			if (isset($statusInfo['IS_FAKE']) && $statusInfo['IS_FAKE'] === 'Y')
			{
				continue;
			}

			$isIdPresent = isset($statusInfo['ID']);
			$sort += 10;
			$sortString = (string)$sort;
			if ($isIdPresent && isset($statusMap[$statusInfo['ID']]))
			{
				// update
				$newStatusMap[$statusInfo['ID']] = true;
				$origFields = $statusMap[$statusInfo['ID']];
				$updateFields = [];
				if ($origFields['NAME'] !== $statusInfo['VALUE'])
				{
					$updateFields['NAME'] = $statusInfo['VALUE'];
					$updateFields['SORT'] = $sortString;
				}
				else if ($origFields['SORT'] !== $sortString)
				{
					$updateFields['SORT'] = $sortString;
				}
				if (!empty($updateFields))
				{
					$actions['update'][] = [
						'ID' => $origFields['ID'],
						'FIELDS' => $updateFields,
					];
				}
			}
			else
			{
				// add
				$addFields = [];
				if ($isIdPresent)
				{
					$addFields['ID'] = $statusInfo['ID'];
				}
				$addFields['NAME'] = $statusInfo['VALUE'];
				$addFields['SORT'] = $sortString;
				$actions['add'][] = ['INDEX' => $index, 'FIELDS' => $addFields];
			}
		}

		// Determining deleted items
		$isSystemItemDeleted = false;
		foreach ($statusMap as $statusInfo)
		{
			if (!isset($newStatusMap[$statusInfo['STATUS_ID']]))
			{
				if ($statusInfo['SYSTEM'] === 'Y')
				{
					$isSystemItemDeleted = true;
					break;
				}
				else
				{
					$actions['delete'][] = ['FIELDS' => ['ID' => $statusInfo['ID']]];
				}
			}
		}

		if ($isSystemItemDeleted)
		{
			$this->addError(
				new Error(
					'There are deleted system items.',
					self::ERR_ACTION_SET_DELETED_SYSTEM_ITEMS
				)
			);
		}

		return $actions;
	}

	protected function playActions(string $statusTypeId, array $actions, &$newStatusList): void
	{
		$status = new CCrmStatus($statusTypeId);

		$isError = false;

		if (is_array($actions['delete']))
		{
			foreach ($actions['delete'] as $info)
			{
				$deleteResult = StatusTable::delete($info['FIELDS']['ID']);
				if (!$deleteResult->isSuccess())
				{
					$isError = true;
					break;
				}
			}
		}
		if ($isError)
		{
			$this->addError(
				new Error(
					'Unable to delete item.',
					self::ERR_ACTION_SET_DELETED_ITEM
				)
			);
		}

		if (!$this->getErrors() && is_array($actions['add']))
		{
			$addIndex = [];
			$addIds = [];
			foreach ($actions['add'] as $info)
			{
				$id = $status->Add($info['FIELDS']);
				if ($id)
				{
					$addIndex[$id] = $info['INDEX'];
					$addIds[] = $id;
				}
				else
				{
					$isError = true;
					break;
				}
			}
			if (!$isError && !empty($addIds))
			{
				$res = StatusTable::getList(
					[
						'filter' => [
							'=ENTITY_ID' => $statusTypeId,
							'@ID' => $addIds
						],
						'select' => ['ID', 'STATUS_ID'],
					]
				);
				while($row = $res->fetch())
				{
					$newStatusList[$addIndex[$row['ID']]]['ID'] = $row['STATUS_ID'];
				}
			}
		}
		if ($isError)
		{
			$this->addError(
				new Error(
					'Unable to add item.',
					self::ERR_ACTION_SET_ADD_ITEM
				)
			);
		}

		if (!$this->getErrors() && is_array($actions['update']))
		{
			foreach ($actions['update'] as $info)
			{
				if (!$status->Update($info['ID'], $info['FIELDS']))
				{
					$isError = true;
					break;
				}
			}
		}
		if ($isError)
		{
			$this->addError(
				new Error(
					'Unable to update item.',
					self::ERR_ACTION_SET_UPDATE_ITEM
				)
			);
		}
	}
}