<?php
namespace Bitrix\Crm\Conversion;

use Bitrix\Crm\Conversion\Entity\EntityConversionMapTable;
use Bitrix\Crm\Conversion\Entity\EO_EntityConversionMap;
use Bitrix\Crm\Item;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Result;

class ConversionManager
{
	/** @var array */
	protected static $entityTypeConversionMap = [
		\CCrmOwnerType::Lead => [
			\CCrmOwnerType::Deal, \CCrmOwnerType::Contact, \CCrmOwnerType::Company
		],
		\CCrmOwnerType::Deal => [
			\CCrmOwnerType::Quote, \CCrmOwnerType::Invoice
		],
		\CCrmOwnerType::Quote => [
			\CCrmOwnerType::Deal, \CCrmOwnerType::Invoice
		]
	];

	/** @var string[] */
	protected static $entityTypeToModuleDependencyMap = [
		\CCrmOwnerType::Invoice => 'sale',
	];

	/** @var int[] */
	protected static $sourcesCandidatesForDynamic = [
		\CCrmOwnerType::Deal, \CCrmOwnerType::Lead, \CCrmOwnerType::Quote,
	];

	/** @var int[] */
	protected static $destinationCandidatesForDynamic = [
		\CCrmOwnerType::Deal, \CCrmOwnerType::Quote,
	];

	/**
	 * dstEntityTypeId => array of sources
	 * @var int[][]
	 */
	protected static $sourceTypesCache = [];

	/**
	 * srcEntityTypeId => array of destinations
	 * @var int[][]
	 */
	protected static $destinationsTypesCache = [];

	/** @var EntityConversionMapTable */
	protected static $conversionMapTable = EntityConversionMapTable::class;

	public static function getConcernedFields($srcEntityTypeID, $srcFieldName)
	{
		$bindings = [];
		static::prepareBoundFields($srcEntityTypeID, $srcFieldName, $bindings);
		return array_values($bindings);
	}

	protected static function prepareBoundFields($srcEntityTypeID, $srcFieldName, array &$bindings)
	{
		foreach(static::getDestinationEntityTypeIDs($srcEntityTypeID) as $dstEntityTypeID)
		{
			//Protection against infinite loop
			if(isset($bindings[$dstEntityTypeID]))
			{
				continue;
			}

			$map = EntityConversionMap::load($srcEntityTypeID, $dstEntityTypeID);
			if(!$map)
			{
				continue;
			}

			foreach($map->getItems() as $item)
			{
				if($srcFieldName !== $item->getSourceField())
				{
					continue;
				}

				$dstFieldName = $item->getDestinationField();
				$bindings[$dstEntityTypeID] = [
					'ENTITY_TYPE_ID' => $dstEntityTypeID,
					'ENTITY_TYPE_NAME' => \CCrmOwnerType::ResolveName($dstEntityTypeID),
					'FIELD_NAME' => $dstFieldName
				];

				static::prepareBoundFields($dstEntityTypeID, $dstFieldName, $bindings);
				break;
			}
		}
	}

	public static function getParentalField($entityTypeID, $fieldName)
	{
		$resultField = [
			'ENTITY_TYPE_ID' => $entityTypeID,
			'ENTITY_TYPE_NAME' => \CCrmOwnerType::ResolveName($entityTypeID),
			'FIELD_NAME' => $fieldName
		];
		$traverseMap = [$fieldName => true];

		for(;;)
		{
			$field = null;
			$srcEntityTypeIDs = static::getSourceEntityTypeIDs($resultField['ENTITY_TYPE_ID']);
			foreach($srcEntityTypeIDs as $srcEntityTypeID)
			{
				$map = EntityConversionMap::load($srcEntityTypeID, $resultField['ENTITY_TYPE_ID']);
				if(!$map)
				{
					continue;
				}

				foreach($map->getItems() as $item)
				{
					if($resultField['FIELD_NAME'] === $item->getDestinationField())
					{
						$field = [
							'ENTITY_TYPE_ID' => $srcEntityTypeID,
							'ENTITY_TYPE_NAME' => \CCrmOwnerType::ResolveName($srcEntityTypeID),
							'FIELD_NAME' => $item->getSourceField()
						];
						break;
					}
				}

				if($field !== null)
				{
					break;
				}
			}

			if($field === null)
			{
				break;
			}

			//Protection against infinite loop
			if(isset($traverseMap[$field['FIELD_NAME']]))
			{
				break;
			}

			$resultField = $field;
			$traverseMap[$field['FIELD_NAME']] = true;
		}

		return $resultField;
	}

