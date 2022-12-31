<?php

namespace Bitrix\Disk\Document\OnlyOffice\Models;

use Bitrix\Disk\Internals\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\DateTime;

/**
 * Class DocumentInfoTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DocumentInfo_Query query()
 * @method static EO_DocumentInfo_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DocumentInfo_Result getById($id)
 * @method static EO_DocumentInfo_Result getList(array $parameters = [])
 * @method static EO_DocumentInfo_Entity getEntity()
 * @method static \Bitrix\Disk\Document\OnlyOffice\Models\EO_DocumentInfo createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Document\OnlyOffice\Models\EO_DocumentInfo_Collection createCollection()
 * @method static \Bitrix\Disk\Document\OnlyOffice\Models\EO_DocumentInfo wakeUpObject($row)
 * @method static \Bitrix\Disk\Document\OnlyOffice\Models\EO_DocumentInfo_Collection wakeUpCollection($rows)
 */
final class DocumentInfoTable extends DataManager
{
	public const CONTENT_STATUS_INIT = 0;
	public const CONTENT_STATUS_EDITING = 1;
	public const CONTENT_STATUS_FORCE_SAVED_WITH_ERROR = 2;
	public const CONTENT_STATUS_FORCE_SAVED = 3;
	public const CONTENT_STATUS_SAVED_WITH_ERROR = 4;
	public const CONTENT_STATUS_SAVED = 5;
	public const CONTENT_STATUS_NO_CHANGES = 6;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_disk_onlyoffice_document_info';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			(new StringField('EXTERNAL_HASH'))
				->configurePrimary()
				->configureAutocomplete()
				->configureSize(128)
			,
			new IntegerField('OBJECT_ID'),
			new IntegerField('VERSION_ID'),
			(new IntegerField('OWNER_ID'))
				->configureRequired()
			,
			(new DatetimeField('CREATE_TIME'))
				->configureRequired()
				->configureDefaultValue(function() {
					return new DateTime();
				})
			,
			(new DatetimeField('UPDATE_TIME'))
				->configureRequired()
				->configureDefaultValue(function() {
					return new DateTime();
				})
			,
			(new IntegerField('USERS'))
				->configureDefaultValue(0)
			,
			(new IntegerField('CONTENT_STATUS'))
				->configureDefaultValue(self::CONTENT_STATUS_INIT)
			,
		];
	}
}