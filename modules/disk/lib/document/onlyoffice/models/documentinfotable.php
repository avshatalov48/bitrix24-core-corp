<?php

namespace Bitrix\Disk\Document\OnlyOffice\Models;

use Bitrix\Disk\Internals\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\DateTime;

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