	public static function getSourceEntityTypeIDs($dstEntityTypeID)
	{
		if(!is_int($dstEntityTypeID))
		{
			$dstEntityTypeID = (int)$dstEntityTypeID;
		}

		$sourceTypes = [];
		foreach(static::$entityTypeConversionMap as $srcEntityTypeID => $dstEntityTypeIDs)
		{
			if(in_array($dstEntityTypeID, $dstEntityTypeIDs, true))
			{
				$sourceTypes[] = $srcEntityTypeID;
			}
		}

		$sourceTypes = array_unique(
			array_merge($sourceTypes, static::getSourceEntityTypeIDsFromTable($dstEntityTypeID))
		);

		return $sourceTypes;
	}

	public static function getDestinationEntityTypeIDs($srcEntityTypeID)
	{
		if(!is_int($srcEntityTypeID))
		{
			$srcEntityTypeID = (int)$srcEntityTypeID;
		}

		$destinationTypes = static::$entityTypeConversionMap[$srcEntityTypeID] ?? [];

		$destinationTypes = array_unique(
			array_merge($destinationTypes, static::getDestinationEntityTypeIDsFromTable($srcEntityTypeID))
		);

		return $destinationTypes;
	}

	protected static function getSourceEntityTypeIDsFromTable(int $dstEntityTypeID): array
	{
		if (!isset(static::$sourceTypesCache[$dstEntityTypeID]))
		{
			$collection = static::$conversionMapTable::getList([
				'select' => ['SRC_TYPE_ID'],
				'filter' => ['=DST_TYPE_ID' => $dstEntityTypeID],
			])->fetchCollection();

			static::$sourceTypesCache[$dstEntityTypeID] = $collection->getSrcTypeIdList();
		}

		return static::$sourceTypesCache[$dstEntityTypeID];
	}

	protected static function getDestinationEntityTypeIDsFromTable(int $srcEntityTypeID): array
	{
		if (!isset(static::$destinationsTypesCache[$srcEntityTypeID]))
		{
			$collection = static::$conversionMapTable::getList([
				'select' => ['DST_TYPE_ID'],
				'filter' => ['=SRC_TYPE_ID' => $srcEntityTypeID],
			])->fetchCollection();

			static::$destinationsTypesCache[$srcEntityTypeID] = $collection->getDstTypeIdList();
		}

		return static::$destinationsTypesCache[$srcEntityTypeID];
	}

	public static function clearTypesCache(): void
	{
		static::$sourceTypesCache = [];
		static::$destinationsTypesCache = [];
	}

	/**
	 * Returns an array of entityTypeId's of entities that could act as sources for the specified dynamic entity
	 *
	 * @param int $dynamicEntityTypeId
	 *
	 * @return int[]
	 */
	public static function getSourceCandidates(int $dynamicEntityTypeId): array
	{
		return array_merge(static::$sourcesCandidatesForDynamic, static::getDynamicCandidates($dynamicEntityTypeId));
	}

	/**
	 * Returns an array of entityTypeId's of entities that could act as destinations for the specified dynamic entity
	 *
	 * @param int $dynamicEntityTypeId
	 *
	 * @return int[]
	 */
	public static function getDestinationCandidates(int $dynamicEntityTypeId): array
	{
		return array_merge(static::$destinationCandidatesForDynamic, static::getDynamicCandidates($dynamicEntityTypeId));
	}

	protected static function getDynamicCandidates(int $dynamicEntityTypeId): array
	{
		$typesMap = Container::getInstance()->getDynamicTypesMap();
		$typesMap->load([
			'isLoadStages' => false,
			'isLoadCategories' => false,
		]);

		$dynamicTypes = [];
		foreach ($typesMap->getTypes() as $type)
		{
			if ($type->getEntityTypeId() === $dynamicEntityTypeId)
			{
				continue;
			}

			$dynamicTypes[] = $type->getEntityTypeId();
		}

		return $dynamicTypes;
	}

