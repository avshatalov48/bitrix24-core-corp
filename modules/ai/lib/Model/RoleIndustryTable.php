<?php

declare(strict_types=1);

namespace Bitrix\AI\Model;

use Bitrix\AI\Entity\RoleIndustry;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;

/**
 * Class RoleIndustryTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RoleIndustry_Query query()
 * @method static EO_RoleIndustry_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_RoleIndustry_Result getById($id)
 * @method static EO_RoleIndustry_Result getList(array $parameters = [])
 * @method static EO_RoleIndustry_Entity getEntity()
 * @method static \Bitrix\AI\Entity\RoleIndustry createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Model\EO_RoleIndustry_Collection createCollection()
 * @method static \Bitrix\AI\Entity\RoleIndustry wakeUpObject($row)
 * @method static \Bitrix\AI\Model\EO_RoleIndustry_Collection wakeUpCollection($rows)
 */
class RoleIndustryTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_ai_role_industry';
	}

	public static function getObjectClass(): string
	{
		return RoleIndustry::class;
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			new Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Entity\StringField('CODE', [
				'required' => true,
			]),
			(new ArrayField('NAME_TRANSLATES', [
				'default_value' => '',
			]))->configureSerializationJson(),
			new Entity\StringField('HASH', [
				'required' => true,
			]),
			new OneToMany('ROLES', RoleTable::class, 'INDUSTRY'),
			(new BooleanField('IS_NEW'))
				->configureValues(0, 1)
				->configureDefaultValue(0),
			new Entity\IntegerField('SORT'),
			new Entity\DatetimeField('DATE_MODIFY'),
		];
	}
}
