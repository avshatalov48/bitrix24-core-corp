<?php

namespace Bitrix\Crm\Activity;

class EmailFacility
{

	protected $owner;
	protected $bindings = array();

	public function getOwner()
	{
		return $this->owner;
	}

	public function getOwnerTypeId()
	{
		return !empty($this->owner) ? $this->owner['OWNER_TYPE_ID'] : 0;
	}

	public function getOwnerId()
	{
		return !empty($this->owner) ? $this->owner['OWNER_ID'] : 0;
	}

	public function getOwnerResponsibleId()
	{
		return \CCrmOwnerType::getResponsibleId($this->owner['OWNER_TYPE_ID'], $this->owner['OWNER_ID'], false);
	}

	public function getBindings()
	{
		return $this->bindings;
	}

	protected static function validateOwner($owner)
	{
		if (!is_array($owner))
		{
			return false;
		}

		if (!isset($owner['OWNER_TYPE_ID'], $owner['OWNER_ID']))
		{
			return false;
		}

		if (!(\CCrmOwnerType::isDefined($owner['OWNER_TYPE_ID']) && $owner['OWNER_ID'] > 0))
		{
			return false;
		}

		return true;
	}

	public function setOwner($typeId, $id)
	{
		$owner = array(
			'OWNER_TYPE_ID' => $typeId,
			'OWNER_ID' => $id,
		);

		if (!static::validateOwner($owner))
		{
			throw new Bitrix\Main\ArgumentNullException('owner');
		}

		$this->owner = $owner;

		$this->rebuildBindings();
	}

	public function setBindings(array $bindings, $force = false)
	{
		foreach ($bindings as $item)
		{
			if (!static::validateOwner($item))
			{
				throw new Bitrix\Main\ArgumentNullException('bindings');
			}
		}

		$this->bindings = $bindings;

		if (empty($this->owner) || $force)
		{
			if (!empty($this->bindings))
			{
				$this->owner = reset($this->bindings);
			}
		}

		$this->rebuildBindings();
	}

	protected function rebuildBindings()
	{
		if (!empty($this->owner))
		{
			$this->bindings = array_merge(
				array(
					$this->owner,
				),
				array_filter(
					$this->bindings,
					function ($item)
					{
						return $item['OWNER_TYPE_ID'] != $this->owner['OWNER_TYPE_ID'];
					}
				)
			);
		}
	}

}
