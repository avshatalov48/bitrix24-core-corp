<?php

namespace Bitrix\Crm\Conversion;

use Bitrix\Crm;
use Bitrix\Crm\Conversion\Exception\AutocreationDisabledException;
use Bitrix\Crm\Conversion\Exception\CreationFailedException;
use Bitrix\Crm\Conversion\Exception\DestinationHasWorkflowsException;
use Bitrix\Crm\Conversion\Exception\DestinationItemNotFoundException;
use Bitrix\Crm\Conversion\Exception\NoActiveDestinationsException;
use Bitrix\Crm\Conversion\Exception\SourceHasParentException;
use Bitrix\Crm\Conversion\Exception\SourceItemNotFoundException;
use Bitrix\Crm\Integration\Channel\DealChannelBinding;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Requisite;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Operation;
use Bitrix\Crm\Settings\ConversionSettings;
use Bitrix\Crm\Tracking;
use Bitrix\Main;

class EntityConverter
{
	protected const PHASE_NEW_API = 100;

	/** @var int */
	protected $currentPhase = 0;
	/** @var array */
	protected $contextData = [];
	/** @var array */
	protected $resultData = [];
	/** @var \CCrmPerms|null  */
	protected $userPermissions = null;
	/** @var bool */
	private $enableUserFieldCheck = true;
	/** @var bool */
	private $enableBizProcCheck = true;
	/** @var bool */
	private $skipBizProcAutoStart = false;

	/** @var EntityConversionConfig */
	protected $config;

	/** @var Factory */
	private $sourceFactory;

	/** @var Item */
	private $sourceItem;
	/** @var int */
	protected $entityID = 0;

	/** @var bool */
	private $isFinished = false;
	private $isInitialized = false;

	protected function __construct(EntityConversionConfig $config)
	{
		$this->config = $config;
	}

	public static function create(
		Factory $srcFactory,
		int $entityID,
		EntityConversionConfig $config
	): self
	{
		$converter = new self($config);

		$converter->sourceFactory = $srcFactory;
		$converter->entityID = $entityID;

		return $converter;
	}

	public static function createFromExternalized(array $externalizedParams): ?self
	{
		$config = EntityConversionConfig::createFromExternalized((array)($externalizedParams['config'] ?? null));
		if (!$config)
		{
			return null;
		}

		$converter = new self($config);
		$converter->internalize($externalizedParams);

		return self::checkInstanceIntegrity($converter) ? $converter : null;
	}

	/**
	 * Check if an instance of converter was constructed completely
	 *
	 * @param EntityConverter $converter
	 * @return bool
	 */
	private static function checkInstanceIntegrity(self $converter): bool
	{
		return (
			($converter->sourceFactory instanceof Factory)
			&& is_int($converter->entityID)
			&& ($converter->config instanceof EntityConversionConfig)
		);
	}

	public function initialize()
	{
		$this->initializeInner();
	}

	/**
	 * @todo move code to initialize after refactoring
	 *
	 * @throws SourceItemNotFoundException
	 */
	private function initializeInner(): void
	{
		if ($this->isInitialized)
		{
			return;
		}

		$this->sourceItem = $this->sourceFactory->getItem($this->entityID);
		if (!$this->sourceItem)
		{
			throw new SourceItemNotFoundException($this->sourceFactory->getEntityTypeId(), $this->entityID);
		}

		$this->isInitialized = true;

		//user permissions check is performed in operation, if needed
	}

	final public function convert(): void
	{
		$items = $this->config->getActiveItems();
		if (empty($items))
		{
			throw new NoActiveDestinationsException($this->sourceFactory->getEntityTypeId());
		}

		foreach ($items as $configItem)
		{
			$this->convertByConfigItem($configItem);
		}

		$this->onFinalizationPhase();

		$this->isFinished = true;
	}

