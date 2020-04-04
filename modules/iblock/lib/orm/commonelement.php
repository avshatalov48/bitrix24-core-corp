<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage iblock
 * @copyright  2001-2018 Bitrix
 */

namespace Bitrix\Iblock\ORM;

use Bitrix\Iblock\ORM\Fields\PropertyOneToMany;
use Bitrix\Iblock\ORM\Fields\PropertyReference;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Objectify\State;
use Bitrix\Main\Text\StringHelper;

/**
 * @package    bitrix
 * @subpackage iblock
 */
abstract class CommonElement extends EO_CommonElement
{
	/**
	 * Accepts PropertyValue and scalar, converts it to property reference
	 *
	 * @param $fieldName
	 * @param $value
	 *
	 * @return EntityObject
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function sysSetValue($fieldName, $value)
	{
		$field = $this->entity->getField($fieldName);

		if ($field instanceof PropertyReference)
		{
			// convert PropertyValue to regular reference
			$value = $this->sysConvertPropertyValue($value, $field);

			/** @var EntityObject $currentValue */
			$currentValue = $this->get($fieldName);

			if (!empty($currentValue))
			{
				// set value directly
				$currentValue->set('VALUE', $value->get('VALUE'));

				if ($this->entity->hasField('DESCRIPTION') && $value->sysHasValue('DESCRIPTION'))
				{
					$currentValue->set('DESCRIPTION', $value->get('DESCRIPTION'));
				}

				// mark current object as changed, or else save() will be skipped
				$this->sysChangeState(State::CHANGED);

				return $this;
			}
		}

		return parent::sysSetValue($fieldName, $value);
	}

	/**
	 * Accepts PropertyValue and scalar, converts it to property reference
	 *
	 * @param $fieldName
	 * @param $remoteObject
	 *
	 * @return bool|void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function sysAddToCollection($fieldName, $remoteObject)
	{
		$fieldName = StringHelper::strtoupper($fieldName);
		$field = $this->entity->getField($fieldName);

		if ($field instanceof PropertyOneToMany)
		{
			// convert PropertyValue to regular relation object
			$remoteObject = $this->sysConvertPropertyValue($remoteObject, $field);

			// check for duplicates
			if (in_array(
				$field->getIblockElementProperty()->getPropertyType(),
				[PropertyTable::TYPE_STRING, PropertyTable::TYPE_NUMBER],
				true
			))
			{
				// it's ok to have duplicates for this type
			}
			else
			{
				// check for duplicates and skip ot
				/** @var Collection $collection */
				$collection = $this->get($fieldName);

				// we need filled collection to check value in it
				if (empty($collection) || !$collection->sysIsFilled())
				{
					$collection = $this->fill($fieldName);
				}

				foreach ($collection as $refObject)
				{
					// we have original scalar in VALUE
					if ($refObject->get('VALUE') === $remoteObject->get('VALUE'))
					{
						// skip duplicate
						return false;
					}
				}
			}
		}

		return parent::sysAddToCollection($fieldName, $remoteObject);
	}

	/**
	 * Accepts PropertyValue and scalar, converts it to property reference
	 *
	 * @param $fieldName
	 * @param $remoteObject
	 *
	 * @return bool|void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function sysRemoveFromCollection($fieldName, $remoteObject)
	{
		$fieldName = StringHelper::strtoupper($fieldName);
		$field = $this->entity->getField($fieldName);

		if ($field instanceof PropertyOneToMany)
		{
			// convert PropertyValue to regular relation object
			$valueObject = $this->sysConvertPropertyValue($remoteObject, $field);

			if ($valueObject->sysHasPrimary())
			{
				// existing object. nothing to do, just call parent remove
				$remoteObject = $valueObject;
			}
			else
			{
				// find relation object by value and remove it
				/** @var Collection $collection */
				$collection = $this->get($fieldName);

				// we need filled collection to check value in it
				if (empty($collection) || !$collection->sysIsFilled())
				{
					$collection = $this->fill($fieldName);
				}

				$foundValue = false;

				foreach ($collection as $refObject)
				{
					// find original object with that value
					if ($valueObject->get('VALUE') === $refObject->get('VALUE'))
					{
						$foundValue = true;
						$remoteObject = $refObject;
						break;
					}
				}

				if (!$foundValue)
				{
					// nothing to do, value was not found in collection
					return false;
				}
			}
		}

		return parent::sysRemoveFromCollection($fieldName, $remoteObject);
	}

	/**
	 * @param mixed               $value
	 * @param PropertyReference|PropertyOneToMany $field
	 *
	 * @return mixed|EntityObject
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function sysConvertPropertyValue($value, $field)
	{
		$valueEntity = $field->getRefEntity();
		$valueObjectClass = $valueEntity->getObjectClass();

		if ($value instanceof $valueObjectClass)
		{
			// nothing to do
			return $value;
		}

		/** @var EntityObject $valueObject */
		$valueObject = $valueEntity->createObject();

		// set base fields
		$valueObject->set('IBLOCK_ELEMENT_ID', $this->getId());

		if ($valueEntity->hasField('IBLOCK_PROPERTY_ID'))
		{
			$valueObject->set('IBLOCK_PROPERTY_ID', $field->getIblockElementProperty()->getId());
		}

		// set value fields
		if ($value instanceof PropertyValue)
		{
			$valueObject->set('VALUE', $value->getValue());

			if ($value->hasDescription())
			{
				$valueObject->set('DESCRIPTION', $value->getDescription());
			}
		}
		else
		{
			$valueObject->set('VALUE', $value);
		}

		return $valueObject;
	}
}
