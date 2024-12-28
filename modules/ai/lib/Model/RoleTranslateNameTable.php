<?php declare(strict_types=1);

namespace Bitrix\AI\Model;

use Bitrix\AI\Model\RoleTable;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class RoleTranslateNameTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RoleTranslateName_Query query()
 * @method static EO_RoleTranslateName_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_RoleTranslateName_Result getById($id)
 * @method static EO_RoleTranslateName_Result getList(array $parameters = [])
 * @method static EO_RoleTranslateName_Entity getEntity()
 * @method static \Bitrix\AI\Model\EO_RoleTranslateName createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Model\EO_RoleTranslateName_Collection createCollection()
 * @method static \Bitrix\AI\Model\EO_RoleTranslateName wakeUpObject($row)
 * @method static \Bitrix\AI\Model\EO_RoleTranslateName_Collection wakeUpCollection($rows)
 */
class RoleTranslateNameTable extends Entity\DataManager
{
	use DeleteByFilterTrait;

	public const DEFAULT_LANG = 'en';

	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_ai_role_translate_name';
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
