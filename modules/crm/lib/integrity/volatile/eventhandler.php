<?php

namespace Bitrix\Crm\Integrity\Volatile;

use Bitrix\Crm\Agent\Duplicate\Volatile;
use Bitrix\Crm\Agent\Duplicate\Background;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Integrity\AutoSearchUserSettings;
use Bitrix\Crm\Integrity\DedupeConfigCleaner;
use Bitrix\Crm\Integrity\DuplicateIndexType;
use Bitrix\Crm\Integrity\DuplicateIndexTypeSettingsTable;
use Bitrix\Crm\Integrity\DuplicateManager;
use Bitrix\Crm\Integrity\DuplicateVolatileCriterion;
use Bitrix\Crm\Integrity\Entity\AutosearchUserSettingsTable;
use Bitrix\Crm\Integrity\Volatile\Type\State;
use Bitrix\Main\Type\DateTime;
use CAgent;
use CCrmCompany;
use CCrmContact;
use CCrmLead;
use CCrmOwnerType;

class EventHandler
{
	protected const EVENT_UNDEFINED = 0;
	protected const EVENT_USER_FIELD_DELETE = 1;
	protected const EVENT_ASSIGN_VOLATILE_TYPE = 2;

	protected int $eventType;
	protected array $volatileTypeIds;
	protected array $entityTypeIds;

	protected function __construct(int $eventTypeId, array $volatileTypeIds, array $entityTypeIds)
	{
		$this->eventType = $this->isEventDefined($eventTypeId) ? $eventTypeId : static::EVENT_UNDEFINED;
		$this->volatileTypeIds = $volatileTypeIds;
		$this->entityTypeIds = $entityTypeIds;
	}

	protected function isEventDefined(int $eventTypeId): bool
	{
		return (
			$eventTypeId >= static::EVENT_USER_FIELD_DELETE
			&& $eventTypeId <= static::EVENT_ASSIGN_VOLATILE_TYPE
		);
	}

	protected function makeCleaningActions()
	{
		$this->stopVolatileTypeIndexAgents();
		if ($this->eventType !== static::EVENT_ASSIGN_VOLATILE_TYPE)
		{
			$this->setVolatileTypesStateFree();
		}
		$this->stopDuplicateIndexAgents();
		$this->cleanDedupeConfig();
		$this->renewAutomaticDuplicateIndexAgents();
		$this->cleanDuplicateIndex();
	}

	/** @noinspection PhpUnusedParameterInspection */
	public static function onUserFieldDelete(array $fields, int $id)
	{
		$ufEntityTypeMap = [
			CCrmLead::GetUserFieldEntityID() => [CCrmOwnerType::Lead],
			CCrmCompany::GetUserFieldEntityID() => [CCrmOwnerType::Company],
			CCrmContact::GetUserFieldEntityID() => [CCrmOwnerType::Contact],
			EntityRequisite::getSingleInstance()->getUfId() => [CCrmOwnerType::Company, CCrmOwnerType::Contact],
		];

		if (
			is_string($fields['FIELD_NAME'])
			&& $fields['FIELD_NAME'] !== ''
			&& is_string($fields['ENTITY_ID'])
			&& isset($ufEntityTypeMap[$fields['ENTITY_ID']])
		)
		{
			$filterEntityTypeIds = $ufEntityTypeMap[$fields['ENTITY_ID']];
			$fieldName = $fields['FIELD_NAME'];
			$volatileTypeIds = [];
			$entityTypeIds = [];
			$res = DuplicateIndexTypeSettingsTable::getList(
				[
					'select' => ['ID', 'ENTITY_TYPE_ID'],
					'filter' => [
						'@ENTITY_TYPE_ID' => $filterEntityTypeIds,
						'=FIELD_NAME' => $fieldName,
					]
				]
			);
			while ($row = $res->fetch())
			{
				$volatileTypeIds[] = (int)$row['ID'];
				$entityTypeIds[] = (int)$row['ENTITY_TYPE_ID'];
			}

			$eventHandler = new static(
				static::EVENT_USER_FIELD_DELETE,
				$volatileTypeIds,
				$entityTypeIds
			);
			$eventHandler->makeCleaningActions();
		}
	}