	private function convertByConfigItem(EntityConversionConfigItem $configItem): void
	{
		$this->initializeInner();

		$destinationFactory = Container::getInstance()->getFactory($configItem->getEntityTypeID());
		if (!$destinationFactory)
		{
			throw new Main\ObjectNotFoundException(
				'Could not find factory for destination type with ID = ' . $configItem->getEntityTypeID()
			);
		}

		$destinationId = (int)($this->contextData[$destinationFactory->getEntityName()] ?? 0);

		if ($destinationId > 0)
		{
			$destinationItem = $destinationFactory->getItem($destinationId);
			if (!$destinationItem)
			{
				throw new DestinationItemNotFoundException($destinationFactory->getEntityTypeId(), $destinationId);
			}
		}
		else
		{
			$destinationItem = $this->createDestinationItem($destinationFactory);
		}

		Container::getInstance()->getRelationManager()->bindItems(
			ItemIdentifier::createByItem($this->sourceItem),
			ItemIdentifier::createByItem($destinationItem),
		);

		$this->resultData[$destinationFactory->getEntityName()] = $destinationItem->getId();
	}

	private function createDestinationItem(Factory $destinationFactory): Item
	{
		if (!ConversionSettings::getCurrent()->isAutocreationEnabled())
		{
			throw new AutocreationDisabledException($destinationFactory->getEntityTypeId());
		}

		if (
			$this->isBizProcCheckEnabled()
			&& \CCrmBizProcHelper::HasParameterizedAutoWorkflows(
				$destinationFactory->getEntityTypeId(),
				\CCrmBizProcEventType::Create,
			)
		)
		{
			throw new DestinationHasWorkflowsException($destinationFactory->getEntityTypeId());
		}

		$relationManager = Container::getInstance()->getRelationManager();
		$sourceParents = $relationManager->getParentElements(ItemIdentifier::createByItem($this->sourceItem));
		foreach ($sourceParents as $sourceParent)
		{
			if ($sourceParent->getEntityTypeId() === $destinationFactory->getEntityTypeId())
			{
				throw new SourceHasParentException(
					$this->sourceFactory->getEntityTypeId(),
					$destinationFactory->getEntityTypeId(),
				);
			}
		}

		$destinationItem = $destinationFactory->createItem();

		$this->fillDestinationItemWithDataFromSourceItem($destinationItem);

		$result = $this->getConfiguredAddOperation($destinationFactory, $destinationItem)->launch();
		if (!$result->isSuccess())
		{
			throw new CreationFailedException(
				$destinationFactory->getEntityTypeId(),
				implode(PHP_EOL, $result->getErrorMessages()),
			);
		}

		if ($destinationFactory->isClientEnabled() || $destinationFactory->isMyCompanyEnabled())
		{
			Requisite\EntityLink::copyRequisiteLink($this->sourceItem, $destinationItem);
		}

		return $destinationItem;
	}

