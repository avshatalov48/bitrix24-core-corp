<?php

namespace Bitrix\Disk\Internals\Index;

use Bitrix\Disk\Internals\DataManager;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\Text\BinaryString;
use Bitrix\Main\Type\DateTime;

abstract class BaseIndexTable extends DataManager
{
	public static function getMap()
	{
		return [
			new Fields\IntegerField("OBJECT_ID", [
				"required" => true,
				"primary" => true,
			]),
			new Fields\Relations\Reference(
				"OBJECT",
				ObjectTable::class,
				Join::on("this.OBJECT_ID", "ref.ID")
			),
			(new Fields\TextField("SEARCH_INDEX"))
				/** @see \Bitrix\Disk\Internals\Index\BaseIndexTable::limitValue */
				->addSaveDataModifier([static::class, 'limitValue']),
			new Fields\DatetimeField("UPDATE_TIME", [
				"required" => true,
				"default_value" => function(){
					return new DateTime();
				}
			]),
		];
	}

	/**
	 * @return int
	 */
	abstract public static function getMaxIndexSize();

	public static function limitValue($value)
	{
		//yes, we know that substr may kills some last characters
		return BinaryString::getSubstring($value, 0, (int)static::getMaxIndexSize());
	}

	public static function upsert($objectId, $searchIndex)
	{
		$objectId = (int)$objectId;
		$searchIndex = trim($searchIndex);

		static::merge([
			'OBJECT_ID' => $objectId,
			'SEARCH_INDEX' => $searchIndex,
			'UPDATE_TIME'=>	new DateTime(),
		]);
	}

	protected static function getPrimaryFieldsForMerge()
	{
		return ['OBJECT_ID'];
	}
}