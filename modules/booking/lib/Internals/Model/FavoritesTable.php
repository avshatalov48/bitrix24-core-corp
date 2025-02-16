<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model;

use Bitrix\Booking\Internals\Model\Trait\InsertIgnoreTrait;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class FavoriteResourceTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Favorites_Query query()
 * @method static EO_Favorites_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Favorites_Result getById($id)
 * @method static EO_Favorites_Result getList(array $parameters = [])
 * @method static EO_Favorites_Entity getEntity()
 * @method static \Bitrix\Booking\Internals\Model\EO_Favorites createObject($setDefaultValues = true)
 * @method static \Bitrix\Booking\Internals\Model\EO_Favorites_Collection createCollection()
 * @method static \Bitrix\Booking\Internals\Model\EO_Favorites wakeUpObject($row)
 * @method static \Bitrix\Booking\Internals\Model\EO_Favorites_Collection wakeUpCollection($rows)
 */
final class FavoritesTable extends DataManager
{
	use InsertIgnoreTrait;
	use DeleteByFilterTrait;

	public static function getTableName(): string
	{
		return 'b_booking_favorites';
	}

	public static function getMap(): array
	{
		return array_merge(
			static::getScalarMap(),
			static::getReferenceMap(),
		);
	}

	private static function getScalarMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new IntegerField('MANAGER_ID'))
				->configureRequired(),

			(new IntegerField('RESOURCE_ID'))
				->configureRequired(),

			(new StringField('TYPE'))
				->addValidator(new LengthValidator(1, 10)),
		];
	}

	private static function getReferenceMap(): array
	{
		return [
			(new Reference(
				'RESOURCE',
				ResourceTable::getEntity(),
				Join::on('this.RESOURCE_ID', 'ref.ID')
			))->configureJoinType(Join::TYPE_INNER),
		];
	}
}
