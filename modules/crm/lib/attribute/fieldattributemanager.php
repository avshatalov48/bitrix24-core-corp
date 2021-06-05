<?php
namespace Bitrix\Crm\Attribute;

use Bitrix\Crm;
use Bitrix\Crm\UserField\Visibility\VisibilityManager;
use Bitrix\Main;

class FieldAttributeManager
{
	public static function isEnabled()
	{
		return true;
	}
	public static function isPhaseDependent()
	{
		return Crm\Restriction\RestrictionManager::getAttributeConfigRestriction()->hasPermission();
	}

	public static function isEntitySupported(int $entityTypeId): bool
	{
		if (
			$entityTypeId === \CCrmOwnerType::Contact
			|| $entityTypeId === \CCrmOwnerType::Company
		)
		{
			return false;
		}

		$factory = Crm\Service\Container::getInstance()->getFactory($entityTypeId);
		if($factory)
		{
			return $factory->isStagesEnabled();
		}

		return true;
	}

	public static function resolveEntityScope($entityTypeID, $entityID, array $options = null): string
	{
		if (!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentException(
				'The argument must be valid CCrmOwnerType.',
				'entityTypeID'
			);
		}

		$categoryID = null;

		if ($entityTypeID === \CCrmOwnerType::Deal)
		{
			$categoryID = is_array($options) && isset($options['CATEGORY_ID']) ? (int)$options['CATEGORY_ID'] : -1;
			if ($categoryID < 0)
			{
				$categoryID = \CCrmDeal::GetCategoryID($entityID);
			}
		}
		else
		{
			$factory = Crm\Service\Container::getInstance()->getFactory($entityTypeID);
			if ($factory)
			{
				if (is_array($options))
				{
					$categoryID = (int)($options['CATEGORY_ID'] ?? null);
				}
				if (!$categoryID && $factory->isCategoriesSupported())
				{
					$item = $factory->getItem($entityID);
					if ($item)
					{
						$categoryID = $item->getCategoryId();
					}
					else
					{
						$categoryID = $factory->createDefaultCategoryIfNotExist()->getId();
					}
				}
			}
		}

		return static::getEntityScopeByCategory($categoryID);
	}

	public static function getEntityScopeByCategory(?int $categoryId = 0): string
	{
		return $categoryId > 0 ? "category_{$categoryId}" : "";
	}

	public static function processPhaseDeletion($phaseID, $entityTypeID, $entityScope)
	{
		Crm\Attribute\Entity\FieldAttributeTable::deleteByPhase($phaseID, $entityTypeID, $entityScope);
	}

	public static function processPhaseModification($phaseID, $entityTypeID, $entityScope, array $phases)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		$connection = Main\HttpApplication::getConnection();
		$helper = $connection->getSqlHelper();

		$scopeSql = $helper->forSql($entityScope);
		$phaseSql = $helper->forSql($phaseID);

		$dbResult = $connection->query(
			"SELECT * FROM b_crm_field_attr WHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_SCOPE = '{$scopeSql}' AND (START_PHASE = '{$phaseSql}' OR FINISH_PHASE = '{$phaseSql}')"
		);

