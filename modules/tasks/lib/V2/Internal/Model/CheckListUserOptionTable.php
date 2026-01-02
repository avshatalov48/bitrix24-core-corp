<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;

/**
 * Class CheckListUserOptionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CheckListUserOption_Query query()
 * @method static EO_CheckListUserOption_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CheckListUserOption_Result getById($id)
 * @method static EO_CheckListUserOption_Result getList(array $parameters = [])
 * @method static EO_CheckListUserOption_Entity getEntity()
 * @method static \Bitrix\Tasks\V2\Internal\Model\EO_CheckListUserOption createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\V2\Internal\Model\EO_CheckListUserOption_Collection createCollection()
 * @method static \Bitrix\Tasks\V2\Internal\Model\EO_CheckListUserOption wakeUpObject($row)
 * @method static \Bitrix\Tasks\V2\Internal\Model\EO_CheckListUserOption_Collection wakeUpCollection($rows)
 */
class CheckListUserOptionTable extends DataManager
{
	use DeleteByFilterTrait;

	public static function getTableName(): string
	{
		return 'b_tasks_checklist_user_option';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))->configurePrimary()->configureAutocomplete(),
			(new IntegerField('USER_ID'))->configureRequired(),
			(new IntegerField('ITEM_ID'))->configureRequired(),
			(new IntegerField('OPTION_CODE'))->configureRequired(),
		];
	}
}
