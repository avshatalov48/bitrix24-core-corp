<?php

namespace Bitrix\Recyclebin\Internals\Models;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\Result;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\TextField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;
use Bitrix\Main\Text\Emoji;

/**
 * Class RecyclebinDataTable
 *
 * @package Bitrix\Recyclebin\Model
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RecyclebinData_Query query()
 * @method static EO_RecyclebinData_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_RecyclebinData_Result getById($id)
 * @method static EO_RecyclebinData_Result getList(array $parameters = [])
 * @method static EO_RecyclebinData_Entity getEntity()
 * @method static \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData createObject($setDefaultValues = true)
 * @method static \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData_Collection createCollection()
 * @method static \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData wakeUpObject($row)
 * @method static \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinData_Collection wakeUpCollection($rows)
 */
final class RecyclebinDataTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_recyclebin_data';
	}

	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			new IntegerField('RECYCLEBIN_ID'),
			new StringField('ACTION'),
			(new TextField('DATA'))
				->addSaveDataModifier([Emoji::class, 'encode'])
				->addFetchDataModifier([Emoji::class, 'decode']),
			new ReferenceField(
				'RECYCLEBIN',
				RecyclebinTable::class,
				Join::on('this.RECYCLEBIN_ID', 'ref.ID')
			),
		];
	}

	/**
	 * @throws SqlQueryException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public static function deleteByRecyclebinId(mixed $recyclebinId): Result
	{
		$connection = self::getEntity()->getConnection();
		$sql = "DELETE FROM " . self::getTableName() . " WHERE RECYCLEBIN_ID = " . (int)$recyclebinId;

		return $connection->query($sql);
	}
}