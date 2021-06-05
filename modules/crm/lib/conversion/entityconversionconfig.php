<?php

namespace Bitrix\Crm\Conversion;

use Bitrix\Main\Config\Option;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Web\Uri;

class EntityConversionConfig
{
	/** @var EntityConversionConfigItem[] */
	protected $items = [];
	/** @var bool */
	protected $enablePermissionCheck = true;
	/** @var Uri|null */
	protected $originUrl;

	public function __construct(array $options = null)
	{
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

	protected static function getOptionName(): string
	{
		$entityName = mb_strtolower(\CCrmOwnerType::ResolveName(static::getEntityTypeId()));
		return "crm_{$entityName}_conversion";
	}

	/**
	 * @return static|null
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function load()
	{
		return static::constructFromOption(Option::get('crm', static::getOptionName(), '', ''));
	}

	protected static function constructFromOption(string $optionValue, array $options = null): ?EntityConversionConfig
	{
		$params = $optionValue !== '' ? unserialize($optionValue, ['allowed_classes' => false]) : null;
		if(!is_array($params))
		{
			return null;
		}

		$item = new static($options);
		$item->internalize($params);
		return $item;
	}

	public function save()
	{
		Option::set('crm', static::getOptionName(), serialize($this->externalize()), '');
	}

	/**
	 * @return static
	 * @throws NotImplementedException
	 */
	public static function getDefault()
	{
		$config = new static();
		$item = $config->getItem(static::getDefaultDestinationEntityTypeId());
		$item->setActive(true);
		$item->enableSynchronization(true);
		return $config;
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
	 * @abstract
	 * @return int
	 * @throws NotImplementedException
	 */
	public function getSchemeID()
	{
		throw new NotImplementedException(__METHOD__.' should be overridden in '.static::class);
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

	/**
	 * Add configuration item.
	 * @param EntityConversionConfigItem $item Configuration item.
	 */
	protected function addItem(EntityConversionConfigItem $item)
	{
		$this->items[$item->getEntityTypeID()] = $item;
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
		foreach($this->items as $k => $v)
		{
			$results[mb_strtolower(\CCrmOwnerType::ResolveName($k))] = $v->toJavaScript();
		}
		return $results;
	}

	public function fromJavaScript(array $params)
	{
		$this->items = [];
		foreach($params as $k => $v)
		{
			$entityTypeID = \CCrmOwnerType::ResolveID($k);
			if($entityTypeID !== \CCrmOwnerType::Undefined)
			{
				$item = new EntityConversionConfigItem($entityTypeID);
				$item->fromJavaScript($v);
				$this->addItem($item);
			}
		}
	}

	public function externalize()
	{
		$results = [];
		foreach($this->items as $k => $v)
		{
			$results[\CCrmOwnerType::ResolveName($k)] = $v->externalize();
		}
		return $results;
	}

	public function internalize(array $params)
	{
		$this->items = [];
		foreach($params as $k => $v)
		{
			$entityTypeID = \CCrmOwnerType::ResolveID($k);
			if($entityTypeID !== \CCrmOwnerType::Undefined)
			{
				$item = new EntityConversionConfigItem($entityTypeID);
				$item->internalize($v);
				$this->addItem($item);
			}
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
}