<?php

namespace Bitrix\Landing\Assets;


/**
 * Class Manager
 * Collect assets, sort by locations, set output in different modes (webpack or default)
 *
 * @package Bitrix\Landing
 */
class ResourceCollection
{
	const KEY_PATH = 'path';
	const KEY_TYPE = 'type';
	const KEY_LOCATION = 'location';
	const KEY_ORDER = 'order';
	
	/**
	 * Collection of added resources. Key of array - path, values - array of parameters
	 * @var array
	 */
	protected $resources;
	/**
	 * Order variable for save added asset position
	 * @var int
	 */
	protected $order;
	/**
	 * Save assets string, like a <script> and <link>. May be external links, or local inline scripts e.g.
	 * @var array
	 */
	protected $strings = [];
	
	/**
	 * ResourceCollection constructor.
	 */
	public function __construct()
	{
		$this->resources = [];
		$this->order = 0;
	}
	
	/**
	 * @param string $path
	 * @param string $type
	 * @param string $location
	 */
	public function add($path, $type, $location)
	{
//		overwrite only if new location more
		if ($this->isResourceAdded($path))
		{
			if (!$this->isNeedRaiseLocation($path, $location))
			{
				return;
			}
		}
		
		$this->resources[$path] = [
			self::KEY_PATH => $path,
			self::KEY_TYPE => $type,
			self::KEY_LOCATION => $location,
			self::KEY_ORDER => $this->order++,
		];
	}
	
	protected function isResourceAdded($path)
	{
		return array_key_exists($path, $this->resources);
	}
	
	protected function isNeedRaiseLocation($path, $location)
	{
		return $location < $this->resources[$path][self::KEY_LOCATION];
	}
	
	/**
	 * Save asset string in collection (like a <script> or <link>)
	 * @param $string
	 */
	public function addString($string)
	{
		if ($string && !in_array($string, $this->strings))
		{
			$this->strings[] = $string;
		}
	}
	
	/**
	 * Return added strings
	 * @return array of strings
	 */
	public function getStrings()
	{
		return $this->strings;
	}
	
	/**
	 * @param mixed $pathes
	 */
	public function remove($pathes)
	{
		if (!is_array($pathes))
		{
			$pathes = [$pathes];
		}
		
		foreach ($pathes as $path)
		{
			$this->removeOnce($path);
		}
	}
	
	protected function removeOnce($path)
	{
		if ($this->isResourceAdded($path))
		{
			unset($this->resources[$path]);
		}
	}
	
	/**
	 * Create new ResourceCollection object by filter
	 *
	 * @param $field - field name
	 * @param $value - value of field
	 * @return ResourceCollection
	 */
	public function getSliceByFilter($field, $value)
	{
		$resourcesByFilter = new self();
		
		foreach ($this->resources as $resource)
		{
			if (array_key_exists($field, $resource) && $resource[$field] == $value)
			{
				$resourcesByFilter->add(
					$resource[self::KEY_PATH],
					$resource[self::KEY_TYPE],
					$resource[self::KEY_LOCATION]
				);
			}
		}
		
		return $resourcesByFilter;
	}
	
	/**
	 * Return pathes of added resources
	 * @return array
	 */
	public function getPathes()
	{
		return array_keys($this->resources);
	}
	
	/**
	 * Sort by location and group by types
	 * @return array
	 */
	public function getNormalized()
	{
		$this->sortByLocation();
		$normalizedResources = [];
		
		foreach ($this->resources as $resource)
		{
			if (!array_key_exists($resource[self::KEY_TYPE], $normalizedResources))
			{
				$normalizedResources[$resource[self::KEY_TYPE]] = [];
			}
			$normalizedResources[$resource[self::KEY_TYPE]][] = $resource[self::KEY_PATH];
		}
		
		return $normalizedResources;
	}
	
	protected function sortByLocation()
	{
		$columnLocation = array_column($this->resources, self::KEY_LOCATION);
		$columnOrder = array_column($this->resources, self::KEY_ORDER);
		array_multisort($columnLocation, $columnOrder, $this->resources);
	}
	
	/**
	 * Return true is no added resources and no added strings
	 * @return bool
	 */
	public function isEmpty()
	{
		return (count($this->resources) === 0) && (count($this->strings) === 0);
	}
}