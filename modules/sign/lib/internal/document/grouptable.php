<?php

namespace Bitrix\Sign\Internal\Document;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;

Loc::loadMessages(__FILE__);

/**
 * Class GroupTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Group_Query query()
 * @method static EO_Group_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Group_Result getById($id)
 * @method static EO_Group_Result getList(array $parameters = [])
 * @method static EO_Group_Entity getEntity()
 * @method static \Bitrix\Sign\Internal\Document\Group createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Internal\Document\GroupCollection createCollection()
 * @method static \Bitrix\Sign\Internal\Document\Group wakeUpObject($row)
 * @method static \Bitrix\Sign\Internal\Document\GroupCollection wakeUpCollection($rows)
 */
final class GroupTable extends Entity\DataManager
{
	public static function getObjectClass(): string
	{
		return Group::class;
	}

	public static function getCollectionClass(): string
	{
		return GroupCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_sign_document_group';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,
			(new IntegerField('CREATED_BY_ID'))
				->configureRequired()
			,
			(new DatetimeField('DATE_CREATE'))
				->configureRequired()
			,
			(new DatetimeField('DATE_MODIFY'))
				->configureNullable()
			,
		];
	}
}
