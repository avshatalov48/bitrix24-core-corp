<?php

namespace Bitrix\Crm\Conversion;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Config\Option;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Web\Uri;

class EntityConversionConfig
{
	/** @var int */
	//todo remove in the future and let only converter now what the destination type is?
	//we can simply change storage approach and move the save out of config and config will
	// have no need in srcEntityTypeId to save itself
	protected $srcEntityTypeID;
	/** @var EntityConversionConfigItem[] */
	protected $items = [];
	/** @var bool */
	protected $enablePermissionCheck = true;
	/** @var Uri|null */
	protected $originUrl;
	/** @var Scheme */
	protected $scheme;
	/** @var Context|null */
	protected $context;

	public function __construct(array $options = null)
	{
	}

	public static function create(int $srcEntityTypeID, array $options = null): self
	{
		$config = new static($options);

		$config->srcEntityTypeID = $srcEntityTypeID;

		return $config;
	}

	public static function createFromExternalized(array $externalizedParams): ?self
	{
		$config = new self();
		$config->internalize($externalizedParams);

		return static::checkInstanceIntegrity($config) ? $config : null;
	}

	/**
	 * Check if an instance of config was constructed completely
	 *
	 * @param EntityConversionConfig $config
	 *
	 * @return bool
	 */
	private static function checkInstanceIntegrity(self $config): bool
	{
		return (
			is_int($config->srcEntityTypeID)
			&& ($config->srcEntityTypeID > 0)
		);
	}

	public function getContext(): ?Context
	{
		return $this->context;
	}

	public function setContext(Context $context): self
	{
		$this->context = $context;

		return $this;
	}

	/**
	 * @abstract
	 * @return int
	 * @throws NotImplementedException
	 */
	protected static function getEntityTypeId(): int
	{
		throw new NotImplementedException(__METHOD__.' should be overridden in '.static::class);
	}

