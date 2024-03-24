<?php

namespace Bitrix\Recyclebin\Internals\Models;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\Result;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;

/**
 * Class RecyclebinFileTable
 *
 * @package Bitrix\Recyclebin\Model
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RecyclebinFile_Query query()
 * @method static EO_RecyclebinFile_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_RecyclebinFile_Result getById($id)
 * @method static EO_RecyclebinFile_Result getList(array $parameters = [])
 * @method static EO_RecyclebinFile_Entity getEntity()
 * @method static \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile createObject($setDefaultValues = true)
 * @method static \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile_Collection createCollection()
 * @method static \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile wakeUpObject($row)
 * @method static \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile_Collection wakeUpCollection($rows)
 */
final class RecyclebinFileTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_recyclebin_files';
	}

	/**
	 * @throws SystemException
	 */
	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			new IntegerField('RECYCLEBIN_ID'),
			new IntegerField('FILE_ID'),
			new StringField('STORAGE_TYPE'),
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