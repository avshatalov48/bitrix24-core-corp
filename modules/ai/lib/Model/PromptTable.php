<?php

namespace Bitrix\AI\Model;

use Bitrix\AI\Entity\Prompt;
use Bitrix\AI\SharePrompt\Model\ShareTable;
use Bitrix\AI\SharePrompt\Repository\CategoryRepository;
use Bitrix\AI\Container;
use Bitrix\AI\SharePrompt\Repository\TranslateNameRepository;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\Relations\ManyToMany;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\UserTable;

/**
 * Class PromptTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Prompt_Query query()
 * @method static EO_Prompt_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Prompt_Result getById($id)
 * @method static EO_Prompt_Result getList(array $parameters = [])
 * @method static EO_Prompt_Entity getEntity()
 * @method static \Bitrix\AI\Entity\Prompt createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Model\EO_Prompt_Collection createCollection()
 * @method static \Bitrix\AI\Entity\Prompt wakeUpObject($row)
 * @method static \Bitrix\AI\Model\EO_Prompt_Collection wakeUpCollection($rows)
 */
class PromptTable extends Entity\DataManager
{
	const TYPE_SIMPLE_TEMPLATE = 'SIMPLE_TEMPLATE';
	const TYPE_DEFAULT = 'DEFAULT';

	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_ai_prompt';
	}

	public static function getObjectClass()
	{
		return Prompt::class;
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
				->configurePrimary()
				->configureAutocomplete(),

			new Entity\StringField('APP_CODE'),
			new Entity\IntegerField('PARENT_ID'),

			(new ArrayField('CACHE_CATEGORY'))
				->configureSerializationJson(),

			new Entity\StringField('SECTION'),
			new Entity\IntegerField('SORT'),

			(new Entity\StringField('CODE'))
				->configureRequired(),

			new Entity\StringField('DEFAULT_TITLE'),

			(new Entity\EnumField('TYPE'))
				->configureRequired(false)
				->configureValues([static::TYPE_DEFAULT, static::TYPE_SIMPLE_TEMPLATE]),

			new Entity\StringField('ICON'),

			(new Entity\StringField('HASH'))
				->configureRequired(),

			new Entity\StringField('PROMPT'),

			(new ArrayField('TEXT_TRANSLATES'))
				->configureDefaultValue('')
				->configureSerializationJson(),

			new Entity\StringField('IS_SYSTEM'),

			new Entity\IntegerField('AUTHOR_ID'),

			new Entity\IntegerField('EDITOR_ID'),

			new Entity\DatetimeField('DATE_MODIFY'),

			new Entity\DatetimeField('DATE_CREATE'),

			(new ArrayField('SETTINGS'))
				->configureSerializationJson(),

			new Entity\StringField('WORK_WITH_RESULT'),

			(new BooleanField('IS_NEW'))
				->configureValues(0, 1)
				->configureDefaultValue(0),

			(new BooleanField('IS_ACTIVE'))
				->configureValues(0, 1)
				->configureDefaultValue(1),
		];
	}

	private static function getReferenceMap(): array
	{
		return [
			(new ManyToMany('ROLES', RoleTable::class))
				->configureTableName('b_ai_role_prompt'),

			(new OneToMany('RULES', PromptDisplayRuleTable::class, 'PROMPT'))
				->configureJoinType(Join::TYPE_LEFT),

			(new Reference(
				'PROMPT_SHARES',
				ShareTable::class,
				Join::on('this.ID', 'ref.PROMPT_ID')
			))->configureJoinType(Join::TYPE_LEFT),

			(new Reference(
				'PROMPT_CATEGORIES',
				PromptCategoryTable::class,
				Join::on('this.ID', 'ref.PROMPT_ID')
			))->configureJoinType(Join::TYPE_LEFT),

			(new Reference(
				'USER_EDITOR',
				UserTable::class,
				Join::on('this.EDITOR_ID', 'ref.ID')
			))->configureJoinType(Join::TYPE_LEFT),

			(new Reference(
				'USER_AUTHOR',
				UserTable::class,
				Join::on('this.AUTHOR_ID', 'ref.ID')
			))->configureJoinType(Join::TYPE_LEFT)
		];
	}

	public static function delete($primary): DeleteResult
	{
		$result = parent::delete($primary);

		if (!$result->isSuccess() || !is_numeric($primary))
		{
			return $result;
		}

		$promptId = (int)$primary;
		/** @var CategoryRepository $categoryRepository */
		$categoryRepository = Container::init()->getItem(CategoryRepository::class);
		$categoryRepository->deleteByPromptId($promptId);

		/** @var TranslateNameRepository $translateNameRepository */
		$translateNameRepository = Container::init()->getItem(TranslateNameRepository::class);
		$translateNameRepository->deleteByPromptId($promptId);

		return $result;
	}
}
