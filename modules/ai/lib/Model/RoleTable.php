<?php

declare(strict_types=1);

namespace Bitrix\AI\Model;

use Bitrix\AI\Entity\Role;
use Bitrix\AI\Entity\RoleIndustry;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\Relations\ManyToMany;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Query\Join;

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

			new Entity\ReferenceField(
				'INDUSTRY', RoleIndustry::class, ['=this.INDUSTRY_CODE' => 'ref.CODE']
			),

			(new Entity\StringField('HASH'))
				->configureRequired(),

			(new Entity\StringField('INSTRUCTION'))
				->configureRequired(),

			(new ManyToMany('PROMPTS', PromptTable::class))
				->configureTableName('b_ai_role_prompt'),

			(new OneToMany('RULES', RoleDisplayRuleTable::class, 'ROLE'))
				->configureJoinType(Join::TYPE_LEFT),


			(new ArrayField('AVATAR'))->configureSerializationJson(),

			(new BooleanField('IS_NEW'))
				->configureValues(0, 1)
				->configureDefaultValue(0),

			(new BooleanField('IS_RECOMMENDED'))
				->configureValues(0, 1)
				->configureDefaultValue(0),

			new Entity\IntegerField('SORT'),

			new Entity\DatetimeField('DATE_MODIFY'),
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
