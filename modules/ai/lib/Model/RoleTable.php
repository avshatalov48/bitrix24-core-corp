<?php

declare(strict_types=1);

namespace Bitrix\AI\Model;

use Bitrix\AI\Entity\Role;
use Bitrix\AI\Entity\RoleIndustry;
use Bitrix\AI\Model\RoleTranslateDescriptionTable;
use Bitrix\AI\Model\RoleTranslateNameTable;
use Bitrix\AI\ShareRole\Model\ShareTable;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\Relations\ManyToMany;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\UserTable;
use Bitrix\Main\Web\Json;

/**
 * Class RoleTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Role_Query query()
 * @method static EO_Role_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Role_Result getById($id)
 * @method static EO_Role_Result getList(array $parameters = [])
 * @method static EO_Role_Entity getEntity()
 * @method static \Bitrix\AI\Entity\Role createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Model\EO_Role_Collection createCollection()
 * @method static \Bitrix\AI\Entity\Role wakeUpObject($row)
 * @method static \Bitrix\AI\Model\EO_Role_Collection wakeUpCollection($rows)
 */
class RoleTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_ai_role';
	}

	public static function getObjectClass(): string
	{
		return Role::class;
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
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
			(new Entity\IntegerField('ID'))
				->configureAutocomplete()
				->configurePrimary(),

			(new Entity\StringField('CODE'))
				->configureRequired(),

			(new ArrayField('NAME_TRANSLATES'))
				->configureDefaultValue('')
				->configureSerializationJson(),

			(new ArrayField('DESCRIPTION_TRANSLATES'))
				->configureDefaultValue('')
				->configureSerializationJson(),

			new Entity\StringField('INDUSTRY_CODE'),
			(new Entity\StringField('HASH'))
				->configureRequired(),

			(new Entity\IntegerField('AUTHOR_ID'))
				->configureDefaultValue(0),

			(new Entity\IntegerField('EDITOR_ID'))
				->configureDefaultValue(0),

			(new Entity\StringField('INSTRUCTION'))
				->configureRequired(),

			(new ArrayField('AVATAR'))
				->configureSerializationJson()
				->configureUnserializeCallback(function ($value) {
					try
					{
						$value = Json::decode($value);
					}
					catch (\Throwable)
					{
						return [];
					}

					return $value;
				}),

			(new BooleanField('IS_NEW'))
				->configureValues(0, 1)
				->configureDefaultValue(0),

			(new BooleanField('IS_RECOMMENDED'))
				->configureValues(0, 1)
				->configureDefaultValue(0),

			(new BooleanField('IS_ACTIVE'))
				->configureValues(0, 1)
				->configureDefaultValue(1),

			(new Entity\BooleanField('IS_SYSTEM'))
				->configureDefaultValue('Y')
				->configureValues('N', 'Y'),

			new Entity\IntegerField('SORT'),

			new Entity\DatetimeField('DATE_CREATE'),

			new Entity\DatetimeField('DATE_MODIFY'),

			new Entity\StringField('DEFAULT_NAME'),

			new Entity\StringField('DEFAULT_DESCRIPTION'),

		];
	}

	private static function getReferenceMap(): array
	{
		return [
			(new ManyToMany('PROMPTS', PromptTable::class))
				->configureTableName('b_ai_role_prompt'),

			(new OneToMany('RULES', RoleDisplayRuleTable::class, 'ROLE'))
				->configureJoinType(Join::TYPE_LEFT),

			(new OneToMany('NAMES', RoleTranslateNameTable::class, 'ROLE')),

			(new OneToMany('DESCRIPTIONS', RoleTranslateDescriptionTable::class, 'ROLE')),

			(new Entity\ReferenceField(
				'INDUSTRY',
				RoleIndustry::class,
				['=this.INDUSTRY_CODE' => 'ref.CODE']
			))->configureJoinType(Join::TYPE_LEFT),

			(new Entity\ReferenceField(
				'ROLE_SHARES',
				ShareTable::class,
				join::on('this.ID', 'ref.ROLE_ID')
			))->configureJoinType(Join::TYPE_LEFT),

			(new Entity\ReferenceField(
				'ROLE_FAVORITES',
				RoleFavoriteTable::class,
				join::on('this.CODE', 'ref.ROLE_CODE')
			))->configureJoinType(Join::TYPE_LEFT),

			(new Entity\ReferenceField(
				'USER_EDITOR',
				UserTable::class,
				Join::on('this.EDITOR_ID', 'ref.ID')
			))->configureJoinType(Join::TYPE_LEFT),

			(new Entity\ReferenceField(
				'USER_AUTHOR',
				UserTable::class,
				Join::on('this.AUTHOR_ID', 'ref.ID')
			))->configureJoinType(Join::TYPE_LEFT)
		];
	}

	public static function onBeforeDelete(Event $event): void
	{
		$primary = $event->getParameter('id');
		$template = static::getEntity()->wakeUpObject($primary['ID']);
		$template->removeAllPrompts();
		$template->save();
	}
}
