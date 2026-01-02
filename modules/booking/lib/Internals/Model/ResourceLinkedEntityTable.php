<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;

/**
 * Class ResourceLinkedEntityTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ResourceLinkedEntity_Query query()
 * @method static EO_ResourceLinkedEntity_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ResourceLinkedEntity_Result getById($id)
 * @method static EO_ResourceLinkedEntity_Result getList(array $parameters = [])
 * @method static EO_ResourceLinkedEntity_Entity getEntity()
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceLinkedEntity createObject($setDefaultValues = true)
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceLinkedEntity_Collection createCollection()
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceLinkedEntity wakeUpObject($row)
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceLinkedEntity_Collection wakeUpCollection($rows)
 */
class ResourceLinkedEntityTable extends DataManager
{
	use DeleteByFilterTrait;

	public static function getTableName(): string
	{
		return 'b_booking_resource_linked_entity';
	}

	public static function getMap(): array
	{
		return [
			...static::getScalarMap(),
			...static::getReferenceMap(),
		];
	}

	private static function getScalarMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new IntegerField('RESOURCE_ID'))
				->configureRequired(),

			(new IntegerField('ENTITY_ID'))
				->configureRequired(),

			(new StringField('ENTITY_TYPE'))
				->addValidator(new LengthValidator(1, 255))
				->configureRequired(),

			(new DatetimeField('CREATED_AT'))
				->configureDefaultValue(new DateTime()),

			(new TextField('DATA')),
		];
	}

	private static function getReferenceMap(): array
	{
		return [
			(new Reference(
				'RESOURCE',
				ResourceTable::class,
				Join::on('this.RESOURCE_ID', 'ref.ID'))
			)->configureJoinType(Join::TYPE_INNER),
		];
	}
}
