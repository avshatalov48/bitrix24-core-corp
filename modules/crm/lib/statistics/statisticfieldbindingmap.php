<?php
namespace Bitrix\Crm\Statistics;
use Bitrix\Main;

class StatisticFieldBindingMap
{
	private $typeName = '';
	/** @var $bindings array[StatisticFieldBinding]|null  */
	private $bindings = null;

	public function __construct($typeName = '')
	{
		if(!is_string($typeName))
		{
			throw new Main\ArgumentTypeException('typeName', 'string');
		}

		$this->typeName = strtolower($typeName);
	}
	/**
	* @return string
	*/
	public function getTypeName()
	{
		return $this->typeName;
	}
	/**
	* @return void
	*/
	public function setTypeName($typeName)
	{
		$this->typeName = $typeName;
	}
	/**
	* @param array $array Array returned by toArray.
	* @return void
	*/
	public function initializeFromArray(array $array)
	{
		$this->bindings = array();
		foreach($array as $item)
		{
			$binding = StatisticFieldBinding::fromArray($item);
			$this->bindings[$binding->getSlotName()] = $binding;
		}
	}
	/**
	* @return void
	*/
	protected function load()
	{
		if($this->bindings !== null)
		{
			return;
		}

		$this->bindings = array();

		$s = Main\Config\Option::get('crm', $this->typeName);
		if(is_string($s) && $s !== '')
		{
			$items = unserialize($s);
			if(is_array($items))
			{
				foreach($items as $item)
				{
					$binding = StatisticFieldBinding::fromArray($item);
					$slotName = $binding->getSlotName();
					if($slotName !== '')
					{
						$this->bindings[$slotName] = $binding;
					}
				}
			}
		}
	}
	/**
	* @return void
	*/
	public function save()
	{
		if($this->bindings === null)
		{
			return;
		}

		if(!empty($this->bindings))
		{
			Main\Config\Option::set('crm', $this->typeName, serialize($this->toArray()));
		}
		else
		{
			Main\Config\Option::delete('crm', array('name' => $this->typeName));
		}
	}
	/**
	* @return void
	*/
	public function clear()
	{
		$this->bindings = array();
	}
	/**
	* @param string $slotName Slot name.
	* @param StatisticFieldBinding $binding Binding.
	* @return void
	*/
	public function add($slotName, StatisticFieldBinding $binding)
	{
		if(!is_string($slotName))
		{
			throw new Main\ArgumentTypeException('slotName', 'string');
		}

		if($slotName === '')
		{
			throw new Main\ArgumentNullException('slotName');
		}

		if(isset($this->bindings[$slotName]))
		{
			throw new Main\InvalidOperationException("Binding for slot '{$slotName}' already exists.");
		}

		$this->load();
		if($binding->getSlotName() !== $slotName)
		{
			$binding->setSlotName($slotName);
		}
		$this->bindings[$slotName] = $binding;
	}
	/**
	* @param string $slotName Entity slot name.
	* @return StatisticFieldBinding|null
	*/
	public function get($slotName)
	{
		if(!is_string($slotName))
		{
			throw new Main\ArgumentTypeException('slotName', 'string');
		}

		if($slotName === '')
		{
			throw new Main\ArgumentNullException('slotName');
		}

		$this->load();
		return isset($this->bindings[$slotName]) ? $this->bindings[$slotName] : null;
	}
	/**
	* @return array[StatisticFieldBinding]
	*/
	public function getAll()
	{
		$this->load();
		return $this->bindings;
	}
	public function getCount()
	{
		$this->load();
		return count($this->bindings);
	}
	/**
	* @return boolean
	*/
	public function remove($slotName)
	{
		if(!is_string($slotName))
		{
			throw new Main\ArgumentTypeException('slotName', 'string');
		}

		if($slotName === '')
		{
			throw new Main\ArgumentNullException('slotName');
		}

		if(!isset($this->bindings[$slotName]))
		{
			return false;
		}

		unset($this->bindings[$slotName]);
		return true;
	}
	public function copyFrom(StatisticFieldBindingMap $src)
	{
		$this->bindings = array();
		foreach($src->bindings as $binding)
		{
			$this->bindings[] = clone $binding;
		}
	}
	/**
	* @return array
	*/
	public function toArray()
	{
		$this->load();

		$result = array();
		if($this->bindings !== null)
		{
			foreach($this->bindings as $binding)
			{
				/** @var $binding StatisticFieldBinding  */
				$result[] = $binding->toArray();
			}
		}
		return $result;
	}
	/**
	* @param array $array
	* @return StatisticFieldBindingMap
	*/
	public static function createFromArray(array $array)
	{
		$item = new StatisticFieldBindingMap();
		$item->initializeFromArray($array);
		return $item;
	}
	/**
	* @param string $slotName Entity slot name.
	* @return string
	*/
	public function resolveFieldName($slotName)
	{
		$binding = $this->get($slotName);
		return $binding !== null ? $binding->getFieldName() : '';
	}
}