<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;
class EntityConversionConfig
{
	/** @var EntityConversionConfigItem[] */
	protected $items = array();
	/** @var bool */
	protected $enablePermissionCheck = true;

	public function __construct(array $options = null)
	{
	}

	/**
	 * Get configuration item by entity type.
	 * @param int $entityTypeID Entity Type ID.
	 * @return EntityConversionConfigItem|null
	 */
	public function getItem($entityTypeID)
	{
		return isset($this->items[$entityTypeID]) ? $this->items[$entityTypeID] : null;
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
	 * Get entity initialization data.
	 * @param $entityTypeID
	 * @return array
	 */
	public function getEntityInitData($entityTypeID)
	{
		$item = $this->getItem($entityTypeID);
		return $item !== null ? $item->getInitData() : array();
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
		$results = array();
		foreach($this->items as $k => $v)
		{
			$results[mb_strtolower(\CCrmOwnerType::ResolveName($k))] = $v->toJavaScript();
		}
		return $results;
	}

	public function fromJavaScript(array $params)
	{
		$this->items = array();
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
		$results = array();
		foreach($this->items as $k => $v)
		{
			$results[\CCrmOwnerType::ResolveName($k)] = $v->externalize();
		}
		return $results;
	}

	public function internalize(array $params)
	{
		$this->items = array();
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
}