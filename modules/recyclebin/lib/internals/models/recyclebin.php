<?php

namespace Bitrix\Recyclebin\Internals\Models;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\Event;
use Bitrix\Main\Entity\EventResult;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;
use Bitrix\Main\Text\Emoji;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;

/**
 * Class RecyclebinTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Recyclebin_Query query()
 * @method static EO_Recyclebin_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Recyclebin_Result getById($id)
 * @method static EO_Recyclebin_Result getList(array $parameters = [])
 * @method static EO_Recyclebin_Entity getEntity()
 * @method static \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity createObject($setDefaultValues = true)
 * @method static \Bitrix\Recyclebin\Internals\Models\RecyclebinEntityCollection createCollection()
 * @method static \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity wakeUpObject($row)
 * @method static \Bitrix\Recyclebin\Internals\Models\RecyclebinEntityCollection wakeUpCollection($rows)
 */
class RecyclebinTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_recyclebin';
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
			(new StringField('NAME'))
				->addSaveDataModifier([Emoji::class, 'encode'])
				->addFetchDataModifier([Emoji::class, 'decode']),
			new StringField('SITE_ID'),
			new StringField('MODULE_ID'),
			new StringField('ENTITY_ID'),
			new StringField('ENTITY_TYPE'),
			new DatetimeField('TIMESTAMP'),
			new IntegerField('USER_ID'),
			new ReferenceField(
				'USER',
				UserTable::class,
				Join::on('this.USER_ID', 'ref.ID')
			),
		];
	}

	public static function onBeforeAdd(Event $event): EventResult
	{
		$result = new EventResult;
		$result->modifyFields(
			array(
				'TIMESTAMP' => DateTime::createFromTimestamp(time())
			)
		);

		return $result;
	}

	public static function getObjectClass(): string
	{
		return RecyclebinEntity::class;
	}

	public static function getCollectionClass(): string
	{
		return RecyclebinEntityCollection::class;
	}
}