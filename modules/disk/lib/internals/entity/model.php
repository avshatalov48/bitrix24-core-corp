<?php

namespace Bitrix\Disk\Internals\Entity;

use Bitrix\Main\ArgumentException;

class Model extends \Bitrix\Disk\Internals\Model
{
	/** @var array */
	protected $lazyAttributes = array();

	protected static function getReferenceConf($refName)
	{
		return FieldManager::getInstance()->getReferenceFieldByName(static::className(), $refName);
	}

	protected function setSubEntityToModel($entityName, array $modelMapAttributes, $className, $subEntity, $aliasesData)
	{
		if(FieldManager::getInstance()->getReferenceFieldByName(static::className(), $entityName))
		{
			$this->lazyAttributes[$entityName] = $className::buildFromArray($subEntity, $aliasesData);
			$this->setAsLoadedAttribute($entityName);
		}
	}

	//todo It's some experiment, draft. Dynamic getter for reference. It's draft. It does not support eager loading in getList (with)
	/**
	 * We have to normalize getMapReferenceAttributes() and use lower case.
	 */
	public function __call($name, $arguments)
	{
		$name = strtolower($name);
		if (substr($name, 0, 3) !== 'get')
		{
			return null;
		}

		$possibleReference = substr($name, 3);

		if (isset($this->lazyAttributes[$possibleReference]) || $this->isLoadedAttribute($possibleReference))
		{
			return $this->lazyAttributes[$possibleReference];
		}

		$mapReferenceAttributes = FieldManager::getInstance()->getReferenceFieldsByModel($this);
		if (!isset($mapReferenceAttributes[$possibleReference]))
		{
			return null;
		}

		if (!isset($mapReferenceAttributes[$possibleReference]['load']))
		{
			return null;
		}

		$load = $mapReferenceAttributes[$possibleReference]['load'];
		$this->lazyAttributes[$possibleReference] = $load($this);

		return $this->lazyAttributes[$possibleReference];
	}

	/**
	 * @param $referenceName
	 * @param $value
	 *
	 * @throws ArgumentException
	 */
	public function setReferenceValue($referenceName, $value)
	{
		$referenceField = FieldManager::getInstance()->getReferenceFieldByName($this, $referenceName);
		if (!$referenceField)
		{
			throw new ArgumentException("Invalid reference name {$referenceName}. Could not find field in map.");
		}

		if (is_a($value, $referenceField['class']))
		{
			$this->lazyAttributes[$referenceName] = $value;
			$this->isLoadedAttribute($referenceName);
		}
	}
}