<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;

/**
 * Class ShareTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Share_Query query()
 * @method static EO_Share_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Share_Result getById($id)
 * @method static EO_Share_Result getList(array $parameters = [])
 * @method static EO_Share_Entity getEntity()
 * @method static \Bitrix\AI\SharePrompt\Model\EO_Share createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\SharePrompt\Model\EO_Share_Collection createCollection()
 * @method static \Bitrix\AI\SharePrompt\Model\EO_Share wakeUpObject($row)
 * @method static \Bitrix\AI\SharePrompt\Model\EO_Share_Collection wakeUpCollection($rows)
 */
class ShareTable extends Entity\DataManager
{
	use DeleteByFilterTrait;

	public static function getTableName(): string
	{
		return 'b_ai_prompt_share';
	}

	public static function getMap(): array
	{
		return [
			(new Entity\IntegerField('ID'))
				->configureAutocomplete()
				->configurePrimary(),

			(new Entity\IntegerField('PROMPT_ID'))
				->configureRequired(),

			(new Entity\StringField('ACCESS_CODE'))
				->configureRequired(),

			new Entity\DatetimeField('DATE_CREATE'),

			new Entity\IntegerField('CREATED_BY'),
		];
	}
}
