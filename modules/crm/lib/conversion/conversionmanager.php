<?php
namespace Bitrix\Crm\Conversion;

use Bitrix\Crm\Conversion\Entity\EntityConversionMapTable;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\InvoiceSettings;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Request;

class ConversionManager
{
	/** @var string[] */
	protected static $entityTypeToModuleDependencyMap = [
		\CCrmOwnerType::Invoice => 'sale',
	];

	/** @var EntityConversionMapTable */
	protected static $conversionMapTable = EntityConversionMapTable::class;

	protected static $configs = [];

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

	/**
	 * @param $dstEntityTypeID
	 * @return int[]
	 */
	public static function getSourceEntityTypeIDs($dstEntityTypeID)
	{
		$dstEntityTypeID = (int)$dstEntityTypeID;

		$invoiceSettings = InvoiceSettings::getCurrent();

		$sourceTypes = [];
		$relations = Container::getInstance()->getRelationManager()->getParentRelations($dstEntityTypeID);
		foreach ($relations as $relation)
		{
			if ($relation->getParentEntityTypeId() === \CCrmOwnerType::Invoice && !$invoiceSettings->isOldInvoicesEnabled())
			{
				continue;
			}
			if (
				$relation->getParentEntityTypeId() === \CCrmOwnerType::SmartInvoice
				&& !$invoiceSettings->isSmartInvoiceEnabled()
			)
			{
				continue;
			}

			if ($relation->getSettings()->isConversion())
			{
				$sourceTypes[] = $relation->getParentEntityTypeId();
			}
		}

		return $sourceTypes;
	}

	/**
	 * @param $srcEntityTypeID
	 * @return int[]
	 */
	public static function getDestinationEntityTypeIDs($srcEntityTypeID)
	{
		$srcEntityTypeID = (int)$srcEntityTypeID;

		$invoiceSettings = InvoiceSettings::getCurrent();

		$destinationTypes = [];
		$relations = Container::getInstance()->getRelationManager()->getChildRelations($srcEntityTypeID);
		foreach ($relations as $relation)
		{
			if ($relation->getSettings()->isConversion())
			{
				if ($relation->getChildEntityTypeId() === \CCrmOwnerType::Invoice && !$invoiceSettings->isOldInvoicesEnabled())
				{
					continue;
				}
				if (
					$relation->getChildEntityTypeId() === \CCrmOwnerType::SmartInvoice
					&& !$invoiceSettings->isSmartInvoiceEnabled()
				)
				{
					continue;
				}

				$destinationTypes[] = $relation->getChildEntityTypeId();
			}
		}

		return $destinationTypes;
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
		if (!isset(static::$configs[$entityTypeId]))
		{
			$default = static::getDefaultConfig($entityTypeId);
			$saved = EntityConversionConfig::loadByEntityTypeId($entityTypeId);
			if ($saved)
			{
				foreach ($default->getItems() as $item)
				{
					//ensure that config always has all relevant items
					if (!$saved->getItem($item->getEntityTypeID()))
					{
						$saved->addItem($item);
					}
				}
			}

			static::$configs[$entityTypeId] = $saved ?? $default;
		}

		return static::$configs[$entityTypeId];
	}

	public static function getDefaultConfig(int $srcEntityTypeId): EntityConversionConfig
	{
		$config = EntityConversionConfig::create($srcEntityTypeId);

		$wasDefaultDestinationSet = false;
		foreach (static::getDestinationEntityTypeIDs($srcEntityTypeId) as $dstEntityTypeId)
		{
			$configItem = new EntityConversionConfigItem($dstEntityTypeId);

			if (!$wasDefaultDestinationSet)
			{
				//todo maybe remove default destination and allow client code decide fully on its own?
				$configItem->setActive(true);
				$configItem->enableSynchronization(true);

				$wasDefaultDestinationSet = true;
			}

			$config->addItem($configItem);
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

	public static function getWizard(
		int $entityTypeId,
		int $entityId,
		EntityConversionConfig $config
	): ?EntityConversionWizard
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

	public static function loadWizard(ItemIdentifier $source): ?EntityConversionWizard
	{
		return EntityConversionWizard::loadByIdentifier($source);
	}

	/**
	 * Extracts relevant data from request and tries to load a wizard by it
	 *
	 * @param Request $request
	 * @return EntityConversionWizard|null
	 */
	public static function loadWizardByRequest(Request $request): ?EntityConversionWizard
	{
		$srcEntityTypeId = (int)$request->get(EntityConversionWizard::QUERY_PARAM_SOURCE_TYPE_ID);
		$entityId = (int)$request->get(EntityConversionWizard::QUERY_PARAM_SOURCE_ID);

		if ($entityId > 0 && \CCrmOwnerType::IsDefined($srcEntityTypeId))
		{
			return static::loadWizard(new ItemIdentifier($srcEntityTypeId, $entityId));
		}

		return null;
	}

	public static function loadWizardByParams(array $params): ?EntityConversionWizard
	{
		if(empty($params))
		{
			return null;
		}

		$entityId = (int)($params['ENTITY_ID'] ?? $params['entityId'] ?? 0);
		$entityTypeId = (int)($params['ENTITY_TYPE_ID'] ?? $params['entityTypeId'] ?? 0);

		if ($entityId > 0 && \CCrmOwnerType::IsDefined($entityTypeId))
		{
			return static::loadWizard(new ItemIdentifier($entityTypeId, $entityId));
		}

		return null;
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
	public static function getWizardClass(int $entityTypeId): ?string
	{
		$map = [
			\CCrmOwnerType::Lead => LeadConversionWizard::class,
			\CCrmOwnerType::Deal => DealConversionWizard::class,
			\CCrmOwnerType::Quote => QuoteConversionWizard::class,
		];

		return $map[$entityTypeId] ?? null;
	}
}