		while($fields = $dbResult->fetch())
		{
			$startPhaseID = $fields['START_PHASE'];
			$finishPhaseID = $fields['FINISH_PHASE'];

			if($startPhaseID === $finishPhaseID)
			{
				continue;
			}

			$startPhase = isset($phases[$startPhaseID]) ? $phases[$startPhaseID] : null;
			$finishPhase = isset($phases[$finishPhaseID]) ? $phases[$finishPhaseID] : null;
			if(!(is_array($startPhase) && is_array($finishPhase)))
			{
				continue;
			}

			$startPhaseSort = isset($startPhase['SORT']) ? (int)$startPhase['SORT'] : 0;
			$finishPhaseSort = isset($finishPhase['SORT']) ? (int)$finishPhase['SORT'] : 0;
			if($startPhaseSort > $finishPhaseSort)
			{
				Crm\Attribute\Entity\FieldAttributeTable::delete($fields['ID']);
			}
		}
	}

	public static function getEntityConfigurations($entityTypeID, $entityScope)
	{
		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentException(
				'The argument must be valid CCrmOwnerType.',
				'entityTypeID'
			);
		}

		$results = self::getFieldAttributes($entityTypeID, $entityScope);

		$configs = array();
		foreach ($results as $fieldName => $fieldData)
		{
			$configs[$fieldName] = array();
			foreach ($fieldData as $typeID => $typeData)
			{
				$config = [
					'typeId' => $typeID,
					'groups' => array()
				];
				foreach ($typeData as $phaseGroupTypeID => $phaseGroupTypeData)
				{
					$config['groups'][] = array(
						'phaseGroupTypeId' => $phaseGroupTypeID,
						'items' => $phaseGroupTypeData
					);
				}
				$configs[$fieldName][] = $config;
			}
		}

		return $configs;
	}

	private static function getFieldAttributes(int $entityTypeID, string $entityScope):array
	{
		$query = new Main\Entity\Query(Crm\Attribute\Entity\FieldAttributeTable::getEntity());
		//$query->addSelect('ID');
		$query->addSelect('TYPE_ID');
		//$query->addSelect('IS_CUSTOM_FIELD');
		$query->addSelect('FIELD_NAME');
		$query->addSelect('START_PHASE');
		$query->addSelect('FINISH_PHASE');
		$query->addSelect('PHASE_GROUP_TYPE_ID');

		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$query->addFilter('=ENTITY_SCOPE', $entityScope);

		$results = array();
		$dbResult = $query->exec();
		while($fields = $dbResult->fetch())
		{
			$fieldName = $fields['FIELD_NAME'];
			$typeID = $fields['TYPE_ID'];
			$phaseGroupTypeID = $fields['PHASE_GROUP_TYPE_ID'];

			if(!isset($results[$fieldName]))
			{
				$results[$fieldName] = array();
			}

			if(!isset($results[$fieldName][$typeID]))
			{
				$results[$fieldName][$typeID] = array();
			}

			if(!isset($results[$fieldName][$typeID][$phaseGroupTypeID]))
			{
				$results[$fieldName][$typeID][$phaseGroupTypeID] = array();
			}

			$results[$fieldName][$typeID][$phaseGroupTypeID][] = array(
				'startPhaseId' => $fields['START_PHASE'],
				'finishPhaseId' => $fields['FINISH_PHASE']
			);
		}
		return $results;
	}
	public static function saveEntityConfiguration(array $config, $fieldName, $entityTypeID, $entityScope)
	{
		if($fieldName === '')
		{
			return;
		}

		$typeID = isset($config['typeId']) ? (int)$config['typeId'] : FieldAttributeType::REQUIRED;
		self::removeEntityConfiguration($typeID, $fieldName, $entityTypeID, $entityScope);

		$groups = isset($config['groups']) && is_array($config['groups']) ? $config['groups'] : array();
		if(empty($groups))
		{
			return;
		}

		foreach($groups as $group)
		{
			$phaseGroupTypeID = isset($group['phaseGroupTypeId'])
				? (int)$group['phaseGroupTypeId'] : FieldAttributePhaseGroupType::UNDEFINED;

			if(!FieldAttributePhaseGroupType::isDefined($phaseGroupTypeID))
			{
				continue;
			}

			if($phaseGroupTypeID === FieldAttributePhaseGroupType::ALL)
			{
				Entity\FieldAttributeTable::add(
					array(
						'ENTITY_TYPE_ID' => $entityTypeID,
						'ENTITY_SCOPE' => $entityScope,
						'TYPE_ID' => $typeID,
						'FIELD_NAME' => $fieldName,
						'CREATED_TIME' => new Main\Type\DateTime(),
						'START_PHASE' => '',
						'FINISH_PHASE' => '',
						'PHASE_GROUP_TYPE_ID' => FieldAttributePhaseGroupType::ALL,
						'IS_CUSTOM_FIELD' => (mb_strpos($fieldName, 'UF_') === 0) ? 'Y' : 'N'
					)
				);
				break;
			}

			$items = isset($group['items']) && is_array($group['items']) ? $group['items'] : array();
			foreach($items as $item)
			{
				$startPhaseID = isset($item['startPhaseId']) ? $item['startPhaseId'] : '';
				$finishPhaseID = isset($item['finishPhaseId']) ? $item['finishPhaseId'] : '';

				if($startPhaseID === '' || $finishPhaseID === '')
				{
					continue;
				}

				Entity\FieldAttributeTable::add(
					array(
						'ENTITY_TYPE_ID' => $entityTypeID,
						'ENTITY_SCOPE' => $entityScope,
						'TYPE_ID' => $typeID,
						'FIELD_NAME' => $fieldName,
						'CREATED_TIME' => new Main\Type\DateTime(),
						'START_PHASE' => $startPhaseID,
						'FINISH_PHASE' => $finishPhaseID,
						'PHASE_GROUP_TYPE_ID' => $phaseGroupTypeID,
						'IS_CUSTOM_FIELD' => (mb_strpos($fieldName, 'UF_') === 0) ? 'Y' : 'N'
					)
				);
			}
		}
	}
	public static function removeEntityConfiguration($typeID, $fieldName, $entityTypeID, $entityScope = '')
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		if(!FieldAttributeType::isDefined($typeID))
		{
			return;
		}

		$connection = Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		$conditionSql = implode(
			' AND ',
			array(
				$helper->prepareAssignment('b_crm_field_attr', 'ENTITY_TYPE_ID', $entityTypeID),
				$helper->prepareAssignment('b_crm_field_attr', 'ENTITY_SCOPE', $entityScope),
				$helper->prepareAssignment('b_crm_field_attr', 'TYPE_ID', $typeID),
				$helper->prepareAssignment('b_crm_field_attr', 'FIELD_NAME', $fieldName)
			)
		);
		$connection->queryExecute('DELETE FROM b_crm_field_attr WHERE '.$conditionSql);
	}

	public static function getRequiredFields($entityTypeID, $entityID, array $entityData, $fieldOrigin = 0, array $options = null)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentException(
				'The argument must be valid CCrmOwnerType.',
				'entityTypeID'
			);
		}

		if (!self::isPhaseDependent())
		{
			return [];
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$entityScope = '';
		if($entityTypeID === \CCrmOwnerType::Deal)
		{
			$categoryID = isset($entityData['CATEGORY_ID']) ? (int)$entityData['CATEGORY_ID'] : -1;
			$stageID = isset($entityData['STAGE_ID']) ? $entityData['STAGE_ID'] : '';
			if($categoryID < 0 || $stageID === '')
			{
				if($stageID === '')
				{
					if($entityID > 0)
					{
						$dbResult = \CCrmDeal::GetListEx(
							array(),
							array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
							false,
							false,
							array('ID', 'CATEGORY_ID', 'STAGE_ID')
						);
						$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
						if(!$fields)
						{
							return array();
						}
						$categoryID = isset($fields['CATEGORY_ID']) ? (int)$fields['CATEGORY_ID'] : 0;
						$stageID = isset($fields['STAGE_ID']) ? $fields['STAGE_ID'] : '';
					}
					if($stageID === '')
					{
						$stageID = \CCrmDeal::GetStartStageID($categoryID);
					}
				}
				elseif($categoryID < 0)
				{
					$categoryID = Crm\Category\DealCategory::resolveFromStageID($stageID);
				}

				$entityData['CATEGORY_ID'] = $categoryID;
				$entityData['STAGE_ID'] = $stageID;
			}

			$entityScope = self::resolveEntityScope(
				\CCrmOwnerType::Deal,
				$entityID,
				array('CATEGORY_ID' => $categoryID)
			);

			if(!isset($options['CATEGORY_ID']))
			{
				$options['CATEGORY_ID'] = $categoryID;
			}
		}

		$fieldsData = static::getList($entityTypeID, $entityScope, $fieldOrigin);

		$result = [];
		foreach ($fieldsData as $fields)
		{
			if (!self::checkPhaseCondition(
				$entityTypeID,
				$entityData,
				$fields['START_PHASE'],
				$fields['FINISH_PHASE'],
				$options
			))
			{
				continue;
			}

			if($fieldOrigin !== FieldOrigin::UNDEFINED)
			{
				$result[] = $fields['FIELD_NAME'];
			}
			else
			{
				$key = $fields['IS_CUSTOM_FIELD'] === 'Y' ? FieldOrigin::CUSTOM : FieldOrigin::SYSTEM;
				if(!isset($result[$key]))
				{
					$result[$key] = array();
				}
				$result[$key][] = $fields['FIELD_NAME'];
			}
		}

		if (is_array($result[FieldOrigin::CUSTOM]))
		{
			$notAccessibleFields = VisibilityManager::getNotAccessibleFields($entityTypeID);
			$result[FieldOrigin::CUSTOM] =  array_diff($result[FieldOrigin::CUSTOM], $notAccessibleFields);
		}

		return $result;
	}

	public static function getRequiredUserFields($entityTypeID, $entityID, array $entityData)
	{
		return self::getRequiredFields($entityTypeID, $entityID, $entityData, FieldOrigin::CUSTOM);
	}

	public static function getRequiredSystemFields($entityTypeID, $entityID, array $entityData)
	{
		return self::getRequiredFields($entityTypeID, $entityID, $entityData, FieldOrigin::SYSTEM);
	}

	public static function onUserFieldDelete(array $fields, $ID)
	{
		$fieldName = isset($fields['FIELD_NAME']) ? $fields['FIELD_NAME'] : '';
		if($fieldName === '')
		{
			return;
		}

		$connection = Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		$connection->queryExecute('DELETE FROM b_crm_field_attr WHERE '
			.$helper->prepareAssignment('b_crm_field_attr', 'FIELD_NAME', $fieldName)
		);
	}
	public static function onUserFieldUpdate(array $fields, $ID)
	{
		if($ID <= 0)
		{
			return;
		}

		$allFields = \CUserTypeEntity::GetByID($ID);
		if(!is_array($allFields))
		{
			return;
		}

		$fields = array_merge($allFields, $fields);

		$fieldName = isset($fields['FIELD_NAME']) ? $fields['FIELD_NAME'] : '';
		if($fieldName === '')
		{
			return;
		}

		if(isset($fields['MANDATORY']) && $fields['MANDATORY'] === 'Y')
		{
			$connection = Main\Application::getConnection();
			$helper = $connection->getSqlHelper();

			$conditionSql = implode(
				' AND ',
				array(
					$helper->prepareAssignment('b_crm_field_attr', 'FIELD_NAME', $fieldName),
					$helper->prepareAssignment('b_crm_field_attr', 'TYPE_ID', FieldAttributeType::REQUIRED)
				)
			);

			$connection->queryExecute("DELETE FROM b_crm_field_attr WHERE {$conditionSql}");
		}
	}

	protected static function checkPhaseCondition($entityTypeID, array $entityData, $startPhase, $finishPhase, array $options = null)
	{
		//If Start Phase and Finish Phase are empty, then field is required always.
		if($startPhase === '' && $finishPhase === '')
		{
			return true;
		}

		if(!is_array($options))
		{
			$options = array();
		}

		if($entityTypeID === \CCrmOwnerType::Deal)
		{
			$categoryID = isset($entityData['CATEGORY_ID']) ? (int)$entityData['CATEGORY_ID'] : -1;
			if($categoryID < 0 && isset($options['CATEGORY_ID']))
			{
				$categoryID = $options['CATEGORY_ID'];
			}

			$startStageSort = \CCrmDeal::GetStageSort($startPhase, $categoryID);
			$finishStageSort = \CCrmDeal::GetStageSort($finishPhase, $categoryID);

			$stageID = isset($options['STAGE_ID'])
				? $options['STAGE_ID']
				: (isset($entityData['STAGE_ID']) ? $entityData['STAGE_ID'] : '');
			$stageSort = \CCrmDeal::GetStageSort($stageID, $categoryID);

			return($stageSort >= $startStageSort && $stageSort <= $finishStageSort);
		}
		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			$startStatusSort = \CCrmLead::GetStatusSort($startPhase);
			$finishStatusSort = \CCrmLead::GetStatusSort($finishPhase);

			$statusID = isset($options['STATUS_ID'])
				? $options['STATUS_ID']
				: (isset($entityData['STATUS_ID']) ? $entityData['STATUS_ID'] : '');
			$statusSort = \CCrmLead::GetStatusSort($statusID);

			return($statusSort >= $startStatusSort && $statusSort <= $finishStatusSort);
		}
		if($entityTypeID === \CCrmOwnerType::Contact)
		{
			// There are no statuses for contacts yet
			return true;
		}
		if($entityTypeID === \CCrmOwnerType::Company)
		{
			// There are no statuses for companies yet
			return true;
		}

		return false;
	}

	/**
	 * Return phrases for entity editor field configurator (BX.Crm.EntityFieldAttributeConfigurator)
	 *
	 * @return array
	 */
	public static function getCaptionsForEntityWithStages(): array
	{
		return [
			'REQUIRED_SHORT' => Main\Localization\Loc::getMessage('CRM_FIELD_ATTRIBUTE_MANAGER_STAGE_CAPTION_REQUIRED_SHORT'),
			'REQUIRED_FULL' => Main\Localization\Loc::getMessage('CRM_FIELD_ATTRIBUTE_MANAGER_STAGE_CAPTION_REQUIRED_FULL'),
			'GROUP_TYPE_GENERAL' => Main\Localization\Loc::getMessage('CRM_FIELD_ATTRIBUTE_MANAGER_STAGE_CAPTION_GROUP_TYPE_GENERAL'),
			'GROUP_TYPE_PIPELINE' => Main\Localization\Loc::getMessage('CRM_FIELD_ATTRIBUTE_MANAGER_STAGE_CAPTION_GROUP_TYPE_PIPELINE'),
			'GROUP_TYPE_JUNK' => Main\Localization\Loc::getMessage('CRM_FIELD_ATTRIBUTE_MANAGER_STAGE_CAPTION_GROUP_TYPE_JUNK')
		];
	}

	/**
	 * Adds information about attributes config from $attrConfigs to $fieldInfos.
	 * Returns array of fieldNames that required by this config.
	 *
	 * @param array $attrConfigs Config to get information from.
	 * @param array $fieldInfos Field parameters to add information to.
	 * @return string[]
	 */
	public static function prepareEditorFieldInfosWithAttributes(array $attrConfigs, array &$fieldInfos): array
	{
		$requiredByAttributeFieldNames = [];

		for ($i = 0, $length = count($fieldInfos); $i < $length; $i++)
		{
			$isPhaseDependent = static::isPhaseDependent();
			if (!$isPhaseDependent)
			{
				$fieldInfos[$i]['data']['isPhaseDependent'] = false;
			}

			$fieldName = $fieldInfos[$i]['name'];
			if(!isset($attrConfigs[$fieldName]))
			{
				continue;
			}

			$fieldInfos[$i]['data']['attrConfigs'] = $attrConfigs[$fieldName];

			if (!is_array($attrConfigs[$fieldName]) || empty($attrConfigs[$fieldName]))
			{
				continue;
			}

			$isRequiredByAttribute = false;
			$ready = false;
			$attrConfig = $attrConfigs[$fieldName];
			foreach ($attrConfig as $item)
			{
				if (
					is_array($item)
					&& isset($item['typeId'])
					&& $item['typeId'] === FieldAttributeType::REQUIRED
				)
				{
					if ($isPhaseDependent)
					{
						if (is_array($item['groups']))
						{
							foreach ($item['groups'] as $group)
							{
								if (is_array($group) && isset($group['phaseGroupTypeId'])
									&& $group['phaseGroupTypeId'] === FieldAttributePhaseGroupType::ALL)
								{
									$isRequiredByAttribute = true;
									$ready = true;
									break;
								}
							}
						}
					}
					else
					{
						$isRequiredByAttribute = true;
						$ready = true;
					}
					if ($ready)
					{
						break;
					}
				}
			}
			if ($isRequiredByAttribute)
			{
				if (!is_array($fieldInfos[$i]['data']))
				{
					$fieldInfos[$i]['data'] = [];
				}
				$fieldInfos[$i]['data']['isRequiredByAttribute'] = true;

				$requiredByAttributeFieldNames[] = $fieldName;
			}
		}

		return $requiredByAttributeFieldNames;
	}

	/**
	 * Return scope for $item.
	 *
	 * @param Crm\Item $item
	 * @return string
	 */
	public static function getItemConfigScope(Crm\Item $item): string
	{
		return static::resolveEntityScope(
			$item->getEntityTypeID(),
			$item->getId(),
			[
				'CATEGORY_ID' => $item->getCategoryId(),
			]
		);
	}

	/**
	 * Returns data from FieldAttributeTable.
	 *
	 * @param int $entityTypeId
	 * @param string $entityScope
	 * @param int|null $fieldOrigin
	 * @param int|null $typeId
	 * @return array
	 */
	public static function getList(
		int $entityTypeId,
		string $entityScope,
		?int $fieldOrigin = FieldOrigin::UNDEFINED,
		?int $typeId = FieldAttributeType::REQUIRED
	): array
	{
		$query = new Main\Entity\Query(Entity\FieldAttributeTable::getEntity());
		$query->addSelect('ENTITY_TYPE_ID');
		$query->addSelect('FIELD_NAME');
		$query->addSelect('START_PHASE');
		$query->addSelect('FINISH_PHASE');
		$query->addSelect('IS_CUSTOM_FIELD');

		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeId);
		$query->addFilter('=ENTITY_SCOPE', $entityScope);
		if($fieldOrigin > 0)
		{
			$query->addFilter('=IS_CUSTOM_FIELD', $fieldOrigin === FieldOrigin::CUSTOM ? 'Y' : 'N');
		}
		if($typeId > 0)
		{
			$query->addFilter('=TYPE_ID', $typeId);
		}

		return $query->exec()->fetchAll();
	}

	/**
	 * Return list of field names that matches specified configs and stages.
	 *
	 * @param array $fieldsData - Data that returns by self::getList() method.
	 * @param Crm\EO_Status_Collection $collection - Stages collection for which settings should be processed.
	 * @param string $currentStageId - Current stage identifier.
	 * @return string[]
	 */
	public static function processFieldsForStages(
		array $fieldsData,
		Crm\EO_Status_Collection $collection,
		string $currentStageId
	): array
	{
		$entityTypeId = null;
		$result = [];

		if (!static::isPhaseDependent())
		{
			return $result;
		}
		if(empty($fieldsData))
		{
			return $result;
		}
		$stages = [];
		foreach ($collection as $stage)
		{
			$stages[$stage->getStatusId()] = $stage;
		}
		$currentStageSort = isset($stages[$currentStageId]) ? $stages[$currentStageId]->getSort() : -1;
		foreach ($fieldsData as $fieldConfig)
		{
			//If Start Phase and Finish Phase are empty, then field is required always.
			if (empty($fieldConfig['START_PHASE']) && empty($fieldConfig['FINISH_PHASE']))
			{
				$result[] = $fieldConfig['FIELD_NAME'];
				continue;
			}

			$startStageSort = isset($stages[$fieldConfig['START_PHASE']]) ? $stages[$fieldConfig['START_PHASE']]->getSort() : -1;
			$finishStageSort = isset($stages[$fieldConfig['FINISH_PHASE']]) ? $stages[$fieldConfig['FINISH_PHASE']]->getSort() : -1;

			if ($currentStageSort >= $startStageSort && $currentStageSort <= $finishStageSort)
			{
				$result[] = $fieldConfig['FIELD_NAME'];
			}
		}

		return $result;
	}
}