	protected static function getOptionName(int $entityTypeId): string
	{
		$entityName = mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId));
		return "crm_{$entityName}_conversion";
	}

	/**
	 * @return static|null
	 */
	public static function load()
	{
		$optionValue = Option::get('crm', static::getOptionName(static::getEntityTypeId()), '', '');

		return static::constructFromOption($optionValue, static::getEntityTypeId());
	}

	public static function loadByEntityTypeId(int $srcEntityTypeId): ?self
	{
		$optionValue = Option::get('crm', static::getOptionName($srcEntityTypeId), '', '');

		return static::constructFromOption($optionValue, $srcEntityTypeId);
	}

	protected static function constructFromOption(
		string $optionValue,
		int $srcEntityTypeId,
		array $options = null
	): ?EntityConversionConfig
	{
		$params = $optionValue !== '' ? unserialize($optionValue, ['allowed_classes' => false]) : null;
		if(!is_array($params))
		{
			return null;
		}

		//todo use static::createFromExternalized here (have to pass $options into a serialized array)
		$item = static::create($srcEntityTypeId, $options);
		$item->internalize($params);
		return $item;
	}

	public function save()
	{
		Option::set('crm', static::getOptionName($this->srcEntityTypeID), serialize($this->externalize()), '');
	}

	public static function removeByEntityTypeId(int $srcEntityTypeId): void
	{
		Option::delete(
			'crm',
			[
				'name' => static::getOptionName($srcEntityTypeId)
			],
		);
	}

	/**
	 * @deprecated
	 *
	 * @return static
	 */
	public static function getDefault()
	{
		return ConversionManager::getDefaultConfig(static::getEntityTypeId());
	}

	/**
	 * @abstract
	 * @return int
	 * @throws NotImplementedException
	 */
	protected static function getDefaultDestinationEntityTypeId(): int
	{
		throw new NotImplementedException(__METHOD__.' should be overridden in '.static::class);
	}

	/**
	 * @return int
	 */
	public function getSchemeID()
	{
		return $this->getScheme()->getCurrentItem()->getId();
	}

	/**
	 * Created while refactoring
	 *
	 * Somehow, static method getCurrentSchemeID became non-static in \Bitrix\Crm\Conversion\LeadConversionConfig
	 * If we declare public static function getCurrentSchemeID in the base class, it would result in an fatal error
	 * So, we have this workaround
	 *
	 * @return int
	 * @throws NotImplementedException
	 * @throws ObjectNotFoundException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	protected static function getCurrentSchemeIDImplementation(): int
	{
		$config = static::load();
		if($config === null)
		{
			$config = static::getDefault();
		}

		$schemeID = $config->getSchemeID();

		$schemeClass = ConversionManager::getSchemeClass(static::getEntityTypeId());
		if (is_null($schemeClass))
		{
			throw new ObjectNotFoundException("Can't find a scheme class for entityTypeId = ".static::getEntityTypeId());
		}

		if($schemeID === $schemeClass::UNDEFINED)
		{
			$schemeID = $schemeClass::getDefault();
		}

		return $schemeID;
	}

	/**
	 * Get configuration item by entity type.
	 * @param int $entityTypeID Entity Type ID.
	 * @return EntityConversionConfigItem|null
	 */
	public function getItem($entityTypeID)
	{
		return $this->items[$entityTypeID] ?? null;
	}

	public function addItem(EntityConversionConfigItem $item)
	{
		$this->items[$item->getEntityTypeID()] = $item;
	}

	public function deleteItemByEntityTypeId(int $entityTypeId): bool
	{
		if (array_key_exists($entityTypeId, $this->items))
		{
			unset($this->items[$entityTypeId]);

			return true;
		}

		return false;
	}

	/**
	* @return EntityConversionConfigItem[]
	*/
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * @return EntityConversionConfigItem[]
	 */
	public function getActiveItems(): array
	{
		$activeItems = [];
		foreach ($this->getItems() as $entityTypeId => $item)
		{
			if ($item->isActive())
			{
				$activeItems[$entityTypeId] = $item;
			}
		}

		return $activeItems;
	}

	/**
	 * Get entity initialization data.
	 * @param $entityTypeID
	 * @return array
	 */
	public function getEntityInitData($entityTypeID)
	{
		$item = $this->getItem($entityTypeID);
		return $item !== null ? $item->getInitData() : [];
	}

	/**
	 * Check if permission check enabled
	 * @return bool
	 */
	public function isPermissionCheckEnabled()
	{
		return $this->enablePermissionCheck;
	}

	/**
	 * Enable permission check
	 * @param bool $enable Flag
	 * @return void
	 */
	public function enablePermissionCheck($enable)
	{
		if(!is_bool($enable))
		{
			$enable = (bool)$enable;
		}
		$this->enablePermissionCheck = $enable;
	}

	public function toJavaScript()
	{
		$results = [];

		foreach($this->items as $entityTypeId => $configItem)
		{
			$results[mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId))] = $configItem->toJavaScript();
		}

		return $results;
	}

	public function toJson(): array
	{
		$results = [];

		foreach($this->items as $configItem)
		{
			$results[] = $configItem->toJavaScript();
		}

		return $results;
	}

	public function fromJavaScript(array $params)
	{
		$this->items = [];
		foreach($params as $entityTypeName => $itemParams)
		{
			$entityTypeID = \CCrmOwnerType::ResolveID($entityTypeName);
			if($entityTypeID !== \CCrmOwnerType::Undefined)
			{
				$item = new EntityConversionConfigItem($entityTypeID);
				$item->fromJavaScript($itemParams);
				$this->addItem($item);
			}
		}
	}

	public function externalize()
	{
		$result = [
			'srcEntityTypeId' => $this->srcEntityTypeID,
		];

		foreach($this->items as $entityTypeId => $item)
		{
			$result[\CCrmOwnerType::ResolveName($entityTypeId)] = $item->externalize();
		}

		return $result;
	}

	public function internalize(array $params)
	{
		foreach ($params as $entityTypeName => $itemParams)
		{
			$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);

			if (is_array($itemParams) && \CCrmOwnerType::IsDefined($entityTypeId))
			{
				$item = $this->getItem($entityTypeId);
				if (!$item)
				{
					$item = new EntityConversionConfigItem($entityTypeId);
					$this->addItem($item);
				}

				$item->internalize($itemParams);
			}
		}

		if (isset($params['srcEntityTypeId']))
		{
			$this->srcEntityTypeID = (int)$params['srcEntityTypeId'];
		}
	}

	public function setOriginUrl(Uri $url): EntityConversionConfig
	{
		if ($url->getUri() !== '')
		{
			$this->originUrl = $url;
		}

		return $this;
	}

	public function getOriginUrl(): ?Uri
	{
		return $this->originUrl;
	}

	public function getScheme(): Scheme
	{
		if ($this->scheme === null)
		{
			$oldScheme = ConversionManager::getSchemeClass($this->srcEntityTypeID);

			$allEntityTypeIds = [];
			$activeEntityTypeIds = [];
			foreach ($this->getItems() as $item)
			{
				$allEntityTypeIds[] = $item->getEntityTypeID();
				if ($item->isActive())
				{
					$activeEntityTypeIds[] = $item->getEntityTypeID();
				}
			}
			sort($activeEntityTypeIds);

			$currentSchemeId = null;
			$items = [];
			foreach ($oldScheme::getAllDescriptions() as $schemeId => $phrase)
			{
				$entityTypeIds = $oldScheme::getEntityTypeIds($schemeId);
				if (!empty(array_diff($entityTypeIds, $allEntityTypeIds)))
				{
					// scheme item contains entity type that is not present in current config
					continue;
				}

				$item =
					(new SchemeItem($entityTypeIds, $phrase))
						->setId($schemeId)
				;

				$items[] = $item;

				if ($entityTypeIds === $activeEntityTypeIds)
				{
					$currentSchemeId = $item->getId();
				}
			}

			foreach ($this->items as $item)
			{
				$typeId = $item->getEntityTypeID();
				if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($typeId))
				{
					$entityTypeIds = [$typeId];
					$item = (new SchemeItem($entityTypeIds, \CCrmOwnerType::GetDescription($typeId)));

					$items[] = $item;

					if ($entityTypeIds === $activeEntityTypeIds)
					{
						$currentSchemeId = $item->getId();
					}
				}
			}

			$this->scheme = new Scheme($items);
			if ($currentSchemeId)
			{
				$this->scheme->setCurrentItemId($currentSchemeId);
			}
		}

		return $this->scheme;
	}

	final protected function appendCustomRelations(): void
	{
		$relations = Container::getInstance()->getRelationManager()->getChildRelations(static::getEntityTypeId());

		foreach ($relations as $relation)
		{
			if ($relation->isPredefined() || !$relation->getSettings()->isConversion())
			{
				continue;
			}

			if (!$this->getItem($relation->getChildEntityTypeId()))
			{
				$this->addItem(new EntityConversionConfigItem($relation->getChildEntityTypeId()));
			}
		}
	}
}
