<?php

declare(strict_types=1);

namespace Bitrix\AI\Model;

use Bitrix\Main\Entity;

/**
 * Class RolePromptTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RolePrompt_Query query()
 * @method static EO_RolePrompt_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_RolePrompt_Result getById($id)
 * @method static EO_RolePrompt_Result getList(array $parameters = [])
 * @method static EO_RolePrompt_Entity getEntity()
 * @method static \Bitrix\AI\Model\EO_RolePrompt createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Model\EO_RolePrompt_Collection createCollection()
 * @method static \Bitrix\AI\Model\EO_RolePrompt wakeUpObject($row)
 * @method static \Bitrix\AI\Model\EO_RolePrompt_Collection wakeUpCollection($rows)
 */
class RolePromptTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_ai_role_prompt';
	}

	public static function getMap(): array
	{
		return [
			(new Entity\IntegerField('ROLE_ID'))
				->configurePrimary(),
			(new Entity\IntegerField('PROMPT_ID'))
				->configurePrimary(),
			new Entity\DatetimeField('DATE_CREATE'),
		];
	}
}
