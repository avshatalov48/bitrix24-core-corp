<?php


namespace Bitrix\Disk\Rest;

use Bitrix\Disk;

class Internalizer 
{
	private $allowedOperations = array('', '!', '<', '<=', '>', '>=', '><', '!><', '?', '=', '!=', '%', '!%', '');
	/**
	 * @var Entity\Base
	 */
	private $entity;

	/**
	 * Constructor of Internalizer.
	 * @param Entity\Base  $entity Entity.
	 * @param Service\Base $service Service which provides methods for REST.
	 */
	public function __construct(Entity\Base $entity, Service\Base $service)
	{
		$this->service = $service;
		$this->entity = $entity;
	}

	/**
	 * Clean wrong fields and operations from filter.
	 * @param array $filter Filter.
	 * @return array Filter after clean.
	 */
	public function cleanFilter(array $filter)
	{
		$possibleFields = $this->entity->getFieldsForFilter();
		$mapFields = $this->entity->getFieldsForMap();
		$whiteFilter = array();

		$filter = array_change_key_case($filter, CASE_UPPER);
		foreach($filter as $key => $value)
		{
			if(is_numeric($key) && is_array($value))
			{
				continue;
			}
			if(preg_match('/^([^a-zA-Z]*)(.*)/', $key, $matches))
			{
				$operation = $matches[1];
				$field = $matches[2];

				if(!in_array($operation, $this->allowedOperations, true))
				{
					continue;
				}
				if(isset($possibleFields[$field]))
				{
					if(isset($mapFields[$field]))
					{
						$value = call_user_func_array($mapFields[$field]['IN'], array($value));
					}
					$whiteFilter[$key] = $value;
				}
			}
		}

		return $whiteFilter;
	}
}