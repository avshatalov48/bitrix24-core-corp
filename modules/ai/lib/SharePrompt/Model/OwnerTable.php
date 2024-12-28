<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Model;

use Bitrix\Main\Entity;

/**
 * Class OwnerTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Owner_Query query()
 * @method static EO_Owner_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Owner_Result getById($id)
 * @method static EO_Owner_Result getList(array $parameters = [])
 * @method static EO_Owner_Entity getEntity()
 * @method static \Bitrix\AI\SharePrompt\Model\EO_Owner createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\SharePrompt\Model\EO_Owner_Collection createCollection()
 * @method static \Bitrix\AI\SharePrompt\Model\EO_Owner wakeUpObject($row)
 * @method static \Bitrix\AI\SharePrompt\Model\EO_Owner_Collection wakeUpCollection($rows)
 */
class OwnerTable extends Entity\DataManager
{
	public static function getTableName(): string
	{
		return 'b_ai_prompt_owner';
	}

	public static function getMap(): array
	{
		return [
			(new Entity\IntegerField('ID'))
				->configureAutocomplete()
				->configurePrimary(),

			(new Entity\IntegerField('USER_ID'))
				->configureRequired(),

			(new Entity\IntegerField('PROMPT_ID'))
				->configureRequired(),

			(new Entity\BooleanField('IS_FAVORITE'))
				->configureValues(0, 1)
				->configureDefaultValue(false),

			(new Entity\BooleanField('IS_DELETED'))
				->configureValues(0, 1)
				->configureDefaultValue(false)
		];
	}
}