	public function fillDestinationItemWithDataFromSourceItem(
		Item  $destinationItem,
		array $sourceCollectionFieldNames = []
	): void
	{
		$this->initialize();

		$configItem = $this->config->getItem($destinationItem->getEntityTypeId());
		if (!$configItem || !$configItem->isActive())
		{
			return;
		}

		$destinationItem->set(Crm\Service\ParentFieldManager::getParentFieldName($this->sourceItem->getEntityTypeId()), $this->sourceItem->getId());

		$map = Container::getInstance()->getConversionMapper()->getMap(
			new Crm\RelationIdentifier($this->sourceFactory->getEntityTypeId(), $destinationItem->getEntityTypeId()),
		);

		$destinationFactory = Container::getInstance()->getFactory($destinationItem->getEntityTypeId());
		if (!$destinationFactory)
		{
			return;
		}

		foreach ($map->getItems() as $mapItem)
		{
			$sourceField = $this->sourceFactory->getFieldsCollection()->getField($mapItem->getSourceField());
			$dstField = $destinationFactory->getFieldsCollection()->getField($mapItem->getDestinationField());

			if (
				!empty($sourceCollectionFieldNames)
				&& (!$sourceField || !$dstField
					|| !in_array($sourceField->getName(), $sourceCollectionFieldNames, true))
			)
			{
				continue;
			}

			$sourceValue = $this->sourceItem->get($mapItem->getSourceField());

			//todo move this code to mapper/merger
			if ($sourceField && $sourceField->isUserField() && $dstField && $dstField->isUserField())
			{
				$srcValues = [$sourceField->getName() => $sourceValue];
				$dstValues = [];

				EntityConversionMapper::mapUserField(
					$this->sourceFactory->getEntityTypeId(),
					$sourceField->getName(),
					$srcValues,
					$destinationFactory->getEntityTypeId(),
					$dstField->getName(),
					$dstValues,
				);

				$sourceValue = $dstValues[$dstField->getName()] ?? $sourceValue;

				if (is_array($sourceValue) && $dstField->isFileUserField())
				{
					$fileUploader = Container::getInstance()->getFileUploader();

					if ($dstField->isMultiple())
					{
						foreach ($sourceValue as &$singleFileArray)
						{
							$singleFileArray = $fileUploader->saveFileTemporary($dstField, $singleFileArray);
						}
						unset($singleFileArray);
					}
					else
					{
						$sourceValue = $fileUploader->saveFileTemporary($dstField, $sourceValue);
					}
				}
			}
			//todo new hierarchy of migrators that will polymorphicaly transform field values from source to dst?
			elseif ($sourceValue instanceof Crm\ProductRowCollection)
			{
				$sourceValue = $this->convertProductRows($sourceValue, $destinationFactory);
			}

			//todo ensure that all fields work with unnamed set
			$destinationItem->set($mapItem->getDestinationField(), $sourceValue);
		}

		if ($destinationItem->hasField(Item::FIELD_NAME_PRODUCTS))
		{
			$srcHasProducts = false;
			if ($this->sourceItem->hasField(Item::FIELD_NAME_PRODUCTS))
			{
				$srcHasProducts = $this->sourceItem->getProductRows() && (count($this->sourceItem->getProductRows()) > 0);
			}
			$dstHasProducts = $destinationItem->getProductRows() && count($destinationItem->getProductRows()) > 0;

			//todo for some interdependent fields real current values of fields affect which ones we have to transfer
			// to dst and which we have to skip
			// Do i still need to map fields in some mapper and return fully prepared dst values?
			if ($srcHasProducts && !$dstHasProducts && !$destinationItem->getIsManualOpportunity() && $destinationItem->getOpportunity() > 0)
			{
				//force opportunity recalculation. Current value is set based on source product rows,
				// but no rows were transferred to dst
				$destinationItem->setOpportunity($destinationItem->getDefaultValue(Item::FIELD_NAME_OPPORTUNITY));
			}
		}

		$categoryId = $configItem->getInitData()['categoryId'] ?? null;

		if (!is_null($categoryId) && $destinationItem->isCategoriesSupported())
		{
			$destinationItem->setCategoryId($categoryId);
		}

		if (isset($this->contextData['RESPONSIBLE_ID']) && $destinationItem->hasField(Item::FIELD_NAME_ASSIGNED))
		{
			$destinationItem->setAssignedById($this->contextData['RESPONSIBLE_ID']);
		}
	}

	/**
	 * @param Crm\ProductRowCollection $srcProductRows
	 * @param Factory $destinationFactory
	 * @return Crm\ProductRow[]
	 */
	private function convertProductRows(Crm\ProductRowCollection $srcProductRows, Factory $destinationFactory): array
	{
		$sourceItemIdentifier = ItemIdentifier::createByItem($this->sourceItem);
		$currentDirection = new Crm\RelationIdentifier(
			$this->sourceItem->getEntityTypeId(),
			$destinationFactory->getEntityTypeId(),
		);
		$relation = Container::getInstance()->getRelationManager()->getRelation($currentDirection);
		if (!$relation)
		{
			throw new Main\InvalidOperationException('Relation for conversion direction not found: ' . $currentDirection);
		}

		$previousDstItemIds = [];
		foreach ($relation->getChildElements($sourceItemIdentifier) as $sourceChild)
		{
			if ($sourceChild->getEntityTypeId() === $destinationFactory->getEntityTypeId())
			{
				$previousDstItemIds[] = $sourceChild->getEntityId();
			}
		}

		/** @var array[][] $alreadyConvertedProductRows */
		$alreadyConvertedProductRows = [];
		if (!empty($previousDstItemIds))
		{
			$previousDstItems = $destinationFactory->getItems([
				'select' => [Item::FIELD_NAME_PRODUCTS],
				'filter' => [
					'@' . Item::FIELD_NAME_ID => $previousDstItemIds,
				],
			]);

			foreach ($previousDstItems as $previousDstItem)
			{
				if ($previousDstItem->getProductRows())
				{
					$alreadyConvertedProductRows[] = $previousDstItem->getProductRows()->toArray();
				}
			}
		}

		$notConvertedProductRowsArrays = \CCrmProductRow::GetDiff([$srcProductRows->toArray()], $alreadyConvertedProductRows);

		$notConvertedProductRows = [];
		foreach ($notConvertedProductRowsArrays as $notConvertedProductRowsArray)
		{
			$notConvertedProductRows[] = Crm\ProductRow::createFromArray($notConvertedProductRowsArray);
		}

		return $notConvertedProductRows;
	}

