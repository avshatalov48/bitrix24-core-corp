<?php declare(strict_types=1);

namespace Bitrix\AI\Model;

use Bitrix\AI\Model\RoleTable;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class RoleTranslateDescriptionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RoleTranslateDescription_Query query()
 * @method static EO_RoleTranslateDescription_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_RoleTranslateDescription_Result getById($id)
 * @method static EO_RoleTranslateDescription_Result getList(array $parameters = [])
 * @method static EO_RoleTranslateDescription_Entity getEntity()
 * @method static \Bitrix\AI\Model\EO_RoleTranslateDescription createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Model\EO_RoleTranslateDescription_Collection createCollection()
 * @method static \Bitrix\AI\Model\EO_RoleTranslateDescription wakeUpObject($row)
 * @method static \Bitrix\AI\Model\EO_RoleTranslateDescription_Collection wakeUpCollection($rows)
 */
class RoleTranslateDescriptionTable extends Entity\DataManager
{
	use DeleteByFilterTrait;

	public const DEFAULT_LANG = 'en';

	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_ai_role_translate_description';
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			(new Entity\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new Entity\StringField('ROLE_ID'))
				->configureRequired(),
			(new Entity\StringField('LANG'))
				->configureRequired(),
			(new Entity\StringField('TEXT'))
				->configureRequired(),
			(new Reference(
				'ROLE',
				RoleTable::class,
				Join::on('this.ROLE_ID', 'ref.ID')
			))
				->configureJoinType(Join::TYPE_LEFT),
		];
	}
}
