<?php

namespace Bitrix\Call\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;
use Bitrix\Call\Track;
use Bitrix\Im\Model\CallTable;

\Bitrix\Main\Loader::includeModule('im');

/**
 * Class CallTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CallTrack_Query query()
 * @method static EO_CallTrack_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CallTrack_Result getById($id)
 * @method static EO_CallTrack_Result getList(array $parameters = [])
 * @method static EO_CallTrack_Entity getEntity()
 * @method static \Bitrix\Call\Track createObject($setDefaultValues = true)
 * @method static \Bitrix\Call\Track\TrackCollection createCollection()
 * @method static \Bitrix\Call\Track wakeUpObject($row)
 * @method static \Bitrix\Call\Track\TrackCollection wakeUpCollection($rows)
 */
class CallTrackTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_call_track';
	}

	public static function getObjectClass(): string
	{
		return Track::class;
	}

	public static function getCollectionClass(): string
	{
		return Track\TrackCollection::class;
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new IntegerField('CALL_ID'))
				->configureRequired(),

			(new IntegerField('EXTERNAL_TRACK_ID'))
				->configureNullable(),

			(new IntegerField('FILE_ID'))
				->configureNullable(),

			(new IntegerField('DISK_FILE_ID'))
				->configureNullable(),

			(new DatetimeField('DATE_CREATE'))
				->configureDefaultValue(function (){return new DateTime;}),

			(new EnumField('TYPE'))
				->configureValues([
					Track::TYPE_TRACK_PACK,
					Track::TYPE_RECORD,
				])
				->configureNullable(),

			(new StringField('DOWNLOAD_URL'))
				->configureRequired(),

			(new StringField('FILE_NAME'))
				->configureSize(100)
				->configureNullable(),

			(new IntegerField('DURATION'))
				->configureNullable(),

			(new IntegerField('FILE_SIZE'))
				->configureNullable(),

			(new StringField('FILE_MIME_TYPE'))
				->configureSize(50)
				->configureNullable(),

			(new StringField('TEMP_PATH'))
				->configureSize(255)
				->configureNullable(),

			(new BooleanField('DOWNLOADED'))
				->configureValues('N', 'Y')
				->configureDefaultValue('N'),

			(new Reference('CALL', CallTable::class, Join::on('this.CALL_ID', 'ref.ID'))),
		];
	}
}