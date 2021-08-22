<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage iblock
 * @copyright  2001-2018 Bitrix
 */

namespace Bitrix\Iblock\ORM;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\ORM\Fields\PropertyOneToMany;
use Bitrix\Iblock\ORM\Fields\PropertyReference;
use Bitrix\Iblock\PropertyIndex\Manager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\EntityError;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\FieldError;
use Bitrix\Main\ORM\Fields\Relations\Relation;
use CIBlock;
use CIBlockProperty;

/**
 * @package    bitrix
 * @subpackage iblock
 *
 * @method static ElementEntity getEntity()
 */
abstract class CommonElementTable extends DataManager
{
	public static function getEntityClass()
	{
		return ElementEntity::class;
	}

	public static function getQueryClass()
	{
		return Query::class;
	}

	public static function setDefaultScope($query)
	{
		return $query->where("IBLOCK_ID", static::getEntity()->getIblock()->getId());
	}

	public static function getTableName()
	{
		return ElementTable::getTableName();
	}

	public static function getMap()
	{
		return ElementTable::getMap();
	}

	public static function onBeforeAdd(Event $event)
	{
		$object = $event->getParameter('object');
		$fields = static::getEntity()->getFields();

		$result = new EventResult;

		foreach ($fields as $field)
		{
			// check required properties
			$hasEmptyRequiredValue = false;

			if ($field instanceof PropertyReference || $field instanceof PropertyOneToMany)
			{
				$property = $field->getIblockElementProperty();

				if ($property->getIsRequired())
				{
					/** @var ValueStorage $valueContainer */
					$valueContainer = $object->get($field->getName());

					if (empty($valueContainer))
					{
						$hasEmptyRequiredValue = true;
					}

					// check with GetLength
					if ($valueContainer instanceof ValueStorage)
					{
						$userType = CIBlockProperty::GetUserType($property->getUserType());

						if(array_key_exists("GetLength", $userType))
						{
							$length = call_user_func_array(
								$userType["GetLength"],
								[
									$property->collectValues(),
									["VALUE" => $valueContainer->getValue()]
								]
							);
						}
						else
						{
							$length = mb_strlen($valueContainer->getValue());
						}

						$hasEmptyRequiredValue = ($length <= 0);
					}


					if ($hasEmptyRequiredValue)
					{
						$result->addError(new FieldError(
							$field,
							Loc::getMessage(
								"MAIN_ENTITY_FIELD_REQUIRED",
								["#FIELD#" => $property->getName()]
							),
							FieldError::EMPTY_REQUIRED
						));
					}
				}
			}
		}

		return $result;
	}

	public static function onAfterAdd(Event $event)
	{
		$elementId = (int) end($event->getParameters()['primary']);
		$iblockId = static::getEntity()->getIblock()->getId();

		// clear tag cache
		CIBlock::clearIblockTagCache($iblockId);

		// update index
		Manager::updateElementIndex($iblockId, $elementId);
	}

	public static function onAfterUpdate(Event $event)
	{
		$elementId = (int) end($event->getParameters()['primary']);
		$iblockId = static::getEntity()->getIblock()->getId();

		// clear tag cache
		CIBlock::clearIblockTagCache($iblockId);

		// update index
		Manager::updateElementIndex($iblockId, $elementId);
	}

	public static function onAfterDelete(Event $event)
	{
		parent::onAfterDelete($event);

		$elementId = (int) end($event->getParameters()['primary']);
		$iblockId = static::getEntity()->getIblock()->getId();
		$connection = static::getEntity()->getConnection();

		// delete property values
		$tables = [static::getEntity()->getSingleValueTableName(), static::getEntity()->getMultiValueTableName()];

		foreach (array_unique($tables) as $table)
		{
			$connection->query("DELETE FROM {$table} WHERE IBLOCK_ELEMENT_ID = {$elementId}");
		}

		// clear tag cache
		CIBlock::clearIblockTagCache($iblockId);

		// delete index
		Manager::deleteElementIndex($iblockId, $elementId);
	}
}