	private function getConfiguredAddOperation(Factory $destinationFactory, Item $destinationItem): Operation\Add
	{
		$context = $this->config->getContext();
		if (!$context)
		{
			//for compatibility
			$userId = $this->contextData['USER_ID'] ?? null;
			if (is_numeric($userId))
			{
				$context = new Crm\Service\Context();
				$context->setUserId((int)$userId);
			}
			else
			{
				$context = Container::getInstance()->getContext();
			}
		}

		$operation = $destinationFactory->getAddOperation($destinationItem, $context);

		if (!$this->config->isPermissionCheckEnabled())
		{
			$operation->disableCheckAccess();
		}

		if (!$this->isUserFieldCheckEnabled())
		{
			$operation->disableCheckFields();
		}

		if ($this->shouldSkipBizProcAutoStart())
		{
			$operation->disableBizProc();
		}

		return $operation;
	}

	//region Deprecated
	//todo mark all methods as deprecated and give them default implementation for compatibility
	//todo move all really used methods out of this region, only truly deprecated should remain here

	/**
	 * @deprecated Used only for maintaining backwards compatibility
	 * @param Factory $sourceFactory
	 * @return $this
	 */
	protected function setSourceFactory(Factory $sourceFactory): self
	{
		$this->sourceFactory = $sourceFactory;

		return $this;
	}

	//region Access to member fields
	/**
	 * Get source entity type ID
	 *
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return $this->sourceFactory->getEntityTypeId();
	}

	/**
	 * Get source entity ID
	 *
	 * @return int
	 */
	public function getEntityID()
	{
		return $this->entityID;
	}

	/**
	 * Set source entity ID.
	 *
	 * @param int $entityID
	 * @return void
	 */
	public function setEntityID($entityID)
	{
		$this->entityID = (int)$entityID;
	}

	/**
	 * Get current converter phase.
	 * @return int
	 */
	public function getCurrentPhase()
	{
		return $this->currentPhase;
	}
	/**
	 * Check if current phase is final.
	 * @return bool
	 */
	public function isFinished()
	{
		return $this->isFinished;
	}
	/**
	 * Get conversion configuration by entity type ID.
	 * @param int $entityTypeID Entity Type ID.
	 * @return EntityConversionConfigItem|null
	 */
	public function getEntityConfig($entityTypeID)
	{
		return $this->config->getItem($entityTypeID);
	}
	/**
	 * Get converter context data.
	 * @return array
	 */
	public function getContextData()
	{
		return $this->contextData;
	}
	/**
	 * Set converter context data.
	 * @param array $contextData Converter context data.
	 * @return void
	 */
	public function setContextData(array $contextData)
	{
		$this->contextData = $contextData;
	}
	/**
	 * Get converter result data.
	 * @return array
	 */
	public function getResultData()
	{
		return $this->resultData;
	}
	/**
	 * Get current user ID.
	 * @return int
	 */
	public function getUserID()
	{
		return \CCrmSecurityHelper::GetCurrentUserID();
	}
	/**
	 * Get current user permissions
	 * @return \CCrmPerms
	 */
	protected function getUserPermissions()
	{
		if($this->userPermissions === null)
		{
			$this->userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		}

		return $this->userPermissions;
	}
	/**
	 * Get converter configuration
	 * @return EntityConversionConfig
	 */
	public function getConfig()
	{
		return $this->config;
	}
	/**
	 * Check if User Field check is enabled.
	 * User Field checking is performed during a creation and update operation. It is enabled by default.
	 * @return bool
	 */
	public function isUserFieldCheckEnabled()
	{
		return $this->enableUserFieldCheck;
	}
	/**
	 * Enable/disable User Field check.
	 * User Field checking is performed during a creation and update operation.
	 * @param bool $enable Flag of enabling User Field checking.
	 */
	public function enableUserFieldCheck($enable)
	{
		$this->enableUserFieldCheck = $enable;
	}
	/**
	 * Check if checking for parametrized business process is enabled.
	 * Checking is performed before a creation operation. It is enabled by default.
	 * @return bool
	 */
	public function isBizProcCheckEnabled()
	{
		return $this->enableBizProcCheck;
	}
	/**
	 * Enable/disable checking for parametrized business process.
	 * Checking is performed before a creation operation.
	 * @param bool $enable Flag of enabling User Field checking.
	 */
	public function enableBizProcCheck($enable)
	{
		$this->enableBizProcCheck = $enable;
	}

