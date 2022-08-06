<?php

namespace Bitrix\Crm\Integrity\Volatile\Type;

use Bitrix\Crm\Agent\Duplicate\Volatile\IndexRebuild;
use Bitrix\Crm\Integrity\DuplicateIndexTypeSettingsTable;
use Bitrix\Crm\Integrity\DuplicateVolatileCriterion;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use CCrmSecurityHelper;
use ReflectionClass;

class State
{
	public const STATE_UNDEFINED = 0;
	public const STATE_FREE = 10;        // Free, ready to assign
	public const STATE_ASSIGNED = 20;    // Assigned, await for indexing
	public const STATE_INDEX = 30;       // Indexing
	public const STATE_ERROR = 40;       // Error while indexing
	public const STATE_READY = 50;       // Indexed, ready for use

	/*
		Possible transitions
		-----------------------------------------------
		STATE_UNDEFINED => [STATE_FREE, STATE_ASSIGNED]
		STATE_FREE => [STATE_ASSIGNED]
		STATE_ASSIGNED => [STATE_FREE, STATE_INDEX]
		STATE_INDEX => [STATE_ERROR, STATE_READY]
		STATE_ERROR => [STATE_FREE, STATE_ASSIGNED]
		STATE_READY => [STATE_FREE, STATE_ASSIGNED, STATE_INDEX]
	*/

	protected const ERROR_MESSAGE_PREFIX = 'CRM_INTEGROTY_VOL_TYPE_STATE';
	protected const ERR_VOL_TYPE_STATE_MAP_EMPTY = 'ERR_VOL_TYPE_STATE_MAP_EMPTY';
	protected const ERR_VOL_TYPE_STATE_MAP_INVALID = 'ERR_VOL_TYPE_STATE_MAP_INVALID';
	protected const ERR_STATE_TRANSITION_IMPOSSIBLE = 'ERR_STATE_TRANSITION_IMPOSSIBLE';

	public static function getInstance()
	{
		static $instance = null;

		if ($instance === null)
		{
			$instance = new static();
		}

		return $instance;
	}

	protected function getPossibleTransitionsMap(): array
	{
		static $map = null;
		
		if ($map === null)
		{
			$map = [
				static::STATE_UNDEFINED => [
					static::STATE_FREE => true,
					static::STATE_ASSIGNED => true,
				],
				static::STATE_FREE => [
					static::STATE_ASSIGNED => true,
				],
				static::STATE_ASSIGNED => [
					static::STATE_FREE => true,
					static::STATE_INDEX => true,
				],
				static::STATE_INDEX => [
					static::STATE_FREE => true,
					static::STATE_ERROR => true,
					static::STATE_READY => true,
				],
				static::STATE_ERROR => [
					static::STATE_FREE => true,
					static::STATE_ASSIGNED => true,
				],
				static::STATE_READY => [
					static::STATE_FREE => true,
					static::STATE_ASSIGNED => true,
					static::STATE_INDEX => true,
				],
			];
		}
		
		return $map;
	}

	protected function getErrorMessage(string $errorCode): string
	{
		$message =  Loc::getMessage(static::ERROR_MESSAGE_PREFIX . '_' . $errorCode);

		if ($message === null)
		{
			$message = '';
		}

		return $message;
	}

	protected function makeErrorByCode(string $errorCode, array $data = null): Error
	{
		return new Error($this->getErrorMessage($errorCode), $errorCode, $data);
	}

	public function getStateCode(int $id): string
	{
		$stateMap = $this->getStateMap();

		return $stateMap[$id] ?? '';
	}

	protected function getStateMap(): array
	{
		static $stateMap = null;

		if ($stateMap === null)
		{
			$stateMap = [];
			$refClass = new ReflectionClass(__CLASS__);
			foreach ($refClass->getConstants() as $name => $value)
			{
				if (mb_substr($name, 0, 6) === 'STATE_')
				{
					$stateMap[$value] = $name;
				}
			}
		}

		return $stateMap;
	}

	public function isDefined($stateId): bool
	{
		$stateMap = $this->getStateMap();

		if ($stateId !== static::STATE_UNDEFINED && isset($stateMap[$stateId]))
		{
			return true;
		}

		return false;
	}

	protected function validateVolatileTypeIds(array $typeIds): array
	{
		$result = [];

		$supportedTypeMap = array_fill_keys(DuplicateVolatileCriterion::getAllSupportedDedupeTypes(), true);

		foreach ($typeIds as $typeId)
		{
			if (is_int($typeId) && isset($supportedTypeMap[$typeId]))
			{
				$result[] = $typeId;
			}
		}

		return $result;
	}

