<?php
namespace Bitrix\Dav\Internals;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\DateTime;

/**
 * Class DavConnectionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DavConnection_Query query()
 * @method static EO_DavConnection_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DavConnection_Result getById($id)
 * @method static EO_DavConnection_Result getList(array $parameters = [])
 * @method static EO_DavConnection_Entity getEntity()
 * @method static \Bitrix\Dav\Internals\EO_DavConnection createObject($setDefaultValues = true)
 * @method static \Bitrix\Dav\Internals\EO_DavConnection_Collection createCollection()
 * @method static \Bitrix\Dav\Internals\EO_DavConnection wakeUpObject($row)
 * @method static \Bitrix\Dav\Internals\EO_DavConnection_Collection wakeUpCollection($rows)
 */
class DavConnectionTable extends DataManager
{
	private const ENTITY_TYPE_USER = 'user';

	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_dav_connections';
	}

	public static function getMap()
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
				->configureTitle(Loc::getMessage('DAV_CONNECTION_ITEM_FIELD_ID'))
			,
			(new StringField('ENTITY_TYPE'))
				->configureRequired()
				->configureSize(32)
				->configureDefaultValue(self::ENTITY_TYPE_USER)
				->configureTitle(Loc::getMessage('DAV_CONNECTION_ITEM_FIELD_ENTITY_TYPE'))
			,
			(new IntegerField('ENTITY_ID'))
				->configureRequired()
				->configureTitle(Loc::getMessage('DAV_CONNECTION_ITEM_FIELD_ENTITY_ID'))
			,
			(new StringField('ACCOUNT_TYPE'))
				->configureRequired()
				->configureSize(32)
				->configureTitle(Loc::getMessage('DAV_CONNECTION_ITEM_FIELD_ACCOUNT_TYPE'))
			,
			(new StringField('SYNC_TOKEN'))
				->configureSize(128)
				->configureNullable()
				->configureTitle(Loc::getMessage('DAV_CONNECTION_ITEM_FIELD_SYNC_TOKEN'))
			,
			(new StringField('NAME'))
				->configureRequired()
				->configureSize(128)
				->configureTitle(Loc::getMessage('DAV_CONNECTION_ITEM_FIELD_NAME'))
			,
			(new StringField('SERVER_SCHEME'))
				->configureSize(5)
				->configureDefaultValue('http')
				->configureTitle(Loc::getMessage('DAV_CONNECTION_ITEM_FIELD_SERVER_SCHEME'))
			,
			(new StringField('SERVER_HOST'))
				->configureSize(128)
				->configureTitle(Loc::getMessage('DAV_CONNECTION_ITEM_FIELD_SERVER_HOST'))
			,
			(new IntegerField('SERVER_PORT'))
				->configureDefaultValue(80)
				->configureTitle(Loc::getMessage('DAV_CONNECTION_ITEM_FIELD_SERVER_PORT'))
			,
			(new StringField('SERVER_USERNAME'))
				->configureSize(128)
				->configureNullable()
				->configureTitle(Loc::getMessage('DAV_CONNECTION_ITEM_FIELD_SERVER_USERNAME'))
			,
			(new StringField('SERVER_PASSWORD'))
				->configureSize(128)
				->configureNullable()
				->configureTitle(Loc::getMessage('DAV_CONNECTION_ITEM_FIELD_SERVER_PASSWORD'))
			,
			(new StringField('SERVER_PATH'))
				->configureSize(128)
				->configureDefaultValue('/')
				->configureTitle(Loc::getMessage('DAV_CONNECTION_ITEM_FIELD_SERVER_PATH'))
			,
			(new StringField('LAST_RESULT'))
				->configureSize(128)
				->configureNullable()
				->configureTitle(Loc::getMessage('DAV_CONNECTION_ITEM_FIELD_LAST_RESULT'))
			,
			(new DatetimeField('CREATED'))
				->configureDefaultValue(static function () {
					return new DateTime();
				})
				->configureTitle(Loc::getMessage('DAV_CONNECTION_ITEM_FIELD_CREATED'))
			,
			(new DatetimeField('MODIFIED'))
				->configureDefaultValue(static function () {
					return new DateTime();
				})
				->configureTitle(Loc::getMessage('DAV_CONNECTION_ITEM_FIELD_MODIFIED'))
			,
			(new DatetimeField('SYNCHRONIZED'))
				->configureNullable()
				->configureTitle(Loc::getMessage('DAV_CONNECTION_ITEM_FIELD_SYNCHRONIZED'))
			,
			(new BooleanField('IS_DELETED'))
				->configureRequired()
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('N')
				->configureTitle(Loc::getMessage('DAV_CONNECTION_ITEM_FIELD_DELETED'))
			,
			(new DatetimeField('NEXT_SYNC_TRY'))
				->configureDefaultValue(static function () {
					return new DateTime();
				})
				->configureTitle(Loc::getMessage('DAV_CONNECTION_ITEM_FIELD_NEXT_SYNC_TRY'))
			,
		];
	}
}