	public static function setSourceTypes(int $dynamicEntityTypeId, array $sourceTypes): void
	{
		static::validateEntityTypeId($dynamicEntityTypeId);

		$deletedSources = array_diff(static::getSourceEntityTypeIDs($dynamicEntityTypeId), $sourceTypes);
		foreach ($deletedSources as $deletedSourceType)
		{
			static::deleteMapItem($deletedSourceType, $dynamicEntityTypeId);
		}

		foreach ($sourceTypes as $sourceType)
		{
			static::validateEntityTypeId($sourceType);

			if (
				!static::isSourceExists($dynamicEntityTypeId, $sourceType)
				&& in_array($sourceType, static::getSourceCandidates($dynamicEntityTypeId), true)
			)
			{
				static::createMapItem($sourceType, $dynamicEntityTypeId);
			}
		}
	}

	public static function setDestinationTypes(int $dynamicEntityTypeId, array $destinationTypes): void
	{
		static::validateEntityTypeId($dynamicEntityTypeId);

		$deletedDestinations = array_diff(static::getDestinationEntityTypeIDs($dynamicEntityTypeId), $destinationTypes);
		foreach ($deletedDestinations as $deletedDestinationType)
		{
			static::deleteMapItem($dynamicEntityTypeId, $deletedDestinationType);
		}

		foreach ($destinationTypes as $destinationType)
		{
			static::validateEntityTypeId($destinationType);

			if (
				!static::isDestinationExists($dynamicEntityTypeId, $destinationType)
				&& in_array($destinationType, static::getDestinationCandidates($dynamicEntityTypeId), true)
			)
			{
				static::createMapItem($dynamicEntityTypeId, $destinationType);
			}
		}
	}

	protected static function validateEntityTypeId($entityTypeId): void
	{
		if (!is_int($entityTypeId) || !\CCrmOwnerType::IsDefined($entityTypeId))
		{
			throw new ArgumentException('The provided entity type id is invalid');
		}
	}

	/**
	 * Checks if the entity with the provided $entityTypeId has source entity with $srcEntityTypeId
	 *
	 * @param int $entityTypeId
	 * @param int $srcEntityTypeId
	 *
	 * @return bool
	 */
	public static function isSourceExists(int $entityTypeId, int $srcEntityTypeId): bool
	{
		return in_array($srcEntityTypeId, static::getSourceEntityTypeIDs($entityTypeId), true);
	}

	/**
	 * Checks if the entity with the provided $entityTypeId has destination entity with $dstEntityTypeId
	 *
	 * @param int $entityTypeId
	 * @param int $dstEntityTypeId
	 *
	 * @return bool
	 */
	public static function isDestinationExists(int $entityTypeId, int $dstEntityTypeId): bool
	{
		return in_array($dstEntityTypeId, static::getDestinationEntityTypeIDs($entityTypeId), true);
	}

	protected static function createMapItem(int $srcEntityTypeId, int $dstEntityTypeId): Result
	{
		$mapItem = static::$conversionMapTable::createObject();
		$mapItem->setSrcTypeId($srcEntityTypeId);
		$mapItem->setDstTypeId($dstEntityTypeId);

		return static::saveMapItem($mapItem);
	}

	/**
	 * Isolated in a separate method for testing purposes
	 *
	 * @param EO_EntityConversionMap $mapItem
	 *
	 * @return Result
	 */
	protected static function saveMapItem(EO_EntityConversionMap $mapItem): Result
	{
		return $mapItem->save();
	}

	/**
	 * Isolated in a separate method for testing purposes
	 *
	 * @param int $srcEntityTypeId
	 * @param int $dstEntityTypeId
	 *
	 * @return Result
	 * @throws \Exception
	 */
	protected static function deleteMapItem(int $srcEntityTypeId, int $dstEntityTypeId): Result
	{
		return static::$conversionMapTable::delete([
			'SRC_TYPE_ID' => $srcEntityTypeId,
			'DST_TYPE_ID' => $dstEntityTypeId,
		]);
	}