	/**
	 * Check should auto start BP after update.
	 * @return bool
	 */
	public function shouldSkipBizProcAutoStart(): bool
	{
		return $this->skipBizProcAutoStart;
	}
	/**
	 * Enable/disable auto start BP after update
	 * @param bool $enable Flag of enabling User Field checking.
	 */
	public function setSkipBizProcAutoStart(bool $enable)
	{
		$this->skipBizProcAutoStart = $enable;
	}

	//endregion
	/**
	 * Try to execute current conversion phase.
	 * @return bool
	 */
	public function executePhase()
	{
		if ($this->currentPhase === static::PHASE_NEW_API)
		{
			foreach ($this->config->getActiveItems() as $item)
			{
				if ($this->isUseNewApi($item))
				{
					$this->convertByConfigItem($item);
				}
			}

			return true;
		}

		return false;
	}

	protected function isUseNewApi(EntityConversionConfigItem $configItem): bool
	{
		return \CCrmOwnerType::isUseDynamicTypeBasedApproach($configItem->getEntityTypeID());
	}

	/**
	 * @todo make so it simply returns false after complete refactoring
	 */
	public function moveToNextPhase()
	{
		if ($this->currentPhase === static::PHASE_NEW_API)
		{
			$this->currentPhase = 0;
		}
	}

	/**
	 * @deprecated
	 * @todo remove after refactoring
	 */
	protected function determineStartingPhase(): void
	{
		foreach ($this->config->getActiveItems() as $item)
		{
			if ($this->isUseNewApi($item))
			{
				$this->currentPhase = static::PHASE_NEW_API;

				return;
			}
		}

		$isIntermediate = ($this->currentPhase === 0);
		if ($isIntermediate)
		{
			$this->currentPhase = 1;
		}
	}

	/**
	 * Map entity fields to specified type.
	 * @param int $entityTypeID Entity type ID.
	 * @param array|null $options Mapping options.
	 * @return array
	 */
	public function mapEntityFields($entityTypeID, array $options = null)
	{
		$map = Container::getInstance()->getConversionMapper()->getMap(
			new Crm\RelationIdentifier($this->sourceFactory->getEntityTypeId(), (int)$entityTypeID),
		);

		//todo call it in constructor?
		try
		{
			$this->initialize();
		}
		catch (SourceItemNotFoundException $exception)
		{
			return [];
		}

		$fields = [];
		foreach ($map->getItems() as $mapItem)
		{
			$fields[$mapItem->getDestinationField()] = $this->sourceItem->get($mapItem->getSourceField());

			if ($mapItem->getSourceField() === Item::FIELD_NAME_PRODUCTS)
			{
				//todo old api expects to find array of arrays in this case. Mark "special" fields in a map somehow?
				$fields[$mapItem->getDestinationField()] =
					$this->sourceItem->getProductRows() ? $this->sourceItem->getProductRows()->toArray() : []
				;
			}
		}

		return $fields;
	}
	//region Externalization/Internalization
	/**
	 * Externalize converter settings
	 * @return array
	 */
	public function externalize()
	{
		$params = [
			'config' => $this->config->externalize(),
			'srcEntityTypeId' => $this->sourceFactory->getEntityTypeId(),
			'entityId' => $this->entityID,
			'currentPhase' => $this->currentPhase,
			'resultData' => $this->resultData,
		];
		$this->doExternalize($params);
		return $params;
	}

