<?php

namespace Bitrix\Sign\Internal\LegalLog;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\TextField;
use Bitrix\Main\ORM\Fields\Validators\RangeValidator;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Class LegalLogTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_LegalLog_Query query()
 * @method static EO_LegalLog_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_LegalLog_Result getById($id)
 * @method static EO_LegalLog_Result getList(array $parameters = [])
 * @method static EO_LegalLog_Entity getEntity()
 * @method static \Bitrix\Sign\Internal\LegalLog\LegalLog createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Internal\LegalLog\LegalLogCollection createCollection()
 * @method static \Bitrix\Sign\Internal\LegalLog\LegalLog wakeUpObject($row)
 * @method static \Bitrix\Sign\Internal\LegalLog\LegalLogCollection wakeUpCollection($rows)
 */
final class LegalLogTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_sign_legal_log';
	}

	public static function getObjectClass(): string
	{
		return LegalLog::class;
	}

	public static function getCollectionClass(): string
	{
		return LegalLogCollection::class;
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configureTitle('ID')
				->configurePrimary()
				->configureAutocomplete()
			,
			(new IntegerField('DOCUMENT_ID'))
				->configureTitle('Document id')
				->configureRequired()
				->addValidator(new RangeValidator(min: 1))
			,
			(new StringField('DOCUMENT_UID'))
				->configureTitle('Document Uid')
				->configureRequired()
				->addValidator(new LengthValidator(1, 20))
			,
			(new IntegerField('MEMBER_ID'))
				->configureTitle('Member id')
				->configureNullable()
			,
			(new StringField('MEMBER_UID'))
				->configureTitle('Member Uid')
				->configureNullable()
				->addValidator(new LengthValidator(1, 32))
			,
			(new StringField('CODE'))
				->configureTitle('Code')
				->configureRequired()
				->addValidator(new LengthValidator(1, 50))
			,
			(new TextField('DESCRIPTION'))
				->configureTitle('Description')
				->configureNullable()
			,
			(new IntegerField('USER_ID'))
				->configureTitle('User id')
				->configureNullable()
			,
			(new DatetimeField('DATE_CREATE'))
				->configureTitle('Created at')
				->configureNullable()
		];
	}
}
