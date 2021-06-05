<?php

namespace Bitrix\Disk\Document\OnlyOffice\Models;

use Bitrix\Main\Entity\BooleanField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Type\DateTime;

final class DocumentSessionTable extends DataManager
{
	public const TYPE_VIEW = 0;
	public const TYPE_EDIT = 2;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_disk_onlyoffice_document_session';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,
			new IntegerField('OBJECT_ID'),
			new IntegerField('VERSION_ID'),
			(new IntegerField('USER_ID'))
				->configureRequired()
			,
			(new IntegerField('OWNER_ID'))
				->configureRequired()
			,
			(new BooleanField('IS_EXCLUSIVE'))
				->configureDefaultValue(false),
			(new StringField('EXTERNAL_HASH'))
				->configureRequired()
				->configureSize(128)
				->configureDefaultValue(function() {
					return Random::getString(32, true);
				})
			,
			(new DatetimeField('CREATE_TIME'))
				->configureRequired()
				->configureDefaultValue(function() {
					return new DateTime();
				})
			,
			(new IntegerField('TYPE'))
				->configureDefaultValue(self::TYPE_VIEW)
			,
			new TextField('CONTEXT'),
		];
	}
}