	protected function doExternalize(array &$params)
	{
	}

	/**
	 * Internalize converter settings.
	 * @param array $params Income parameters.
	 * @return void
	 */
	public function internalize(array $params)
	{
		if(isset($params['config']) && is_array($params['config']))
		{
			$this->config->internalize($params['config']);
		}

		if (isset($params['srcEntityTypeId']) && \CCrmOwnerType::IsDefined($params['srcEntityTypeId']))
		{
			$this->sourceFactory = Container::getInstance()->getFactory((int)$params['srcEntityTypeId']);
		}

		if(isset($params['entityId']))
		{
			$this->entityID = (int)$params['entityId'];
		}

		if(isset($params['currentPhase']))
		{
			$this->currentPhase = (int)$params['currentPhase'];
		}

		if(isset($params['resultData']) && is_array($params['resultData']))
		{
			$this->resultData = $params['resultData'];
		}

		$this->doInternalize($params);
	}

	protected function doInternalize(array $params)
	{
	}
	//endregion
	//region Misc.
	/**
	 * Get Supported Destination Types
	 * @return array
	 */
	public function getSupportedDestinationTypeIDs()
	{
		$entityTypeIds = [];

		foreach ($this->config->getItems() as $item)
		{
			$entityTypeIds[] = $item->getEntityTypeID();
		}

		return $entityTypeIds;
	}
	/**
	 * Get deal category IDs are allowed to use in converter
	 * @param \CCrmPerms|null $permissions
	 * @return array
	 */
	public static function getPermittedDealCategoryIDs(\CCrmPerms $permissions = null)
	{
		return \CAllCrmDeal::GetPermittedToCreateCategoryIDs($permissions);
	}

	public static function getDestinationEntityID($entityTypeName, array $data)
	{
		return isset($data[$entityTypeName]) ? (int)$data[$entityTypeName] : 0;
	}

	public static function setDestinationEntityID($entityTypeName, $entityID, array &$data, array $options = null)
	{
		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$data[$entityTypeName] = $entityID;
		if(isset($options['isNew']) && $options['isNew'])
		{
			$data["IS_RECENT_{$entityTypeName}"] = true;
		}
	}

	public static function isNewDestinationEntity($entityTypeName, $entityID, array $data)
	{
		$isNewKeyName = "IS_RECENT_{$entityTypeName}";
		return ($entityID > 0
			&& isset($data[$entityTypeName])
			&& $data[$entityTypeName] == $entityID
			&& isset($data[$isNewKeyName]) && $data[$isNewKeyName]
		);
	}

	/**
	 * Delete specified entity.
	 * @param int $entityTypeID Entity Type ID.
	 * @param int $entityID Entity ID.
	 */
	protected function removeEntity($entityTypeID, $entityID)
	{
	}

	/**
	 * Remove recently created entities that were created by converter.
	 * @return void
	 */
	public function undo()
	{
		foreach($this->getSupportedDestinationTypeIDs() as $entityTypeID)
		{
			$this->removeRecentEntity($entityTypeID);
		}
		$this->currentPhase = 0;
	}

	protected function removeRecentEntity($entityTypeID)
	{
		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		$entityID = isset($this->resultData[$entityTypeName]) ? $this->resultData[$entityTypeName] : 0;
		if($entityID <= 0)
		{
			return;
		}

		$isNewKeyName = "IS_RECENT_{$entityTypeName}";
		if(!(isset($this->resultData[$isNewKeyName]) && $this->resultData[$isNewKeyName]))
		{
			return;
		}

		$this->removeEntity($entityTypeID, $entityID);
		unset($this->resultData[$entityTypeName], $this->resultData[$isNewKeyName]);
	}

	//endregion

