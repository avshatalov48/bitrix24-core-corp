<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Model;

use Bitrix\Main\Entity;

/**
 * Class OwnerOptionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_OwnerOption_Query query()
 * @method static EO_OwnerOption_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_OwnerOption_Result getById($id)
 * @method static EO_OwnerOption_Result getList(array $parameters = [])
 * @method static EO_OwnerOption_Entity getEntity()
 * @method static \Bitrix\AI\SharePrompt\Model\EO_OwnerOption createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\SharePrompt\Model\EO_OwnerOption_Collection createCollection()
 * @method static \Bitrix\AI\SharePrompt\Model\EO_OwnerOption wakeUpObject($row)
 * @method static \Bitrix\AI\SharePrompt\Model\EO_OwnerOption_Collection wakeUpCollection($rows)
 */
class OwnerOptionTable extends Entity\DataManager
{
	public static function getTableName(): string
	{
		return 'b_ai_prompt_owner_option';
	}

	public static function getMap(): array
	{
		return [
			(new Entity\IntegerField('ID'))
				->configureAutocomplete()
				->configurePrimary(),

			(new Entity\IntegerField('USER_ID'))
				->configureRequired(),

			(new Entity\StringField('SORTING_IN_FAVORITE_LIST'))
				->configureRequired(),
		];
	}
}
