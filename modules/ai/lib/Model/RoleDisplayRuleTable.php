<?php declare(strict_types=1);

namespace Bitrix\AI\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;


/**
 * Class RoleDisplayRuleTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RoleDisplayRule_Query query()
 * @method static EO_RoleDisplayRule_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_RoleDisplayRule_Result getById($id)
 * @method static EO_RoleDisplayRule_Result getList(array $parameters = [])
 * @method static EO_RoleDisplayRule_Entity getEntity()
 * @method static \Bitrix\AI\Model\EO_RoleDisplayRule createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Model\EO_RoleDisplayRule_Collection createCollection()
 * @method static \Bitrix\AI\Model\EO_RoleDisplayRule wakeUpObject($row)
 * @method static \Bitrix\AI\Model\EO_RoleDisplayRule_Collection wakeUpCollection($rows)
 */
class RoleDisplayRuleTable extends Entity\DataManager
{
	use DeleteByFilterTrait;

	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_ai_role_display_rule';
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

			(new Entity\IntegerField('ROLE_ID'))
				->configureRequired(),

			(new Entity\StringField('NAME'))
				->configureRequired(),

			(new Entity\BooleanField('IS_CHECK_INVERT'))
				->configureRequired()
				->configureValues(0, 1),

			(new Entity\StringField('VALUE'))
				->configureRequired(),

			(new Reference(
				'ROLE',
				RoleTable::class,
				Join::on('this.ROLE_ID', 'ref.ID')
			))
				->configureJoinType(Join::TYPE_RIGHT)
		];
	}
}