	protected function validateVolatileTypeStateMap(array $volatileTypeStateMap): array
	{
		$result = [];

		$supportedTypeMap = array_fill_keys(DuplicateVolatileCriterion::getAllSupportedDedupeTypes(), true);

		foreach ($volatileTypeStateMap as $typeId => $stateId)
		{
			if (is_int($typeId) && isset($supportedTypeMap[$typeId]))
			{
				$result[$typeId] = $this->isDefined($stateId) ? $stateId : static::STATE_UNDEFINED;
			}
		}

		return $result;
	}

	public function bulkGet(array $volatileTypeIds = []): Result
	{
		$result = new Result();

		if (empty($volatileTypeIds))
		{
			$volatileTypeIds = DuplicateVolatileCriterion::getAllSupportedDedupeTypes();
		}
		else
		{
			$volatileTypeIds = $this->validateVolatileTypeIds($volatileTypeIds);
		}

		$volatileTypeStateMap = [];

		if (!empty($volatileTypeIds))
		{
			$res = DuplicateIndexTypeSettingsTable::getList(
				[
					'filter' => ['@ID' => $volatileTypeIds],
					'select' => ['ID', 'STATE_ID']
				]
			);
			while ($row = $res->fetch())
			{
				$volatileTypeStateMap[(int)$row['ID']] = (int)$row['STATE_ID'];
			}
		}

		$result->setData($this->validateVolatileTypeStateMap($volatileTypeStateMap));

		return $result;
	}

	public function get(int $volatileTypeId): Result
	{
		$result = new Result();

		$localResult = $this->bulkGet([$volatileTypeId]);

		if ($localResult->isSuccess())
		{
			$stateMap = $localResult->getData();
			if (isset($stateMap[$volatileTypeId]))
			{
				$stateId = $stateMap[$volatileTypeId];
			}
			else
			{
				$stateId = State::STATE_UNDEFINED;
			}
			$result->setData(['stateId' => $stateId]);
		}
		else
		{
			$result->addErrors($localResult->getErrors());
		}

		return $result;
	}

	protected function checkStateTransition(int $currentStateId, int $nextStateId): bool
	{
		$transitionMap = $this->getPossibleTransitionsMap();

		if (isset($transitionMap[$currentStateId][$nextStateId]))
		{
			return true;
		}

		return false;
	}

	public function set(int $volatileTypeId, int $stateId, int $currentStateId = null): Result
	{
		$result = new Result();

		if ($currentStateId === null)
		{
			$localResult = $this->get($volatileTypeId);
			if (!$localResult->isSuccess())
			{
				$result->addErrors($localResult->getErrors());

				return $result;
			}

			$currentStateId = $localResult->getData()['stateId'];
		}

		if (!$this->checkStateTransition($currentStateId, $stateId))
		{
			$result->addError(
				$this->makeErrorByCode(
					static::ERR_STATE_TRANSITION_IMPOSSIBLE,
					[
						'volatileTypeId' => $volatileTypeId,
						'currentStateId' => $currentStateId,
						'nextStateId' => $stateId,
					]
				)
			);

			return $result;
		}

		$active = $stateId === static::STATE_READY ? 'Y' : 'N';

		if ($stateId === static::STATE_INDEX)
		{
			$agentParams = [];
			$userId = CCrmSecurityHelper::GetCurrentUserID();
			if ($userId > 0)
			{
				$agentParams['USER_ID'] = $userId;
			}
		}

		DuplicateIndexTypeSettingsTable::update(
			$volatileTypeId,
			['ACTIVE' => $active, 'STATE_ID' => $stateId]
		);

		return $result;
	}

	public function bulkSet(array $volatileTypeStateMap): Result
	{
		$result = new Result();

		if (empty($volatileTypeStateMap))
		{
			$result->addError($this->makeErrorByCode(static::ERR_VOL_TYPE_STATE_MAP_EMPTY));

			return $result;
		}

		$volatileTypeStateMap = $this->validateVolatileTypeStateMap($volatileTypeStateMap);
		if (empty($volatileTypeStateMap))
		{
			$result->addError($this->makeErrorByCode(static::ERR_VOL_TYPE_STATE_MAP_INVALID));

			return $result;
		}

		$localResult = $this->bulkGet(array_keys($volatileTypeStateMap));
		if (!$localResult->isSuccess())
		{
			$result->addErrors($localResult->getErrors());

			return $result;
		}

		$currentVolatileTypeStateMap = $localResult->getData();

		foreach ($volatileTypeStateMap as $volatileTypeId => $nextStateId)
		{
			$currentStateId = $currentVolatileTypeStateMap[$volatileTypeId] ?? static::STATE_UNDEFINED;
			$localResult = $this->set($volatileTypeId, $nextStateId, $currentStateId);
			if (!$localResult->isSuccess())
			{
				$result->addErrors($localResult->getErrors());
			}
		}

		return $result;
	}
}
