<?php

namespace Bitrix\Tasks\Scrum\Internal;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields;

/**
 * Class TypeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Type_Query query()
 * @method static EO_Type_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Type_Result getById($id)
 * @method static EO_Type_Result getList(array $parameters = [])
 * @method static EO_Type_Entity getEntity()
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Type createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Type_Collection createCollection()
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Type wakeUpObject($row)
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Type_Collection wakeUpCollection($rows)
 */
class TypeTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_tasks_scrum_type';
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getMap()
	{
		$id = new Fields\IntegerField('ID');
		$id->configurePrimary(true);
		$id->configureAutocomplete(true);

		$entityId = new Fields\IntegerField('ENTITY_ID');

		$name = new Fields\StringField('NAME');
		$name->addValidator(new Fields\Validators\LengthValidator(1, 255));

		$sort = new Fields\IntegerField('SORT');
		$sort->configureDefaultValue(0);

		$dodRequired = new Fields\StringField('DOD_REQUIRED');
		$dodRequired->addValidator(new Fields\Validators\LengthValidator(1, 1));

		$participants = new Fields\Relations\OneToMany(
			'PARTICIPANTS',
			TypeParticipantsTable::class,
			'TYPE'
		);

		return [
			$id,
			$entityId,
			$name,
			$sort,
			$dodRequired,
			$participants,
		];
	}
}