	public static function onAssignVolatileTypes(array $volatileTypeIds, array $entityTypeIds = [])
	{
		$verifiedVolatileTypeIds = [];
		foreach ($volatileTypeIds as $volatileTypeId)
		{
			if (DuplicateVolatileCriterion::isSupportedType($volatileTypeId))
			{
				$verifiedVolatileTypeIds[$volatileTypeId] = true;
			}
		}
		$verifiedVolatileTypeIds = array_keys($verifiedVolatileTypeIds);

		if (!empty($verifiedVolatileTypeIds))
		{
			$verifiedEntityTypeIds = [];
			foreach ($entityTypeIds as $entityTypeId)
			{
				if (CCrmOwnerType::IsDefined($entityTypeId))
				{
					$verifiedEntityTypeIds[$entityTypeId] = true;
				}
			}
			$verifiedEntityTypeIds = array_keys($verifiedEntityTypeIds);

			if (empty($verifiedEntityTypeIds))
			{
				foreach ($verifiedVolatileTypeIds as $volatileTypeId)
				{
					$typeInfo = TypeInfo::getInstance()->getById($volatileTypeId);
					$verifiedEntityTypeIds[$typeInfo['ENTITY_TYPE_ID']] = true;
				}
				$verifiedEntityTypeIds = array_keys($verifiedEntityTypeIds);
			}

			if (!empty($verifiedEntityTypeIds))
			{
				$eventHandler = new static(
					static::EVENT_ASSIGN_VOLATILE_TYPE,
					$verifiedVolatileTypeIds,
					$verifiedEntityTypeIds
				);
				$eventHandler->makeCleaningActions();
			}
		}
	}

	protected function stopVolatileTypeIndexAgents()
	{
		foreach (DuplicateVolatileCriterion::getSupportedDedupeTypes() as $volatileTypeId)
		{
			$agent = Volatile\IndexRebuild::getInstance($volatileTypeId);
			if (is_object($agent))
			{
				$progressData = $agent->state()->getData();
				$finalActionsInfo = $progressData['FINAL_ACTIONS'] ?? [];
				$userTypeMap =
					is_array($progressData['FINAL_ACTIONS']['USER_TYPE_MAP'])
						? $progressData['FINAL_ACTIONS']['USER_TYPE_MAP']
						: []
				;
				if (!empty($userTypeMap))
				{                                	
					foreach ($userTypeMap as $userId => $userInfo)
					{
						$userInfoModified = false;
						foreach (['types', 'notReadyVolatileTypes'] as $index)
						{
							$types = $userInfo[$index] ?? [];
							$modifiedTypes = [];
							$isModified = false;
							foreach ($types as $typeName)
							{
								if (in_array(DuplicateIndexType::resolveID($typeName), $this->volatileTypeIds, true))
								{
									$isModified = true;
								}
								else
								{
									$modifiedTypes[] = $typeName;
								}
							}
							if ($isModified)
							{
								$userInfo[$index] = $modifiedTypes;
								$userInfoModified = true;
							}
						}
						if ($userInfoModified)
						{
							if (empty($userInfo['types']) && empty($userInfo['notReadyVolatileTypes']))
							{
								unset($userTypeMap[$userId]);
							}
							else
							{
								$userTypeMap[$userId] = $userInfo;
							}
							if (empty($userTypeMap))
							{
								unset($finalActionsInfo['USER_TYPE_MAP']);
							}
							else
							{
								$finalActionsInfo['USER_TYPE_MAP'] = $userTypeMap;
							}
							$agent->setFinalActionsInfo($finalActionsInfo);
						}
					}
				}
			}
		}

		foreach ($this->volatileTypeIds as $volatileTypeId)
		{
			Volatile\IndexRebuild::getInstance($volatileTypeId)->delete();
		}
	}