	/**
	 * @param Item $item
	 * @return bool[]
	 */
	public static function getConversionPermissions(Item $item): array
	{
		$userPermissions = Container::getInstance()->getUserPermissions();

		$canUpdateSourceItem = $userPermissions->canUpdateItem($item);

		$result = [];
		foreach (static::getDestinationEntityTypeIDs($item->getEntityTypeId()) as $destinationEntityTypeId)
		{
			$canAddDestinationItem = EntityAuthorization::checkCreatePermission($destinationEntityTypeId);
			/** @var string $entityTypeName */
			$entityTypeName = mb_strtolower(\CCrmOwnerType::ResolveName($destinationEntityTypeId));

			$result[$entityTypeName] = (
				$canUpdateSourceItem
				&& $canAddDestinationItem
				&& static::checkDependencies($destinationEntityTypeId)
			);
		}

		return $result;
	}

	protected static function checkDependencies(int $dstEntityTypeId): bool
	{
		$moduleToCheck = static::$entityTypeToModuleDependencyMap[$dstEntityTypeId] ?? null;
		if (is_null($moduleToCheck))
		{
			return true;
		}

		return ModuleManager::isModuleInstalled($moduleToCheck);
	}

	public static function isConversionPossible(Item $item): bool
	{
		$permissions = static::getConversionPermissions($item);

		$permittedDestinationEntities = array_filter($permissions);

		return !empty($permittedDestinationEntities);
	}

	public static function getConfig(int $entityTypeId): ?EntityConversionConfig
	{
		$configClassName = static::getConfigClass($entityTypeId);
		if (is_null($configClassName))
		{
			return null;
		}

		$config = $configClassName::load();
		if (is_null($config))
		{
			$config = $configClassName::getDefault();
		}

		return $config;
	}

	public static function getConfigFromJavaScript(int $entityTypeId, array $configParams): ?EntityConversionConfig
	{
		$configClassName = static::getConfigClass($entityTypeId);
		if (is_null($configClassName))
		{
			return null;
		}

		/** @var EntityConversionConfig $config */
		$config = new $configClassName();
		$config->fromJavaScript($configParams);

		return $config;
	}

	public static function getWizard(int $entityTypeId, int $entityId, EntityConversionConfig $config): ?EntityConversionWizard
	{
		$wizardClass = static::getWizardClass($entityTypeId);
		if (is_null($wizardClass))
		{
			return null;
		}

		/** @var EntityConversionWizard $wizard */
		$wizard = new $wizardClass($entityId, $config);

		if(\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled())
		{
			$wizard->setSliderEnabled(true);
		}

		$url = $config->getOriginUrl();
		if (!is_null($url))
		{
			$wizard->setOriginUrl($url->getUri());
		}

		return $wizard;
	}

	public static function getCurrentSchemeId(int $srcEntityTypeId): ?int
	{
		$configClass = static::getConfigClass($srcEntityTypeId);
		if (is_null($configClass))
		{
			return null;
		}

		return $configClass::getCurrentSchemeID();
	}

	/**
	 * @param int $entityTypeId
	 *
	 * @return string|null|DealConversionScheme|LeadConversionScheme|OrderConversionScheme|QuoteConversionScheme
	 */
	public static function getSchemeClass(int $entityTypeId): ?string
	{
		$map = [
			\CCrmOwnerType::Deal => DealConversionScheme::class,
			\CCrmOwnerType::Lead => LeadConversionScheme::class,
			\CCrmOwnerType::Order => OrderConversionScheme::class,
			\CCrmOwnerType::Quote => QuoteConversionScheme::class,
		];

		return $map[$entityTypeId] ?? null;
	}

	/**
	 * @param int $entityTypeId
	 *
	 * @return EntityConversionConfig|string|null
	 */
	protected static function getConfigClass(int $entityTypeId): ?string
	{
		$map = [
			\CCrmOwnerType::Deal => DealConversionConfig::class,
			\CCrmOwnerType::Order => OrderConversionConfig::class,
			\CCrmOwnerType::Quote => QuoteConversionConfig::class,
		];

		return $map[$entityTypeId] ?? null;
	}

	/**
	 * @param int $entityTypeId
	 *
	 * @return EntityConversionWizard|string|null
	 */
	protected static function getWizardClass(int $entityTypeId): ?string
	{
		$map = [
			\CCrmOwnerType::Lead => LeadConversionWizard::class,
			\CCrmOwnerType::Deal => DealConversionWizard::class,
			\CCrmOwnerType::Quote => QuoteConversionWizard::class,
		];

		return $map[$entityTypeId] ?? null;
	}
}