	//region Permissions
	/**
	 * Check permission for CREATE operation.
	 * @param string $entityTypeName EntityTypeName.
	 * @param EntityConversionConfigItem $config Entity configuration.
	 * @return bool
	 */
	protected function checkCreatePermission($entityTypeName, EntityConversionConfigItem $config = null)
	{
		if(!$this->config->isPermissionCheckEnabled())
		{
			return true;
		}

		/** @var \CCrmPerms $permissions */
		$permissions = $this->getUserPermissions();

		if($entityTypeName === \CCrmOwnerType::CompanyName)
		{
			return \CCrmCompany::CheckCreatePermission($permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::ContactName)
		{
			return \CCrmContact::CheckCreatePermission($permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::DealName)
		{
			$initData = $config !== null ? $config->getInitData() : null;
			$categoryID = is_array($initData) && isset($initData['categoryId'])
				? max((int)$initData['categoryId'], 0) : 0;
			return \CCrmDeal::CheckCreatePermission($permissions, $categoryID);
		}
		elseif($entityTypeName === \CCrmOwnerType::InvoiceName)
		{
			return \CCrmInvoice::CheckCreatePermission($permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::QuoteName)
		{
			return \CCrmQuote::CheckCreatePermission($permissions);
		}

		return \CCrmAuthorizationHelper::CheckCreatePermission($entityTypeName, $permissions);
	}
	/**
	 * Check permission for READ operation.
	 * @param string $entityTypeName EntityTypeName.
	 * @param int $entityID Entity ID.
	 * @return bool
	 */
	protected function checkReadPermission($entityTypeName, $entityID)
	{
		if(!$this->config->isPermissionCheckEnabled())
		{
			return true;
		}

		/** @var \CCrmPerms $permissions */
		$permissions = $this->getUserPermissions();

		if($entityTypeName === \CCrmOwnerType::CompanyName)
		{
			return \CCrmCompany::CheckReadPermission($entityID, $permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::ContactName)
		{
			return \CCrmContact::CheckReadPermission($entityID, $permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::DealName)
		{
			return \CCrmDeal::CheckReadPermission($entityID, $permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::InvoiceName)
		{
			return \CCrmInvoice::CheckReadPermission($entityID, $permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::QuoteName)
		{
			return \CCrmQuote::CheckReadPermission($entityID, $permissions);
		}

		return \CCrmAuthorizationHelper::CheckReadPermission($entityTypeName, $entityID, $permissions);
	}
	/**
	 * Check permission for UPDATE operation.
	 * @param string $entityTypeName EntityTypeName.
	 * @param int $entityID Entity ID.
	 * @return bool
	 */
	protected function checkUpdatePermission($entityTypeName, $entityID)
	{
		if(!$this->config->isPermissionCheckEnabled())
		{
			return true;
		}

		/** @var \CCrmPerms $permissions */
		$permissions = $this->getUserPermissions();

		if($entityTypeName === \CCrmOwnerType::CompanyName)
		{
			return \CCrmCompany::CheckUpdatePermission($entityID, $permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::ContactName)
		{
			return \CCrmContact::CheckUpdatePermission($entityID, $permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::DealName)
		{
			return \CCrmDeal::CheckUpdatePermission($entityID, $permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::InvoiceName)
		{
			return \CCrmInvoice::CheckUpdatePermission($entityID, $permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::QuoteName)
		{
			return \CCrmQuote::CheckUpdatePermission($entityID, $permissions);
		}

		return \CCrmAuthorizationHelper::CheckUpdatePermission($entityTypeName, $entityID, $permissions);
	}
	//endregion
	/**
	 * Finalization phase. Called for every entity.
	 * Uses for calling common conversion code for every converted entity.
	 *
	 * @return void
	 */
	protected function onFinalizationPhase()
	{
		$this->createConversionTimelineRecord();

		if ($this->isAttachingSourceActivitiesEnabled())
		{
			$this->attachSourceActivitiesToDestination();
		}

		$this->copySourceTraceToDestination();
	}

	protected function createConversionTimelineRecord(): void
	{
		/**
		 * @todo move to operation after refactoring old entities on factory
		 * @see \Bitrix\Crm\Service\Operation\Conversion::createTimelineRecord()
		 */
		$controller = Crm\Timeline\TimelineManager::resolveController(
			[
				'ASSOCIATED_ENTITY_TYPE_ID' => $this->getEntityTypeID(),
			]
		);

		if ($controller)
		{
			$controller->onConvert(
				$this->getEntityID(),
				[
					'ENTITIES' => $this->resultData,
				]
			);
		}
	}

	/**
	 * Returns true if activities from source entity should be copied to destination entity
	 *
	 * @return bool
	 */
	protected function isAttachingSourceActivitiesEnabled(): bool
	{
		// If this functionality is not needed in some source type, overwrite this method and return false
		return true;
	}

	protected function attachSourceActivitiesToDestination(): void
	{
		$entityCreationTime = new Main\Type\DateTime();
		$entityCreationTime->add('T1S');

		foreach ($this->getSupportedDestinationTypeIDs() as $entityTypeID)
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);

			$entityID = static::getDestinationEntityID($entityTypeName, $this->resultData);
			if ($entityID <= 0)
			{
				continue;
			}

			$this->attachEntity($entityTypeID, $entityID);
			if (static::isNewDestinationEntity($entityTypeName, $entityID, $this->resultData))
			{
				//HACK: We are trying to shift events of created entities
				Crm\Timeline\CreationEntry::shiftEntity($entityTypeID, $entityID, $entityCreationTime);
				Crm\Timeline\LinkEntry::shiftAllEntriesForTimelineOwner($entityTypeID, $entityID, $entityCreationTime);
			}
		}
	}

	/**
	 * Attach source entity's activities, timeline objects and channel trackers to specified entity.
	 *
	 * @param int $dstEntityTypeId Entity Type ID.
	 * @param int $dstEntityId Entity ID.
	 */
	protected function attachEntity($dstEntityTypeId, $dstEntityId)
	{
		Crm\Timeline\Entity\TimelineBindingTable::attach(
			$this->getEntityTypeID(),
			$this->getEntityID(),
			$dstEntityTypeId,
			$dstEntityId,
			$this->getTimelineEntryTypesToAttach()
		);

		\CCrmActivity::AttachBinding($this->getEntityTypeID(), $this->getEntityID(), $dstEntityTypeId, $dstEntityId);

		if ($dstEntityTypeId === \CCrmOwnerType::Deal)
		{
			DealChannelBinding::attach($this->getEntityTypeID(), $this->getEntityID(), $dstEntityId);
		}
	}

	/**
	 * Detach source entity's activities, timeline objects and channel trackers from specified entity.
	 *
	 * @param int $dstEntityTypeId Entity Type ID.
	 * @param int $dstEntityId Entity ID.
	 */
	protected function detachEntity($dstEntityTypeId, $dstEntityId)
	{
		Crm\Timeline\Entity\TimelineBindingTable::detach(
			$this->getEntityTypeID(),
			$this->getEntityID(),
			$dstEntityTypeId,
			$dstEntityId,
			$this->getTimelineEntryTypesToAttach()
		);

		\CCrmActivity::DetachBinding($this->getEntityTypeID(), $this->getEntityID(), $dstEntityTypeId, $dstEntityId);

		if ($dstEntityTypeId === \CCrmOwnerType::Deal)
		{
			DealChannelBinding::detach($this->getEntityTypeID(), $this->getEntityID(), $dstEntityId);
		}
	}

	protected function getTimelineEntryTypesToAttach(): array
	{
		return [
			Crm\Timeline\TimelineType::ACTIVITY,
			Crm\Timeline\TimelineType::CREATION,
			Crm\Timeline\TimelineType::MARK,
			Crm\Timeline\TimelineType::COMMENT
		];
	}

	protected function copySourceTraceToDestination(): void
	{
		foreach ($this->getSupportedDestinationTypeIDs() as $entityTypeID)
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			$entityID = self::getDestinationEntityID($entityTypeName, $this->resultData);
			if ($entityID <= 0)
			{
				continue;
			}

			Tracking\Entity::copyTrace(
				$this->getEntityTypeID(),
				$this->getEntityID(),
				$entityTypeID,
				$entityID
			);
		}
	}

	protected function getAddOptions(): array
	{
		$options = $this->getUpdateOptions();

		if (isset($this->contextData['USER_ID']))
		{
			$options['USER_ID'] = $this->contextData['USER_ID'];
			$options['CURRENT_USER'] = $options['USER_ID'];
		}

		$options['DISABLE_USER_FIELD_CHECK'] = !$this->isUserFieldCheckEnabled();

		return $options;
	}

	protected function getUpdateOptions(): array
	{
		return [];
	}

	//endregion
}