	protected function stopDuplicateIndexAgents()
	{
		$res = CAgent::GetList(
			['ID' => 'DESC'],
			[
				'MODULE_ID' => 'crm',
				'NAME' => '%Bitrix\\Crm\\Agent\\Duplicate\\Background\\%',
				'ACTIVE' => 'Y',
			]
		);
		if (is_object($res))
		{
			$types = [];
			foreach ($this->volatileTypeIds as $id)
			{
				$types[DuplicateIndexType::resolveName($id)] = true;
			}
			$types = array_keys($types);
			$regexp =
				'/^(\\\\)?Bitrix\\\\Crm\\\\Agent\\\\Duplicate\\\\Background\\\\'
				. '(Lead|Company|Contact)IndexRebuild::run\\((\\d+)\\);$/'
			;
			$matches = [];
			$indexAgentHelper = Background\Helper::getInstance();
			while ($row = $res->Fetch())
			{
				$agentName = $row['NAME'] ?? '';
				if (preg_match($regexp, $agentName, $matches))
				{
					$entityTypeName = mb_strtoupper($matches[2]);
					$userId = (int)$matches[3];

					// Get agent and state
					/** @var Background\IndexRebuild $agentClassName */
					$agentClassName = $indexAgentHelper->getAgentClassName($entityTypeName, 'IndexRebuild');
					$agent = $agentClassName::getInstance($userId);
					$state = $agent->state()->getData();

					if (is_array($state['TYPES']) && !empty(array_intersect($types, $state['TYPES'])))
					{
						$agent->stop();
					}
				}
			}
		}
	}

	protected function setVolatileTypesStateFree()
	{
		State::getInstance()->bulkSet(array_fill_keys($this->volatileTypeIds, State::STATE_FREE));
	}

	protected function cleanDedupeConfig()
	{
		$configCleaner = new DedupeConfigCleaner();
		$configCleaner->removeTypes($this->entityTypeIds, $this->volatileTypeIds);
	}

	protected function renewAutomaticDuplicateIndexAgents()
	{
		foreach (
			AutosearchUserSettingsTable::query()
				->where('STATUS_ID', AutoSearchUserSettings::STATUS_INDEX_REBUILDING)
				->whereIn('ENTITY_TYPE_ID', $this->entityTypeIds)
				->setSelect(['*'])
				->fetchCollection()
			as $settings
		)
		{
			$userId = $settings->getUserId();
			$entityTypeId = $settings->getEntityTypeId();
			$progressData = $settings->getProgressData();
			$selectedTypeIds = $progressData['TYPE_IDS'] ?? [];
			if (!empty(array_intersect($this->volatileTypeIds, $selectedTypeIds)))
			{
				$res = CAgent::GetList(
					['ID' => 'DESC'],
					[
						'MODULE_ID' => 'crm',
						'NAME' => "%Bitrix\\Crm\\Agent\\Duplicate\\Automatic\\"
							. "RebuildUserDuplicateIndexAgent::run($entityTypeId, $userId);",
					]
				);
				if (is_object($res))
				{
					while ($row = $res->Fetch())
					{
						CAgent::Delete((int)$row['ID']);
					}
				}

				$settings->setNextExecTime((new DateTime())->add('20 minutes'));
				$settings->setStatusId(AutoSearchUserSettings::STATUS_NEW);
				$settings->save();
			}
		}
	}

	protected function cleanDuplicateIndex()
	{
		DuplicateManager::deleteDuplicateIndexItems(
			[
				'ENTITY_TYPE_ID' => $this->entityTypeIds,
				'TYPE_ID' => $this->volatileTypeIds,
			],
			true,
		);
	}
}
